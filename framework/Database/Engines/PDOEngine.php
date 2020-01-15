<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * PDOEngine.php
 */

namespace SmoothPHP\Framework\Database\Engines;

use PDO;
use PDOException;
use PDOStatement;
use SmoothPHP\Framework\Core\Config;
use SmoothPHP\Framework\Database\DatabaseException;

abstract class PDOEngine implements Engine {
	/* @var $connection PDO */
	protected $connection;

	protected abstract function getDSN(Config $config);

	public function connect(Config $config) {
		try {
			$this->connection = new PDO($this->getDSN($config), $config->db_user, $config->db_password, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			]);
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}

	public function start() {
		try {
			$this->connection->beginTransaction();
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}

	public function commit() {
		try {
			$this->connection->commit();
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}

	public function rollback() {
		try {
			$this->connection->rollBack();
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}

	public function prepare($query, array &$args = []) {
		$previousMatch = null;
		$params = [];
		$types = [];

		$query = preg_replace_callback('/\'[^\']*\'(*SKIP)(*FAIL)|%([dfsbr])/', function (array $matches) use (&$previousMatch, &$args, &$params, &$types) {
			if ($matches[1] != 'r') {
				$args[] = null;
				$previousMatch = $matches[1];
			} else if ($previousMatch == null)
				throw new DatabaseException('Trying to use %r (repeat) in a query with no previous variables.');

			$pdoKey = null;
			switch ($previousMatch) {
				case 'd':
					$pdoKey = PDO::PARAM_INT;
					break;
				case 'b':
					$pdoKey = PDO::PARAM_LOB;
					break;
				default:
					$pdoKey = PDO::PARAM_STR;
			}
			$types[] = $pdoKey;
			$params[] = &$args[count($args) - 1];
			return '?';
		}, $query);

		try {
			$stmt = $this->connection->prepare($query);
			for ($i = 0; $i < count($types); $i++)
				$stmt->bindParam($i + 1, $params[$i], $types[$i]);
			return $this->createEngineStatement($stmt);
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}

	protected abstract function createEngineStatement(PDOStatement $stmt);

}

abstract class PDOSQLStatement implements Statement {
	protected $stmt;

	public function __construct(PDOStatement $stmt) {
		$this->stmt = $stmt;
	}

	public function execute() {
		try {
			$this->stmt->execute();
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}

	public abstract function getInsertID();

	public function getResults() {
		try {
			return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new DatabaseException('', 0, $e);
		}
	}
}