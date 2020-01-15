<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ImmediateCacheProvider.php
 */

namespace SmoothPHP\Framework\Cache\Builder;

class ImmediateCacheProvider extends RuntimeCacheProvider {

	public function fetch($sourceFile, $cacheBuilder = null, $readCache = null, $writeCache = null) {
		$cacheBuilder = $cacheBuilder ?: $this->cacheBuilder;
		return $cacheBuilder($sourceFile);
	}

}