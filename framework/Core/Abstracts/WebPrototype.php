<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * WebPrototype.php
 */

namespace SmoothPHP\Framework\Core\Abstracts;

use SmoothPHP\Framework\Core\ClassLoader\ClassLoader;
use SmoothPHP\Framework\Core\Cron\CronManager;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Routing\RouteDatabase;

abstract class WebPrototype {

	public function prepareClassloader(ClassLoader $loader) {
		$loader->loadFromComposer();
	}

	public abstract function initialize(Kernel $kernel);

	public function registerCron(CronManager $mgr) {
	}

	public abstract function registerRoutes(RouteDatabase $routes);

}