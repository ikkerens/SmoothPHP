<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AssignElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class AssignElement extends Element {
	private $varName;
	private $value;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd) {
		$command->skipWhitespace();
		$command->peek('$');
		$varName = $command->readAlphaNumeric();

		$command->skipWhitespace();
		$value = new Chain();
		$compiler->handleCommand($command, $lexer, $value, $stackEnd);
		$chain->addElement(new self($varName, TemplateCompiler::flatten($value)));

	}

	public function __construct($varName, Element $value) {
		$this->varName = $varName;
		$this->value = $value;
	}

	public function optimize(CompilerState $tpl) {
		$optValue = $this->value->optimize($tpl);

		if ($optValue instanceof PrimitiveElement)
			$tpl->vars->{$this->varName} = $optValue;

		return new self($this->varName, $optValue);
	}

	public function output(CompilerState $tpl) {
		$optimized = $this->optimize($tpl);

		if (!($optimized->value instanceof PrimitiveElement))
			throw new TemplateCompileException("Value '" . $this->varName . "' could not be resolved.");
	}
}
