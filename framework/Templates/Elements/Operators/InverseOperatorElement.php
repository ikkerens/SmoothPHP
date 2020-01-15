<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * InverseOperatorElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Operators;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;

class InverseOperatorElement extends Element {
	private $body;

	public function __construct(Element $body) {
		$this->body = $body;
	}

	public function optimize(CompilerState $tpl) {
		$body = $this->body->optimize($tpl);

		if ($body instanceof PrimitiveElement)
			return new PrimitiveElement(!$body->getValue());

		return new self($body);
	}

	public function output(CompilerState $tpl) {
		$result = $this->optimize($tpl);

		if (!($result instanceof PrimitiveElement))
			throw new TemplateCompileException("Could not determine inverse value at runtime.");

		$result->output($tpl);
	}

}