<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * RuntimeCacheProvider.php
 */

namespace SmoothPHP\Framework\Cache\Builder;

abstract class RuntimeCacheProvider extends CacheProvider {
	private static $useAPC;
	protected $cacheBuilder;

	/**
	 * @return RuntimeCacheProvider
	 */
	public static function create(callable $cacheBuilder) {
		if (!isset(self::$useAPC)) {
			if (__ENV__ != 'dev')
				if (extension_loaded('apcu'))
					self::$useAPC = 'apcu';
				else if (extension_loaded('apc'))
					self::$useAPC = 'apc';
				else
					self::$useAPC = false;
			else
				self::$useAPC = false;
		}

		if (self::$useAPC)
			return new APCCacheProvider($cacheBuilder, self::$useAPC . '_fetch', self::$useAPC . '_store');
		else
			return new ImmediateCacheProvider($cacheBuilder);
	}

	protected function __construct(callable $cacheBuilder) {
		$this->cacheBuilder = $cacheBuilder ?: 'file_get_contents';
	}

}