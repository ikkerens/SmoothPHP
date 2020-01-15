<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * PasswordType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Form;

class PasswordType extends StringType {

	public function __construct($field) {
		parent::__construct($field);

		$this->options = array_replace_recursive($this->options, [
			'attr' => [
				'type'        => 'password',
				'placeholder' => 'language:smooth_form_password'
			]
		]);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		parent::checkConstraint($request, $name, $label, $value, $form);
		// Make sure we never send back the password
		unset($this->options['attr']['value']);
	}

}