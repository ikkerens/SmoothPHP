<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * VariableElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Elements\Element;

class VariableElement extends Element {
	private $varName;

	public function __construct($varName) {
		$this->varName = $varName;
	}

	public function getVarName() {
		return $this->varName;
	}

	public function optimize(CompilerState $tpl) {
		if ($tpl->isUncertain())
			return $this;

		$var = $tpl->vars->{$this->varName};
		if ($var != null)
			return $var;
		else
			return $this;
	}

	public function output(CompilerState $tpl) {
		if (!isset($tpl->vars->{$this->varName}))
			throw new TemplateCompileException("Variable '" . $this->varName . "' not set.");

		$tpl->vars->{$this->varName}->output($tpl);
	}
}
