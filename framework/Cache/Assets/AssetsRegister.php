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

class AssetsRegister {
	/* @var FileCacheProvider */
	private $jsCache, $cssCache, $rawCache;
	/* @var AssetDistributor */
	private $cdn;
	/* @var $imageCache ImageCache */
	private $imageCache;
	private $js, $css;

	public function initialize(Kernel $kernel) {
		$this->js = [];
		$this->css = [];

		if (__ENV__ == 'dev') {
			$this->jsCache = new FileCacheProvider('js', null, [AssetsRegister::class, 'simpleLoad']);
			$this->cssCache = new FileCacheProvider('css', null, [AssetsRegister::class, 'simpleLoad']);
		} else {
			$this->jsCache = new FileCacheProvider('js', 'final.js', [AssetsRegister::class, 'minifyJS']);
			$this->cssCache = new FileCacheProvider('css', 'final.css', [AssetsRegister::class, 'minifyCSS']);
		}
		$this->rawCache = new FileCacheProvider('raw', null, 'file_get_contents');
		$this->cdn = new LocalAssetDistributor();
		$this->imageCache = new ImageCache('images');

		$route = $kernel->getRouteDatabase();
		if ($route) {
			$route->register([
				'name'       => 'favicon',
				'path'       => 'favicon.ico',
				'controller' => AssetsController::class,
				'call'       => 'favicon',
				'robots'     => Robots::HIDE,
				'internal'   => true
			]);
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
		if (strtolower(substr($file, 0, 4)) != 'http') {
			$path = self::getSourcePath('js', $file);
			$this->jsCache->fetch($path);
		}
	}

	public function getJSFiles() {
		return $this->js;
	}

	public function getJSPath($file) {
		return $this->jsCache->getCachePath(self::getSourcePath('js', $file));
	}

	public function addCSS($file) {
		$this->css[] = $file;
		if (strtolower(substr($file, 0, 4)) != 'http') {
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
			return $this->cdn->getImageURL($cachePath, $virtualPath, $width, $height);
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

	public static function simpleLoad($filePath) {
		global $kernel;
		return $kernel->getTemplateEngine()->simpleFetch($filePath, [
			'assets' => $kernel->getAssetsRegister(),
			'route'  => $kernel->getRouteDatabase()
		]);
	}

	public static function minifyCSS($filePath) {
		/** @noinspection PhpFullyQualifiedNameUsageInspection */
		return (new \tubalmartin\CssMin\Minifier())->run(self::simpleLoad($filePath));
	}

	public static function minifyJS($filePath) {
		/** @noinspection PhpFullyQualifiedNameUsageInspection */
		return \JShrink\Minifier::minify(self::simpleLoad($filePath));
	}

}
