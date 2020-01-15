<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * EmailType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Form;

class EmailType extends Type {

	public function __construct($field) {
		parent::__construct($field);

		$this->options = array_replace_recursive($this->options, [
			'attr' => [
				'type'        => 'email',
				'placeholder' => 'language:smooth_form_email'
			]
		]);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		parent::checkConstraint($request, $name, $label, $value, $form);

		if ($value === '') // Don't run this check if there is no data, let that be handled by the RequiredConstraint
			return;

		if (!$request->post->email->get($this->field)) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_email_invalid'), $value));
		}
	}

}