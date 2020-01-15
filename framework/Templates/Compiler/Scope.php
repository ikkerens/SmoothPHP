<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Scope.php
 */

namespace SmoothPHP\Framework\Templates\Compiler;

class Scope {
	private $variables;

	public function __construct(Scope &$parent = null) {
		$this->variables = [];
		if ($parent != null)
			foreach ($parent->variables as $key => &$value)
				$this->variables[$key] = &$value;
	}

	public function __set($name, $value) {
		$this->variables[$name] = $value;
	}

	public function __get($name) {
		if (isset($this->variables[$name]))
			return $this->variables[$name];
		else
			return null;
	}

	public function __isset($name) {
		return $this->{$name} != null;
	}

}