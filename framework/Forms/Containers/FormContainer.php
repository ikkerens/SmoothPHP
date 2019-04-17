<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FormContainer.php
 */

namespace SmoothPHP\Framework\Forms\Containers;

use RuntimeException;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class FormContainer extends Constraint {
	private $backing;

	public function __construct(array $backing) {
		$this->backing = $backing;
	}

	public function __isset($name) {
		return isset($this->backing[$name]);
	}

	public function __get($name) {
		return $this->backing[$name];
	}

	public function __iterate() {
		return $this->backing;
	}

	public function __call($method, $args) {
		foreach ($this->backing as $sub)
			if (method_exists($sub, $method))
				return call_user_func_array([$sub, $method], $args);

		throw new RuntimeException(sprintf('The method %s::%s does not exist.', __CLASS__, $method));
	}

	public function __toString() {
		$result = '';
		foreach ($this->backing as $element)
			$result .= $element;
		return $result;
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		foreach ($this->backing as $element)
			if ($element instanceof Constraint) {
				if ($element instanceof Type) {
					$value = $request->post->get($element->getFieldName());
					$element->checkConstraint($request, $element->getFieldName(), null, $value, $form);
				} else
					$element->checkConstraint($request, null, null, $value, $form);
			}
	}

}