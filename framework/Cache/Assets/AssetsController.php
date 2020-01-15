<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AssetsController.php
 */

namespace SmoothPHP\Framework\Cache\Assets;

use SmoothPHP\Framework\Core\Abstracts\Controller;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Responses\FileStream;
use SmoothPHP\Framework\Localization\LanguageRepository;

class AssetsController extends Controller {

	public function getJS(AssetsRegister $register, array $path) {
		$file = $register->getJSPath(implode('/', $path));

		return new FileStream([
			'cache'    => true,
			'type'     => 'text/javascript',
			'filename' => end($path),
			'url'      => $file
		]);
	}

	public function getCompiledJS(Kernel $kernel, LanguageRepository $language, Request $request, $hash) {
		$file = __ROOT__ . 'cache/js/compiled.' . $hash . '.js';

		if (!file_exists($file)) {
			http_response_code(404);
			return $kernel->error($language->getEntry('smooth_error_404'));
		}

		$this->checkGZip($request, $file);

		return new FileStream([
			'cache'    => true,
			'type'     => 'text/javascript',
			'filename' => 'compiled.js',
			'url'      => $file
		]);
	}

	public function getCSS(AssetsRegister $register, array $path) {
		$file = $register->getCSSPath(implode('/', $path));

		return new FileStream([
			'cache'    => true,
			'type'     => 'text/css',
			'filename' => end($path),
			'url'      => $file
		]);
	}

	public function getCompiledCSS(Kernel $kernel, LanguageRepository $language, Request $request, $hash) {
		$file = __ROOT__ . 'cache/css/compiled.' . $hash . '.css';

		if (!file_exists($file)) {
			http_response_code(404);
			return $kernel->error($language->getEntry('smooth_error_404'));
		}

		$this->checkGZip($request, $file);

		return new FileStream([
			'cache'    => true,
			'type'     => 'text/css',
			'filename' => 'compiled.css',
			'url'      => $file
		]);
	}

	public function getImage(Kernel $kernel, LanguageRepository $language, array $path) {
		preg_match('/^(.+?)(?:\.([0-9]+?)x([0-9]+?))?\.([a-z]+)$/', implode('/', $path), $matches);

		$srcFile = sprintf('src/assets/images/%s.%s', $matches[1], $matches[4]);
		$srcFileFull = __ROOT__ . $srcFile;
		$cacheFile = sprintf('%scache/images/%s.%dx%d.%s.%s',
			__ROOT__,
			str_replace(['/', '\\'], ['_', '_'], $srcFile),
			$matches[2],
			$matches[3],
			file_hash($srcFileFull),
			$matches[4]);

		if (!file_exists($cacheFile)) {
			http_response_code(404);
			return $kernel->error($language->getEntry('smooth_error_404'));
		}

		return new FileStream([
			'cache'    => true,
			'type'     => image_type_to_mime_type(exif_imagetype($srcFileFull)),
			'filename' => end($path),
			'url'      => $cacheFile
		]);
	}

	public function getRaw(AssetsRegister $register, array $path) {
		$file = $register->getRawPath(implode('/', $path));

		return new FileStream([
			'cache'    => true,
			'filename' => end($path),
			'url'      => $file
		]);
	}

	public function favicon(Kernel $kernel, LanguageRepository $language) {
		return $this->getImage($kernel, $language, ['favicon.ico']);
	}

	private function checkGZip(Request $request, &$file) {
		if (strpos($request->server->HTTP_ACCEPT_ENCODING, 'gzip') !== false) {
			$file .= '.gz';
			header('Content-Encoding: gzip');
		}

		header('Vary: Accept-Encoding');
	}

}