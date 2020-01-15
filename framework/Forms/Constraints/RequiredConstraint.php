<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * RequiredConstraint.php
 */

namespace SmoothPHP\Framework\Forms\Constraints;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class RequiredConstraint extends Constraint {

	public function setOptions(array &$options) {
		$options['attr']['required'] = 'required';
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if (!isset($value) || strlen($value) == 0) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_required'), $label));
		}
	}

}