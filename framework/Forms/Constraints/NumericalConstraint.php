<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * NumericalConstraint.php
 */

namespace SmoothPHP\Framework\Forms\Constraints;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class NumericalConstraint extends Constraint {
	private $step, $min, $max;

	public function __construct($step = null, $min = null, $max = null) {
		$this->step = $step;
		$this->min = $min;
		$this->max = $max;
	}

	public function setOptions(array &$options) {
		$options['attr']['type'] = 'number';
		if ($this->step)
			$options['attr']['step'] = $this->step;
		if ($this->min)
			$options['attr']['min'] = $this->min;
		if ($this->max)
			$options['attr']['max'] = $this->max;
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if (!is_numeric($value)) {
			global $kernel;
			$form->addErrorMessage($kernel->getLanguageRepository()->getEntry('smooth_form_non_numeric'));
		}
	}
}