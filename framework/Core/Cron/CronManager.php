<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CronManager.php
 */

namespace SmoothPHP\Framework\Core\Cron;

use SmoothPHP\Framework\Core\Kernel;

class CronManager {
	/* @var CronHandler */
	private $handler;
	private $jobs;

	public function __construct(Kernel $kernel) {
		$handlerClass = $kernel->getConfig()->cron_handler;
		$this->handler = new $handlerClass();
		$this->jobs = [];
	}

	public function newJob($jobname, $timer, $callable) {
		$this->jobs[$jobname] = new Job($jobname, $timer, $callable);
	}

	public function install() {
		$this->handler->save($this->jobs);
	}

	public function uninstall() {
		$this->handler->save([]);
	}

	public function run(Kernel $kernel, $name) {
		$this->jobs[$name]->run($kernel);
	}

}