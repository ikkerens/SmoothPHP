<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * DBObjectMapper.php
 */

namespace SmoothPHP\Framework\Database\Mapper;

use ReflectionClass;
use SmoothPHP\Framework\Database\Database;
use SmoothPHP\Framework\Database\DatabaseException;
use SmoothPHP\Framework\Database\Engines\MySQL;
use SmoothPHP\Framework\Database\Engines\PostgreSQL;
use SmoothPHP\Framework\Database\Statements\SQLStatementWithResult;

define('DB_NO_LIMIT', -1);

class DBObjectMapper {
	private $db;

	private $className, $classDef;
	private $fields;

	private $fetch, $insert, $delete;

	public function __construct(Database $db, $clazz) {
		$this->db = $db;
		$engine = $this->db->getEngine();

		$this->className = $clazz;
		$this->classDef = new ReflectionClass($clazz);

		if (!$this->classDef->isSubclassOf(MappedDBObject::class))
			throw new DatabaseException("Attempting to map an object that is not a subclass of MappedDBObject");

		$this->fields = array_filter(array_map(function (\ReflectionProperty $field) {
			if ($field->isStatic())
				return null;
			$field->setAccessible(true);
			return $field;
		}, $this->classDef->getProperties()), function ($value) {
			return $value != null;
		});

		// Prepare queries
		{
			/* @var $object MappedDBObject */
			$object = $this->classDef->newInstanceWithoutConstructor();

			// Set up fetch query
			{
				$query = 'SELECT ';

				$this->fetch = new PreparedMapStatement();

				$query .= implode(', ', array_map(function (\ReflectionProperty $field) use ($engine) {
					$this->fetch->references[] = null;
					$this->fetch->params[$field->getName()] = &$this->fetch->references[count($this->fetch->references) - 1];
					return $engine->quote($field->getName());
				}, $this->fields));

				$query .= ' FROM ' . $engine->quote($object->getTableName()) . ' WHERE ' . $engine->quote('id') . ' = %d';

				$this->fetch->statement = $this->db->prepare($query);
			}

			// Set up insert/update query
			{
				$query = 'INSERT INTO ' . $engine->quote($object->getTableName()) . ' (';

				$insertParams = [];
				$this->insert = new PreparedMapStatement();

				$first = true;
				array_map(function (\ReflectionProperty $field) use ($engine, &$object, &$query, &$insertParams, &$first) {
					if (!$first)
						$query .= ', ';
					else
						$first = false;
					$query .= $engine->quote($field->getName());

					$value = $field->getValue($object);
					if (is_int($value))
						$insertKey = '%d';
					else if (is_float($value))
						$insertKey = '%f';
					else
						$insertKey = '%s';

					if ($engine instanceof PostgreSQL && $field->getName() == 'id')
						$insertKey = 'CASE WHEN ' . $insertKey . ' != 0 THEN %r ELSE nextval(pg_get_serial_sequence(\'' . $object->getTableName() . '\', \'id\')) END';
					$insertParams[] = $insertKey;
					if ($field->getName() != 'id') {
						if ($engine instanceof MySQL)
							$this->insert->params[$field->getName()] = $engine->quote($field->getName()) . ' = VALUES(' . $engine->quote($field->getName()) . ')';
						else if ($engine instanceof PostgreSQL)
							$this->insert->params[$field->getName()] = $engine->quote($field->getName()) . ' = excluded.' . $engine->quote($field->getName());
					}
				}, $this->fields);

				$conflictCondition = 'ON DUPLICATE KEY UPDATE ';
				if ($engine instanceof PostgreSQL)
					$conflictCondition = 'ON CONFLICT (' . $engine->quote('id') . ') DO UPDATE SET ';
				$query .= ') VALUES (' . implode(', ', $insertParams) . ') ' . $conflictCondition . implode(', ', array_values($this->insert->params));
				if ($engine instanceof PostgreSQL)
					$query .= ' RETURNING "id"';
				$this->insert->statement = $this->db->prepare($query, false);
			}

			// Set up delete query
			{
				$query = 'DELETE FROM ' . $engine->quote($object->getTableName()) . ' WHERE ' . $engine->quote('id') . ' = %d';
				$this->delete = $this->db->prepare($query, false);
			}
		}
	}

	public function __wakeup() {
		$this->classDef = new ReflectionClass($this->className);
		$this->fields = array_filter(array_map(function (\ReflectionProperty $field) {
			if ($field->isStatic())
				return null;
			$field->setAccessible(true);
			return $field;
		}, $this->classDef->getProperties()), function ($value) {
			return $value != null;
		});
	}

	/**
	 * @param int|array|string $where Either an object ID, or an array of properties, or a string
	 * @param int $limit
	 * @return array|MappedDBObject
	 */
	public function fetch($where, $limit = 1) {
		$prepared = null;
		if (is_int($where) || ctype_digit($where)) {
			$prepared = $this->fetch;
			$rawResults = $prepared->statement->execute((int)$where);
		} else {
			$engine = $this->db->getEngine();
			$query = 'SELECT ';

			$prepared = new PreparedMapStatement();

			$query .= implode(', ', array_map(function (\ReflectionProperty $field) use ($engine, &$prepared) {
				$prepared->references[] = null;
				$prepared->params[$field->getName()] = &$prepared->references[count($prepared->references) - 1];
				return $engine->quote($field->getName());
			}, $this->fields));

			$query .= ' FROM ' . $engine->quote($this->classDef->newInstanceWithoutConstructor()->getTableName()) . ' WHERE ';

			if (is_array($where)) {
				$whereSeparator = 'AND';
				if (isset($where['_separator'])) {
					$whereSeparator = $where['_separator'];
					unset($where['_separator']);
				}

				$conditions = [];
				array_walk($where, function (&$value, $key) use ($engine, &$conditions) {
					if ($key[0] == '_')
						return;

					$comparator = '=';
					if (mb_strtolower(substr($key, 0, 5)) == 'like ') {
						$comparator = 'LIKE';
						$key = substr($key, 5);
					}
					$valueType = is_int($value) || ctype_digit($value) ? '%d' : '%s';
					$conditions[] = sprintf('%s %s %s', $engine->quote($key), $comparator, $valueType);
				});
				$query .= implode(' ' . $whereSeparator . ' ', $conditions);

				if (isset($where['_order'])) {
					$query .= ' ORDER BY ' . $where['_order'];
				}
			} else
				$query .= $where;

			if ($limit != DB_NO_LIMIT)
				$query .= ' LIMIT ' . $limit;

			$prepared->statement = new SQLStatementWithResult($this->db, $query);

			if (is_array($where)) {
				$rawResults = call_user_func_array([$prepared->statement, 'execute'], array_values($where));
			} else
				$rawResults = $prepared->statement->execute();
		}

		$results = [];
		foreach ($rawResults->getAsArray(false) as $result) {
			$target = $this->classDef->newInstanceWithoutConstructor();
			/* @var $field \ReflectionProperty */
			foreach ($this->fields as $field) {
				$field->setValue($target, $result[$field->getName()]);
			}
			$results[] = $target;
		}

		if ($limit == 1) {
			if (count($results) == 0)
				return null;
			else
				return current($results);
		}

		return $results;
	}

	public function insert(MappedDBObject $object) {
		$params = [];
		/* @var $field \ReflectionProperty */
		$idField = null;
		foreach ($this->fields as $field) {
			$val = $field->getValue($object);
			if ($field->getName() == 'id')
				$idField = $field;

			$params[] = $val;
		}

		$id = call_user_func_array([$this->insert->statement, 'execute'], $params);
		if ($id != 0)
			$idField->setValue($object, $id);
	}

	/**
	 * @param MappedDBObject|int $object
	 */
	public function delete($object) {
		if ($object instanceof MappedDBObject)
			$id = $object->getId();
		else
			$id = $object;
		$this->delete->execute($id);
	}

}