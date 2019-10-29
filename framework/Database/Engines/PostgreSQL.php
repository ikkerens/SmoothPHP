<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * PostgreSQL.php
 */

namespace SmoothPHP\Framework\Database\Engines;

use PDOException;
use PDOStatement;
use SmoothPHP\Framework\Core\Config;
use SmoothPHP\Framework\Database\DatabaseException;

class PostgreSQL extends PDOEngine {

	public function getShortName() {
		return 'pg';
	}

	public function quote($field) {
		return '"' . $field . '"';
	}

	protected function getDSN(Config $config) {
		return sprintf('pgsql:host=%s port=%d dbname=%s %s',
			$config->db_host,
			$config->db_port,
			$config->db_database,
			$config->db_parameters);
	}

	/**
	 * This function will only work inside the CLI environment.
	 */
	public function wipe() {
		if (__ENV__ != 'cli')
			die('Wipe cannot be called outside of the CLI environment.');

		try {
			$this->connection->exec('DROP OWNED BY current_user CASCADE');
		} catch (PDOException $e) {
			throw new DatabaseException($e);
		}
	}

	protected function createEngineStatement(PDOStatement $stmt) {
		return new PostgreSQLStatement($stmt);
	}

}

class PostgreSQLStatement extends PDOSQLStatement {

	public function getInsertID() {
		$results = $this->getResults();

		if (!isset($results[0]['id']))
			return 0;

		return $results[0]['id'];
	}

}
