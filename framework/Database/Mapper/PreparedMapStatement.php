<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * PreparedMapStatement.php
 */

namespace SmoothPHP\Framework\Database\Mapper;

use SmoothPHP\Framework\Database\Statements\SQLStatement;

class PreparedMapStatement {
	public $params, $references;
	/* @var $statement SQLStatement */
	public $statement;

	public function __construct() {
		$this->params = [];
		$this->references = [];
	}
}