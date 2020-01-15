<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * IfElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class IfElement extends Element {
	private $condition;
	/* @var Element */
	private $trueBody, $falseBody;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd = null) {
		$condition = new Chain();
		$compiler->handleCommand($command, $lexer, $condition, $stackEnd);
		$fullIf = new Chain();
		$compiler->read($lexer, $fullIf, TemplateCompiler::DELIMITER_START . '/if' . TemplateCompiler::DELIMITER_END);

		$i = 0;
		$elements = $fullIf->getAll();

		$else = false;
		$trueBody = new Chain();
		$falseBody = new Chain();
		for (; $i < count($elements); $i++) {
			if ($elements[$i] instanceof ElseElement) {
				if ($else)
					throw new TemplateCompileException('Multiple else\'s in 1 if-statement.');
				$else = true;
				continue;
			}

			if (!$else)
				$trueBody->addElement($elements[$i]);
			else
				$falseBody->addElement($elements[$i]);
		}

		$chain->addElement(new self(TemplateCompiler::flatten($condition), TemplateCompiler::flatten($trueBody), $else ? TemplateCompiler::flatten($falseBody) : null));
	}

	public function __construct(Element $condition, Element $true, $false) {
		$this->condition = $condition;
		$this->trueBody = $true;
		$this->falseBody = $false;
	}

	public function optimize(CompilerState $tpl) {
		$condition = $this->condition->optimize($tpl);

		if ($condition instanceof PrimitiveElement) {
			if ($condition->getValue())
				return $this->trueBody->optimize($tpl);
			else if (isset($this->falseBody))
				return $this->falseBody->optimize($tpl);
			else
				return new PrimitiveElement();
		} else
			return new self($condition, $this->trueBody ? $this->trueBody->optimize($tpl) : null, $this->falseBody ? $this->falseBody->optimize($tpl) : null);
	}

	public function output(CompilerState $tpl) {
		$result = $this->condition->optimize($tpl);

		if ($result instanceof PrimitiveElement)
			$primitiveResult = $result->getValue();
		else
			throw new TemplateCompileException("Could not deduce if condition at runtime.");

		if ($primitiveResult)
			$this->trueBody->output($tpl);
		else if (isset($this->falseBody))
			$this->falseBody->output($tpl);
	}
}
