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
				'placeholder' => '...',
				'step'        => null,
				'min'         => null,
				'max'         => null
			]
		]);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		parent::checkConstraint($request, $name, $label, $value, $form);

		if (!is_numeric($value)) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_non_numeric'), $label));
			return;
		}

		if ($this->options['attr']['step'] !== null && ($value % $this->options['attr']['step']) !== 0) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_numeric_modulo'), $label, $this->options['attr']['step']));
			return;
		}

		if ($this->options['attr']['min'] !== null && $value < $this->options['attr']['min']) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_numeric_min'), $label, $this->options['attr']['min']));
			return;
		}

		if ($this->options['attr']['max'] !== null && $value > $this->options['attr']['max']) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_numeric_max'), $label, $this->options['attr']['max']));
		}
	}

}