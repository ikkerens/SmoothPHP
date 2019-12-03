<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AssetsRegister.php
 */

namespace SmoothPHP\Framework\Cache\Assets;

use SmoothPHP\Framework\Cache\Assets\Distribution\AssetDistributor;
use SmoothPHP\Framework\Cache\Assets\Distribution\LocalAssetDistributor;
use SmoothPHP\Framework\Cache\Builder\FileCacheProvider;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Robots;
use SmoothPHP\Framework\Localization\JSLanguage;

class AssetsRegister {
	private $fileHashCache;
	/* @var FileCacheProvider */
	private $jsCache, $cssCache, $rawCache;
	/* @var AssetDistributor */
	private $cdn;
	/* @var ImageCache */
	private $imageCache;
	private $js, $css, $lang;

	public function __construct() {
		if (__ENV__ != 'prod')
			return;

		$this->fileHashCache = [];
		$cache = function ($file, $dir) {
			if ($dir) return;
			$realpath = realpath($file);
			$this->fileHashCache[$realpath] = md5_file($realpath);
		};
		traverse_path(__ROOT__ . 'framework/meta/assets/', $cache);
		traverse_path(__ROOT__ . 'src/templates/', $cache);
		traverse_path(__ROOT__ . 'src/assets/', $cache);
	}

	public function initialize(Kernel $kernel) {
		$this->js = [];
		$this->css = [];
		$this->lang = [];

		if (__ENV__ == 'dev') {
			$this->jsCache = new FileCacheProvider('js', 'js',
				[AssetsRegister::class, 'simpleJS'],
				[AssetsRegister::class, 'loadJS'],
				[AssetsRegister::class, 'saveJS']);
			$this->cssCache = new FileCacheProvider('css', null, [AssetsRegister::class, 'simpleCSS']);
		} else {
			$this->jsCache = new FileCacheProvider('js', 'final.js',
				[AssetsRegister::class, 'minifyJS'],
				[AssetsRegister::class, 'loadJS'],
				[AssetsRegister::class, 'saveJS']);
			$this->cssCache = new FileCacheProvider('css', 'final.css', [AssetsRegister::class, 'minifyCSS']);
		}
		$this->rawCache = new FileCacheProvider('raw', null, 'file_get_contents');
		$this->cdn = new LocalAssetDistributor();
		$this->imageCache = new ImageCache('images');

		$route = $kernel->getRouteDatabase();
		if ($route) {
			if (file_exists(__ROOT__ . 'src/assets/images/favicon.ico')) {
				$this->imageCache->ensureCache(self::getSourcePath('images', 'favicon.ico'), $nope, $nope);
				$route->register([
					'name'       => 'favicon',
					'path'       => '/favicon.ico',
					'controller' => AssetsController::class,
					'call'       => 'favicon',
					'robots'     => Robots::HIDE,
					'internal'   => true
				]);
			}
			$route->register([
				'name'       => 'assets_images',
				'path'       => '/images/...',
				'controller' => AssetsController::class,
				'call'       => 'getImage',
				'robots'     => Robots::HIDE,
				'internal'   => true
			]);
			$route->register([
				'name'       => 'assets_raw',
				'path'       => '/raw/...',
				'controller' => AssetsController::class,
				'call'       => 'getRaw',
				'robots'     => Robots::HIDE,
				'internal'   => true
			]);

			if (__ENV__ != 'dev') {
				$route->register([
					'name'       => 'assets_css_compiled',
					'path'       => '/css/%/compiled.css',
					'controller' => AssetsController::class,
					'call'       => 'getCompiledCSS',
					'robots'     => Robots::HIDE,
					'internal'   => true
				]);
				$route->register([
					'name'       => 'assets_js_compiled',
					'path'       => '/js/%/compiled.js',
					'controller' => AssetsController::class,
					'call'       => 'getCompiledJS',
					'robots'     => Robots::HIDE,
					'internal'   => true
				]);
			} else {
				$route->register([
					'name'       => 'assets_js',
					'path'       => '/js/...',
					'controller' => AssetsController::class,
					'call'       => 'getJS',
					'robots'     => Robots::HIDE,
					'internal'   => true
				]);
				$route->register([
					'name'       => 'assets_css',
					'path'       => '/css/...',
					'controller' => AssetsController::class,
					'call'       => 'getCSS',
					'robots'     => Robots::HIDE,
					'internal'   => true
				]);
			}
		}
	}

	public function getCachedFileHash($realpath) {
		return isset($this->fileHashCache[$realpath]) ? $this->fileHashCache[$realpath] : null;
	}

	public function getAssetDistributor() {
		return $this->cdn;
	}

	public function setAssetDistributor(AssetDistributor $distributor) {
		$this->cdn = $distributor;
	}

	public static function getSourcePath($type, $file) {
		if (file_exists($file))
			return $file;

		$path = sprintf('%ssrc/assets/%s/%s', __ROOT__, $type, $file);
		if (!file_exists($path))
			throw new \RuntimeException($type . " file '" . $file . "' does not exist.");
		return $path;
	}

	public function addJS($file) {
		$this->js[] = $file;
		if (mb_strtolower(substr($file, 0, 4)) != 'http') {
			$path = self::getSourcePath('js', $file);
			list($lang, $_) = $this->jsCache->fetch($path);
			$this->lang = array_merge($this->lang, $lang);
		}
	}

	public function getJSFiles() {
		return $this->js;
	}

	public function getJSLanguageKeys() {
		return array_unique($this->lang);
	}

	public function getJSPath($file) {
		return $this->jsCache->getCachePath(self::getSourcePath('js', $file));
	}

	public function addCSS($file) {
		$this->css[] = $file;
		if (mb_strtolower(substr($file, 0, 4)) != 'http') {
			$path = self::getSourcePath('css', $file);
			$this->cssCache->fetch($path);
		}
	}

	public function getCSSPath($file) {
		return $this->cssCache->getCachePath(self::getSourcePath('css', $file));
	}

	public function getCSSFiles() {
		return $this->css;
	}

	public function getImage($file, $width = null, $height = null) {
		$cachePath = $this->imageCache->ensureCache(self::getSourcePath('images', $file), $width, $height);
		$fileInfo = pathinfo($file);

		$mimes = [
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif'  => 'image/gif',
			'png'  => 'image/png'
		];

		global $kernel;
		if (__ENV__ != 'dev' && isset($mimes[$fileInfo['extension']]) && filesize($cachePath) <= $kernel->getConfig()->image_inline_threshold)
			return sprintf('data:%s;base64,%s', $mimes[$fileInfo['extension']], base64_encode(file_get_contents($cachePath)));
		else {
			$virtualImageName = sprintf('%s%s.%dx%d.%s',
				$fileInfo['dirname'] == '.' ? '' : ($fileInfo['dirname'] . '/'),
				$fileInfo['filename'],
				$width,
				$height,
				$fileInfo['extension']);

			$virtualPath = $kernel->getRouteDatabase()->buildPath('assets_images', $virtualImageName);
			if (__ENV__ == 'dev')
				return $virtualPath;
			return $this->cdn->getImageURL($cachePath, $virtualPath);
		}
	}

	public function getRaw($file) {
		$path = self::getSourcePath('raw', $file);
		$this->rawCache->fetch($path);

		global $kernel;
		return $kernel->getRouteDatabase()->buildPath('assets_raw', $file);
	}

	public function getRawPath($file) {
		return $this->rawCache->getCachePath(self::getSourcePath('raw', $file));
	}

	public static function simpleCSS($filePath) {
		global $kernel;
		return $kernel->getTemplateEngine()->simpleFetch($filePath, [
			'assets' => $kernel->getAssetsRegister(),
			'route'  => $kernel->getRouteDatabase()
		]);
	}

	public static function simpleJS($filePath) {
		global $kernel;
		$language = new JSLanguage();
		$tpl = $kernel->getTemplateEngine()->simpleFetch($filePath, [
			'assets'   => $kernel->getAssetsRegister(),
			'route'    => $kernel->getRouteDatabase(),
			'language' => $language,
		]);
		return [$language->getKeys(), $tpl];
	}

	public static function saveJS($fileName, $data) {
		list($lang, $tpl) = $data;
		file_put_contents($fileName . '.lang', serialize($lang));
		file_put_contents($fileName, $tpl);
	}

	public static function loadJS($fileName) {
		$lang = unserialize(file_get_contents($fileName . '.lang'));
		$tpl = file_get_contents($fileName);
		return [$lang, $tpl];
	}

	public static function minifyCSS($filePath) {
		/** @noinspection PhpFullyQualifiedNameUsageInspection */
		return (new \tubalmartin\CssMin\Minifier())->run(self::simpleCSS($filePath));
	}

	public static function minifyJS($filePath) {
		list($lang, $tpl) = self::simpleJS($filePath);
		/** @noinspection PhpFullyQualifiedNameUsageInspection */
		return [$lang, \JShrink\Minifier::minify($tpl)];
	}

}
