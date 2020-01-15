<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ParenthesisElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;

class ParenthesisElement extends Element {
	private $element;

	public function __construct(Element $element) {
		$this->element = $element;
	}

	public function optimize(CompilerState $tpl) {
		return $this->element->optimize($tpl);
	}

	public function output(CompilerState $tpl) {
		$this->element->output($tpl);
	}
}
