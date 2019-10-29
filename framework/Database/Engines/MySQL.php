<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * MySQL.php
 */

namespace SmoothPHP\Framework\Database\Engines;

use PDO;
use PDOException;
use PDOStatement;
use SmoothPHP\Framework\Core\Config;
use SmoothPHP\Framework\Database\DatabaseException;

class MySQL extends PDOEngine {

	public function getShortName() {
		return 'my';
	}

	public function quote($field) {
		return '`' . $field . '`';
	}

	/**
	 * This function will only work inside the CLI environment.
	 */
	public function wipe() {
		if (__ENV__ != 'cli')
			die('Wipe cannot be called outside of the CLI environment.');

		global $kernel;

		try {
			print('Dropping constraints...' . PHP_EOL);
			$constraintsStmt = $this->connection->query("SELECT DISTINCT
			    CONCAT('ALTER TABLE `',
			            K.TABLE_NAME,
			            '` DROP FOREIGN KEY `',
			            K.CONSTRAINT_NAME,
			            '`;') AS query
			FROM
			    information_schema.KEY_COLUMN_USAGE K
			        LEFT JOIN
			    information_schema.REFERENTIAL_CONSTRAINTS C USING (CONSTRAINT_NAME)
			WHERE
			    K.REFERENCED_TABLE_SCHEMA = " . $this->connection->quote($kernel->getConfig()->db_database));

			$constraints = $constraintsStmt->fetchAll();
			foreach ($constraints as $constraint) {
				printf('Executing: %s' . PHP_EOL, $constraint['query']);
				$this->connection->exec($constraint['query']);
			}

			print('Dropping tables...' . PHP_EOL);
			$databasesStmt = $this->connection->query("
			SELECT
			    concat('DROP TABLE IF EXISTS `', table_name, '`;') AS query
			FROM
			    information_schema.tables
			WHERE
			    table_schema = " . $this->connection->quote($kernel->getConfig()->db_database));

			$databases = $databasesStmt->fetchAll();
			foreach ($databases as $database) {
				printf('Executing: %s' . PHP_EOL, $database['query']);
				$this->connection->exec($database['query']);
			}
		} catch (PDOException $e) {
			throw new DatabaseException($e);
		}
	}

	protected function getDSN(Config $config) {
		return sprintf('mysql:host=%s;port=%d;dbname=%s;%s',
			$config->db_host,
			$config->db_port,
			$config->db_database,
			$config->db_parameters);
	}

	protected function createEngineStatement(PDOStatement $stmt) {
		return new MySQLStatement($this->connection, $stmt);
	}
}

class MySQLStatement extends PDOSQLStatement {
	private $connection;

	public function __construct(PDO $connection, PDOStatement $stmt) {
		parent::__construct($stmt);
		$this->connection = $connection;
	}

	public function getInsertID() {
		try {
			return $this->connection->lastInsertId();
		} catch (PDOException $e) {
			throw new DatabaseException($e);
		}
	}
}
