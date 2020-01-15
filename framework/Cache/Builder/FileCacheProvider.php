<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileCacheProvider.php
 */

namespace SmoothPHP\Framework\Cache\Builder;

use SmoothPHP\Framework\Core\Lock;

class FileCacheProvider extends CacheProvider {
	const PERMS = 0755;

	private $cacheFileFormat;
	private $cacheBuilder;
	private $readCache, $writeCache;

	public function __construct($folder, $ext = null, $cacheBuilder = null, $readCache = null, $writeCache = null) {
		$ext = $ext ?: $folder;
		$this->cacheFileFormat = sprintf('%scache/%s/%s.%s.%s', __ROOT__, $folder, '%s', '%s', $ext);

		$this->cacheBuilder = $cacheBuilder ?: 'file_get_contents';
		$this->readCache = $readCache ?: 'file_get_contents';
		$this->writeCache = $writeCache ?: 'file_put_contents';
	}

	public function fetch($sourceFile, $cacheBuilder = null, $readCache = null, $writeCache = null) {
		$cacheBuilder = $cacheBuilder ?: $this->cacheBuilder;
		$readCache = $readCache ?: $this->readCache;
		$writeCache = $writeCache ?: $this->writeCache;

		$cacheFile = $this->getCachePath($sourceFile, $fileName);
		if (!is_dir(dirname($cacheFile)))
			mkdir(dirname($cacheFile), self::PERMS, true);

		// Try reading the cache
		try {
			if (file_exists($cacheFile))
				return call_user_func($readCache, $cacheFile);
		} catch (CacheExpiredException $e) {
		}

		// If we get to this point, the above return has not returned.
		// Which means we have to generate a new cache
		$lock = new Lock(pathinfo($cacheFile, PATHINFO_BASENAME));

		if ($lock->lock()) {
			array_map('unlink', glob(sprintf($this->cacheFileFormat, $fileName, '*'), GLOB_NOSORT));

			$newCache = call_user_func($cacheBuilder, $sourceFile);
			call_user_func($writeCache, $cacheFile, $newCache);

			$lock->unlock();
			return $newCache;
		} else
			return call_user_func($readCache, $cacheFile);
	}

	public function getCachePath($sourceFile, &$fileName = null) {
		$fileName = str_replace(['/', '\\'], ['_', '_'], str_replace(__ROOT__, '', $sourceFile));
		$checksum = file_hash($sourceFile);

		return sprintf($this->cacheFileFormat, $fileName, $checksum);
	}

}
