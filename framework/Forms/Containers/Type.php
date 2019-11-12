<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Type.php
 */

namespace SmoothPHP\Framework\Forms\Containers;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Constraints\RequiredConstraint;
use SmoothPHP\Framework\Forms\Form;

abstract class Type extends Constraint {
	protected $field;
	protected $options;
	private $constraints;

	public function __construct($field) {
		$this->field = $field;
		$this->options = [
			'label'       => self::getLabel($field),
			'required'    => true,
			'attr'        => [
				'class' => ''
			],
			'constraints' => []
		];
	}

	public function initialize(array $options) {
		$this->options = array_merge_recursive($this->options, $options);

		$this->constraints = [];
		foreach ($this->options['constraints'] as $constraint) {
			if ($constraint instanceof Constraint)
				$this->constraints[] = $constraint;
			else
				$this->constraints[] = new $constraint();
		}

		if (last($this->options['required']))
			$this->constraints[] = new RequiredConstraint();

		foreach ($this->constraints as $constraint) {
			$copy = $this->options;
			$constraint->setOptions($copy);
			$this->options = array_replace_recursive($copy, $this->options);
		}
	}

	public function getContainer() {
		return [
			'rowstart'     => '<tr><td>',
			'label'        => $this->generateLabel(),
			'rowseparator' => '</td><td>',
			'input'        => $this,
			'rowend'       => '</td></tr>'
		];
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		$label = $label ?: last($this->options['label']);
		foreach ($this->constraints as $constraint)
			/* @var $constraint Constraint */
			$constraint->checkConstraint($request, $name, $label, $value, $form);
		$this->setAttribute('value', $value);
	}

	public function getFieldName() {
		return $this->field;
	}

	public function getAttribute($key) {
		return isset($this->options['attr'][$key]) ? $this->options['attr'][$key] : null;
	}

	public function setAttribute($key, $value) {
		$this->options['attr'][$key] = $value;
	}

	public function generateLabel() {
		return sprintf('<label for="%s">%s</label>',
			$this->field,
			last($this->options['label']));
	}

	public function __toString() {
		$attributes = $this->options['attr'];

		$attributes['id'] = $this->field;
		$attributes['name'] = $this->field;

		return sprintf('<input %s />', $this->transformAttributes($attributes));
	}

	protected function transformAttributes(array $attributes) {
		$htmlAttributes = [];

		foreach ($attributes as $key => $attribute) {
			if ($key == 'class')
				$attribute = implode(' ', array_filter((array)$attribute));
			else
				$attribute = $this->transformLanguage(last($attribute));
			if (isset($attribute) && strlen($attribute) > 0)
				$htmlAttributes[] = sprintf('%s="%s"', $key, addcslashes($attribute, '"'));
		}

		return implode(' ', $htmlAttributes);
	}

	protected static function getLabel($field) {
		$pieces = preg_split('/(?=[A-Z])/', $field);
		array_map('mb_strtolower', $pieces);
		$pieces[0] = ucfirst($pieces[0]);

		return implode(' ', $pieces);
	}

	private function transformLanguage($attribute) {
		static $languagePrefix = 'language:';

		if (strpos($attribute, $languagePrefix) === 0) {
			global $kernel;
			return $kernel->getLanguageRepository()->getEntry(substr($attribute, strlen($languagePrefix)));
		}

		return $attribute;
	}

}