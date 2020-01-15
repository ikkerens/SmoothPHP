<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Database.php
 */

namespace SmoothPHP\Framework\Database;

use SmoothPHP\Framework\Core\Config;
use SmoothPHP\Framework\Database\Engines\Engine;
use SmoothPHP\Framework\Database\Engines\MySQL;
use SmoothPHP\Framework\Database\Engines\PostgreSQL;
use SmoothPHP\Framework\Database\Mapper\DBObjectMapper;
use SmoothPHP\Framework\Database\Statements as Statements;

class Database {
	static $engines = [MySQL::class, PostgreSQL::class];

	/* @var $engine Engine */
	private $engine;
	private $config;
	private $maps;

	public function __construct(Config $config) {
		$this->config = $config;
		$this->__wakeup();
		$this->maps = [];
	}

	public function __sleep() {
		return ['config', 'maps'];
	}

	public function __wakeup() {
		if (!isset($this->engine)) {
			$this->engine = new $this->config->db_engine();
			$this->engine->connect($this->config);
		}
	}

	public function getEngine() {
		return $this->engine;
	}

	public function prepareCustom($query) {
		return new Statements\SQLCustomStatement($this, $query);
	}

	public function prepare($query, $returnsData = true) {
		return $returnsData ? new Statements\SQLStatementWithResult($this, $query)
			: new Statements\SQLStatementWithoutResult($this, $query);
	}

	public function prepareFile($filename, $returnsData = true) {
		$attempts = [
			__ROOT__ . 'src/sql/' . $filename . '.' . $this->engine->getShortName() . '.query.sql',
			__ROOT__ . 'src/sql/' . $filename . '.query.sql',
			__ROOT__ . 'framework/meta/sql/' . $filename . '.' . $this->engine->getShortName() . '.query.sql',
			__ROOT__ . 'framework/meta/sql/' . $filename . '.query.sql',
		];
		foreach ($attempts as $attempt)
			if (file_exists($attempt))
				return $this->prepare(file_get_contents($attempt), $returnsData);
		throw new \Exception('No sql query file ' . $filename . '.query.sql or ' . $filename . '.' . $this->engine->getShortName() . '.query.sql found in either src/sql/ or framework/meta/sql/');
	}

	public function start() {
		$this->engine->start();
	}

	public function commit() {
		$this->engine->commit();
	}

	public function rollback() {
		$this->engine->rollback();
	}

	/**
	 * @param $query
	 * @param array $params
	 * @return int Insert id if applicable, num_rows otherwise
	 */
	public function execute($query, array $params = []) {
		return $this->prepare($query, false)->execute($params);
	}

	/**
	 * @param $query
	 * @param array $params
	 * @return DatabaseResult
	 */
	public function fetch($query, array $params = []) {
		return $this->prepare($query, true)->execute($params);
	}

	/**
	 * @param $clazz
	 * @return DBObjectMapper
	 */
	public function map($clazz) {
		if (__ENV__ == 'cli')
			return null;

		if (!isset($this->maps[$clazz]))
			$this->maps[$clazz] = new DBObjectMapper($this, $clazz);

		return $this->maps[$clazz];
	}

}
