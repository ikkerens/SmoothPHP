<?php

/* !
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * * * *
 * Copyright (C) 2017 Rens Rikkerink
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * * * *
 * PreparedMapStatement.php
 * Internal class used by the object mapper to store data
 */

namespace SmoothPHP\Framework\Database\Mapper;

class PreparedMapStatement {
	public $params, $references;
	/* @var $statement \SmoothPHP\Framework\Database\Statements\MySQLStatement */
	public $statement;

	public function __construct() {
		$this->params = [];
		$this->references = [];
	}
}