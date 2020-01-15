<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * SelectType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Form;

class SelectType extends Type {

	const KEY_SELECTOR = 0x1;
	const VALUE_SELECTOR = 0x2;

	const KEY_ONLY = (self::KEY_SELECTOR << 4) | self::KEY_SELECTOR;
	const VALUE_ONLY = (self::VALUE_SELECTOR << 4) | self::VALUE_SELECTOR;
	const KEY_VALUE_PAIR = (self::KEY_SELECTOR << 4) | self::VALUE_SELECTOR;
	const KEY_VALUE_INVERSE = (self::VALUE_SELECTOR << 4) | self::KEY_SELECTOR;

	public function __construct($field) {
		parent::__construct($field);
		$this->options = array_replace_recursive($this->options, [
			'options_mode'        => self::KEY_VALUE_INVERSE,
			'strict'              => true,
			'options'             => [],
			'options_attr'        => [],
			'selected'            => null,
			'empty_default'       => true,
			'empty_default_label' => '',
			'required'            => true
		]);
	}

	public function setAttribute($key, $value) {
		if ($key == 'value')
			$this->options['selected'] = $value;
		else
			parent::setAttribute($key, $value);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		parent::checkConstraint($request, $name, $label, $value, $form);
		$label = $label ?: last($this->options['label']);

		if (last($this->options['strict'])) {
			$mode = last($this->options['options_mode']);
			$method = ((($mode >> 4) & self::KEY_SELECTOR) == self::KEY_SELECTOR) ? 'array_keys' : 'array_values';
			$options = call_user_func($method, $this->options['options']);

			if (!in_array($value, $options)) {
				global $kernel;
				$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_selectvalue'), $value, $label));
			}
		}
	}

	public function __toString() {
		$attributes = $this->options['attr'];

		$attributes['id'] = $this->field;
		$attributes['name'] = $this->field;

		$mode = last($this->options['options_mode']);
		$options = [];
		$optionsAttr = $this->transformAttributes($this->options['options_attr']);
		$selected = last($this->options['selected']);

		if (last($this->options['empty_default']) && $selected == null)
			$options[] = '<option disabled selected value style="display: none;">' . last($this->options['empty_default_label']) . '</option>';

		foreach ($this->options['options'] as $key => $value) {
			$optionValue = ((($mode >> 4) & self::KEY_SELECTOR) == self::KEY_SELECTOR) ? $key : $value;
			$labelValue = (($mode & self::KEY_SELECTOR) == self::KEY_SELECTOR) ? $key : $value;
			$selectedStr = $selected != null && ($key == $selected || $value == $selected) ? ' selected' : '';
			$options[] = sprintf('<option value="%s"%s%s>%s</option>', $optionValue, strlen($optionsAttr) ? ' ' . $optionsAttr : '', $selectedStr, $labelValue);
		}

		return sprintf('<select %s>%s</select>', $this->transformAttributes($attributes), implode(' ', $options));
	}

}