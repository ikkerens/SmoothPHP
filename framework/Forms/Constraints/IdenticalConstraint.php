<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * IdenticalConstraint.php
 */

namespace SmoothPHP\Framework\Forms\Constraints;

use RuntimeException;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class IdenticalConstraint extends Constraint {
	private $fieldName;

	public function __construct($fieldName) {
		$this->fieldName = $fieldName;
	}

	public function setOptions(array &$options) {
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if (!$form->hasField($this->fieldName))
			throw new RuntimeException('Attempted to compare form field but that field does not exist.');

		if ($value != $request->post->{$this->fieldName})
			$form->addErrorMessage('');
	}

}