<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * DatabaseResult.php
 */

namespace SmoothPHP\Framework\Database;

class DatabaseResult {
	private $results;
	private $current;

	public function __construct(array $results) {
		$this->results = $results;
		$this->current = 0;
	}

	public function hasData() {
		return isset($this->results[$this->current]);
	}

	public function next() {
		$this->current++;
		if ($this->current >= count($this->results))
			return false;
		else
			return true;
	}

	public function getAsArray($flatten = true) {
		if ($flatten) {
			$arrays = array_values($this->results);

			if (isset($arrays[0]) && is_array($arrays[0]) && count($arrays[0]) == 1) {
				$arrays = array_map(function ($value) {
					return current($value);
				}, $arrays);
			}

			return $arrays;
		} else
			return $this->results;
	}

	public function __get($varName) {
		if (isset($this->results[$this->current][$varName]))
			return $this->results[$this->current][$varName];
		else
			return false;
	}

	public function __iterate() {
		return $this->results;
	}
}