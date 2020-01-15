<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * SQLStatement.php
 */

namespace SmoothPHP\Framework\Database\Statements;

use SmoothPHP\Framework\Database\Database;
use SmoothPHP\Framework\Database\DatabaseResult;
use SmoothPHP\Framework\Database\Engines\Statement;

abstract class SQLStatement {
	protected $db;

	protected $query;
	protected $params;
	protected $args;

	/* @var Statement */
	private $stmt;

	/**
	 * @param Database $db
	 * @param $query
	 */
	public function __construct(Database $db, $query) {
		$this->db = $db;
		$this->query = $query;

		$this->__wakeup();
		$this->verifyStmtAwake();
	}

	public function __sleep() {
		return ['db', 'query'];
	}

	public function __wakeup() {
		$this->params = [];
		$this->args = [];
	}

	private function verifyStmtAwake() {
		if (!isset($this->stmt)) {
			$this->db->__wakeup();
			$this->stmt = $this->db->getEngine()->prepare($this->query, $this->args);
		}
	}

	public function getStatement() {
		$this->verifyStmtAwake();
		return $this->stmt;
	}

	/**
	 * @return DatabaseResult|int
	 */
	public function execute() {
		$this->verifyStmtAwake();

		$args = func_get_args();
		if (isset($args[0]) && is_array($args[0]))
			$args = $args[0];

		for ($i = 0; $i < count($args); $i++)
			$this->args[$i] = $args[$i];

		$this->stmt->execute();

		return $this->createResult();
	}

	protected abstract function createResult();
}
