<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CustomType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Form;

class CustomType extends Type {

	public function __construct($field) {
		parent::__construct($field);
		$this->options = array_replace_recursive($this->options, [
			'required' => false,
			'content'  => '<i>content</i> parameter not set'
		]);
	}

	public function setAttribute($key, $value) {
		$this->options[$key] = $value;
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
	}

	public function __toString() {
		return last($this->options['content']);
	}

}