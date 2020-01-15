<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Bootstrap.php
 */

use SmoothPHP\Framework\Cache\Builder\RuntimeCacheProvider;
use SmoothPHP\Framework\Core\Abstracts\WebPrototype;
use SmoothPHP\Framework\Core\ClassLoader\BasicClassLoader;
use SmoothPHP\Framework\Core\Kernel;

if (__ENV__ != 'prod') {
	ini_set('display_errors', '1');
	error_reporting(E_ALL);
}

// Set an error handler that uses exceptions instead
set_error_handler(function ($severity, $msg, $file, $line) {
	throw new ErrorException($msg, 0, $severity, $file, $line);
});

if (!defined('__ROOT__'))
	define('__ROOT__', strrev(preg_replace(strrev('/public/'), '', strrev($_SERVER['DOCUMENT_ROOT']), 1)));

require_once __ROOT__ . 'framework/Core/Utilities.php';
require_once __ROOT__ . 'framework/Core/ClassLoader/BasicClassLoader.php';

$classLoader = new BasicClassLoader();
$classLoader->register();

/* @var $kernel Kernel */
$kernel = null;

return function (WebPrototype $prototype) use ($classLoader) {
	$prototype->prepareClassloader($classLoader);

	global $kernel;
	$kernel = RuntimeCacheProvider::create(function () use (&$kernel, $prototype) {
		$kernel = new Kernel();
		$kernel->loadPrototype($prototype);
		return $kernel;
	})->fetch("kernel");
};
