<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FormBuilder.php
 */

namespace SmoothPHP\Framework\Forms;

use RuntimeException;
use SmoothPHP\Framework\Forms\Containers\FormContainer;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Types as Types;

class FormBuilder {
	private $action = null;

	private $header = ['attr' => []];
	private $options;

	/**
	 * @param string $field of the field
	 * @param string $type Type name of the field
	 * @param array $options Options to be used, such as label
	 * @return $this
	 */
	public function add($field, $type = null, array $options = []) {
		if (isset($this->options[$field]))
			throw new RuntimeException("Form field has already been declared.");

		$this->options[$field] = array_merge_recursive([
			'field' => $field,
			'type'  => $type ?: Types\StringType::class,
			'attr'  => []
		], $options);

		return $this;
	}

	public function setAction() {
		global $kernel;
		$action = func_get_arg(0);

		if ($kernel->getRouteDatabase()->getRoute($action))
			$this->action = call_user_func_array([$kernel->getRouteDatabase(), 'buildPath'], func_get_args());
		else
			$this->action = $action;
	}

	public function setTokenRequired($required) {
		$this->header['token'] = $required;
	}

	public function setHeaderAttribute($name, $value) {
		$this->header['attr'][$name] = $value;
	}

	public function getForm() {
		$elements = [];

		foreach ($this->options as $key => $value) {
			/* @var $element Type */
			$element = new $value['type']($key);
			$element->initialize($value);
			$elements[$key] = new FormContainer($element->getContainer());
		}

		return new Form($this->action, $this->header, $elements);
	}
}