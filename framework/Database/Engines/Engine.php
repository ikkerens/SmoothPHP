<?php
/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Engine.php
 */

namespace SmoothPHP\Framework\Database\Engines;

use SmoothPHP\Framework\Core\Config;

interface Engine {
	public function connect(Config $config);

	public function getShortName();

	public function start();

	public function commit();

	public function rollback();

	public function prepare($query, array &$args = [], array &$params = []);

	public function quote($field);

	/**
	 * This function will only work inside the CLI environment.
	 */
	public function wipe();
}

interface Statement {
	public function execute();

	public function getInsertID();

	public function getResults();
}