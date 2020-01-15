<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * APCCacheProvider.php
 */

namespace SmoothPHP\Framework\Cache\Builder;

use SmoothPHP\Framework\Core\Lock;

class APCCacheProvider extends RuntimeCacheProvider {
	private static $apcKey;

	private $cacheFetch, $cacheStore;

	public function __construct(callable $cacheBuilder, callable $cacheFetch, $cacheStore) {
		$this->cacheFetch = $cacheFetch;
		$this->cacheStore = $cacheStore;

		if (!isset(self::$apcKey)) {
			if (!is_dir(__ROOT__ . 'cache/'))
				mkdir(__ROOT__ . 'cache/', FileCacheProvider::PERMS);

			$cacheFile = __ROOT__ . 'cache/apc_app_id';
			if (!file_exists($cacheFile)) {
				self::$apcKey = md5(mt_rand(0, 99999));
				file_put_contents($cacheFile, self::$apcKey);
			} else {
				self::$apcKey = file_get_contents($cacheFile);
			}
		}

		parent::__construct($cacheBuilder);
	}

	public function fetch($sourceFile, $cacheBuilder = null, $readCache = null, $writeCache = null) {
		$cacheBuilder = $cacheBuilder ?: $this->cacheBuilder;
		if (file_exists($sourceFile) && !is_dir($sourceFile)) {
			$realPath = realpath($sourceFile);
			$checksum = file_hash($realPath);
		} else {
			$realPath = $sourceFile;
			$checksum = md5($sourceFile);
		}

		$cacheItem = call_user_func($this->cacheFetch, self::$apcKey . $realPath);

		if ($cacheItem instanceof APCCacheItem && $cacheItem->md5 == $checksum)
			return $cacheItem->value;
		else {
			$lock = new Lock(md5($realPath));

			if ($lock->lock()) {
				$newCache = call_user_func($cacheBuilder, $sourceFile);
				call_user_func($this->cacheStore, self::$apcKey . $realPath, new APCCacheItem($checksum, $newCache));
				return $newCache;
			} else
				return call_user_func($this->cacheFetch, self::$apcKey . $realPath)->value;
		}
	}

}

class APCCacheItem {
	public $md5;
	public $value;

	public function __construct($md5, $value) {
		$this->md5 = $md5;
		$this->value = $value;
	}
}
