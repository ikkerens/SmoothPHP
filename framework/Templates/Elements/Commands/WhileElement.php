<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * WhileElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class WhileElement extends Element {
	private $condition;
	private $body;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd = null) {
		$condition = new Chain();
		$compiler->handleCommand($command, $lexer, $condition, $stackEnd);
		$body = new Chain();
		$compiler->read($lexer, $body, TemplateCompiler::DELIMITER_START . '/while' . TemplateCompiler::DELIMITER_END);
		$chain->addElement(new self(TemplateCompiler::flatten($condition), TemplateCompiler::flatten($body)));
	}

	public function __construct(Element $condition, Element $body) {
		$this->condition = $condition;
		$this->body = $body;
	}

	public function optimize(CompilerState $tpl) {
		$tpl->pushUncertainty();
		$condition = $this->condition->optimize($tpl);
		$body = $this->body->optimize($tpl);
		$tpl->popUncertainty();

		return new self($condition, $body);
	}

	public function output(CompilerState $tpl) {
		while (true) {
			$result = $this->condition->optimize($tpl);

			if (!($result instanceof PrimitiveElement))
				throw new TemplateCompileException("Could not deduce if condition at runtime.");

			if (!$result->getValue())
				break;

			$this->body->output($tpl);
		}
	}
}
