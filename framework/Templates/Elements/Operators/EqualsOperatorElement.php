<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * EqualsOperatorElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Operators;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;

class EqualsOperatorElement extends ArithmeticOperatorElement {

	public function getPriority() {
		return 6;
	}

	public function optimize(CompilerState $tpl) {
		$left = $this->left->optimize($tpl);
		$right = $this->right->optimize($tpl);

		if ($left instanceof PrimitiveElement && $right instanceof PrimitiveElement)
			return new PrimitiveElement($left->getValue() == $right->getValue());
		else
			return new self($left, $right);
	}

	public function output(CompilerState $tpl) {
		$result = $this->optimize($tpl);

		if (!($result instanceof PrimitiveElement))
			throw new TemplateCompileException("Could not determine values at runtime.");

		$result->output($tpl);
	}
}
