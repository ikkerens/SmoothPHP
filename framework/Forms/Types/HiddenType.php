<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * HiddenType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Forms\Containers\Type;

class HiddenType extends Type {

	public function __construct($field) {
		parent::__construct($field);
		$this->options = array_replace_recursive($this->options, [
			'attr' => [
				'type' => 'hidden',
			]
		]);
	}

	public function getContainer() {
		return [
			'rowstart'     => '',
			'label'        => '',
			'rowseparator' => '',
			'input'        => $this,
			'rowend'       => ''
		];
	}

	public function __toString() {
		unset($this->options['attr']['required']);
		return parent::__toString();
	}

}