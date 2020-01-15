<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileSource.php
 */

namespace SmoothPHP\Framework\Flow\Requests\Files;

class FileSource {
	private $source;

	public function __construct(array $source) {
		$this->source = [];

		foreach ($source as $name => $element)
			$this->source[$name] = new FileElement($element);
	}

	/**
	 * @param $name
	 * @return FileElement|bool
	 */
	public function __get($name) {
		if (!isset($this->source[$name]))
			return false;

		return $this->source[$name];
	}
}