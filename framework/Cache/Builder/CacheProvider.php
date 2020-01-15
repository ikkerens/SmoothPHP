<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CacheProvider.php
 */

namespace SmoothPHP\Framework\Cache\Builder;

abstract class CacheProvider {

	abstract function fetch($sourceFile, $cacheBuilder = null, $readCache = null, $writeCache = null);

}