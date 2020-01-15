<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileDataSource.php
 */

namespace SmoothPHP\Framework\Localization;

use SmoothPHP\Framework\Cache\Builder\RuntimeCacheProvider;

class FileDataSource implements DataSource {
	/* @var RuntimeCacheProvider */
	private static $cache;
	private $folder;

	public function __construct($folder) {
		$this->__wakeup();
		$this->folder = $folder;
	}

	public function __wakeup() {
		if (!isset(self::$cache))
			self::$cache = RuntimeCacheProvider::create(function ($folder) {
				$dir = opendir($folder);
				$entries = [];
				while (($file = readdir($dir)) !== false) {
					if ($file == '.' || $file == '..')
						continue;
					$entries = array_merge_recursive(parse_ini_file($folder . DIRECTORY_SEPARATOR . $file, true, INI_SCANNER_RAW), $entries);
				}
				return array_change_key_case($entries, CASE_LOWER);
			});
	}

	public function getAvailableLanguages() {
		$entries = self::$cache->fetch($this->folder);
		return array_keys($entries);
	}

	public function getEntry($language, $key) {
		$entries = self::$cache->fetch($this->folder);
		if (!isset($entries[$language][$key]))
			return null;
		return $entries[$language][$key];
	}

}