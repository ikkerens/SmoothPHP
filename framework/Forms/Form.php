<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Form.php
 */

namespace SmoothPHP\Framework\Forms;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\FormContainer;
use SmoothPHP\Framework\Forms\Containers\FormHeader;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Styles\FormStyle;

class Form extends FormContainer {
	private $action;

	private $hasResult;
	private $failReasons;

	public function __construct(FormStyle $style, $action, array $headerArgs, array $elements) {
		parent::__construct($style->buildForm(new FormHeader($this, $headerArgs), new FormContainer($elements)));
		$this->hasResult = false;
		$this->action = $action;
	}

	public function getAction($default = true) {
		if (!isset($this->action) && $default)
			return $_SERVER['REQUEST_URI'];
		return $this->action;
	}

	public function hasField($key) {
		return isset($this->inputs->{$key});
	}

	public function setValue($key, $value, $group = null) {
		$this->setAttribute($key, 'value', $value, $group);
	}

	public function setAttribute($key, $attribute, $value, $group = null) {
		/* @var $input Type */
		if ($group == null)
			$input = $this->inputs->{$key}->input;
		else
			$input = $this->inputs->{$group}->children->{$key};

		$input->setAttribute($attribute, $value);
	}

	public function setAction() {
		global $kernel;
		$action = func_get_arg(0);

		if ($kernel->getRouteDatabase()->getRoute($action))
			$this->action = call_user_func_array([$kernel->getRouteDatabase(), 'buildPath'], func_get_args());
		else
			$this->action = $action;
	}

	public function validate(Request $request) {
		if (!$request->post->hasData())
			return;

		$this->hasResult = true;
		$this->failReasons = [];
		$this->checkConstraint($request, null, null, null, $this);
	}

	public function hasResult() {
		return $this->hasResult;
	}

	public function isValid() {
		if (!$this->hasResult)
			return true;
		return isset($this->failReasons) && count($this->failReasons) == 0;
	}

	public function addErrorMessage($message) {
		if (!isset($this->failReasons))
			throw new \RuntimeException('Form has not yet been validated.');
		$this->failReasons[] = $message;
	}

	public function getErrorMessages() {
		return $this->failReasons;
	}

}