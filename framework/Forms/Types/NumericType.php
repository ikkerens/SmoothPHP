<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * NumericType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Form;

class NumericType extends Type {

	public function __construct($field) {
		parent::__construct($field);
		$this->options = array_replace_recursive($this->options, [
			'attr' => [
				'type'        => 'number',
				'placeholder' => '...'
			]
		]);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		parent::checkConstraint($request, $name, $label, $value, $form);

		if (!is_numeric($value)) {
			global $kernel;
			$form->addErrorMessage($kernel->getLanguageRepository()->getEntry('smooth_form_non_numeric', $label));
		}
	}

}