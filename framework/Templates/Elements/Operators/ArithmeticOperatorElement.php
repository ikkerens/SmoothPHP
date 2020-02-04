<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ArithmeticOperatorElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Operators;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Commands\AssignElement;
use SmoothPHP\Framework\Templates\Elements\Commands\VariableElement;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

abstract class ArithmeticOperatorElement extends Element {
	/**
	 * @var Element
	 */
	protected $left, $right;

	protected function __construct(Element $left = null, Element $right = null) {
		$this->left = $left;
		$this->right = $right;
	}

	// Priorities will be taken from: https://en.wikipedia.org/wiki/Order_of_operations#Programming_languages
	protected abstract function getPriority();

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain) {
		switch ($command->next()) {
			case '+':
				$op = new AdditionOperatorElement();
				break;
			case '-':
				$command->skipWhitespace();
				if ($command->peek('>')) {
					$chain->addElement(new DereferenceOperatorElement($chain->pop()));
					return;
				} else
					$op = new SubstractionOperatorElement();
				break;
			case '*':
				$op = new MultiplicationOperatorElement();
				break;
			case '/':
				$op = new DivisionOperatorElement();
				break;
			case '&':
				if ($command->peek('&'))
					$op = new AndOperatorElement();
				else
					$op = new BinaryAndOperatorElement();
				break;
			case '|':
				if ($command->peek('|')) {
					$op = new OrOperatorElement();
					break;
				} else {
					$args = new Chain();
					$args->addElement($chain->pop());
					$chain->addElement(new FunctionOperatorElement($command->readAlphaNumeric(), $args));
					return;
				}
			case '=':
				if ($command->peek('=')) {
					$op = new EqualsOperatorElement();
					break;
				} else {
					$assignTo = $chain->pop();
					if (!($assignTo instanceof VariableElement))
						throw new TemplateCompileException("Attempting to assign a value to a non-variable around " . $command->getDebugSurroundings('') . ".");

					$right = new Chain();
					$compiler->handleCommand($command, $lexer, $right);
					$chain->addElement(new AssignElement($assignTo->getVarName(), TemplateCompiler::flatten($right)));
					return;
				}
			case '!':
				if ($command->peek('=')) {
					$op = new InEqualsOperatorElement();
					break;
				} else {
					$right = new Chain();
					$compiler->handleCommand($command, $lexer, $right);
					$chain->addElement(new InverseOperatorElement(TemplateCompiler::flatten($right)));
					return;
				}
			case '<':
				$op = new LessThanOperatorElement($command->peek('='));
				break;
			case '>':
				$op = new GreaterThanOperatorElement($command->peek('='));
				break;
		}
		$command->skipWhitespace();

		$right = new Chain();
		$compiler->handleCommand($command, $lexer, $right, ')');
		$left = $chain->pop();
		if (!($left instanceof Element))
			throw new TemplateCompileException('Could not determine left-hand-side of operator in "' . $command->getRawContent() . '"');
		$chain->addElement(ArithmeticOperatorElement::determineOrder($left, TemplateCompiler::flatten($right), $op));
	}

	public static function determineOrder(Element $previous, Element $next, ArithmeticOperatorElement $op) {
		if ($previous instanceof ArithmeticOperatorElement && $previous->getPriority() <= $op->getPriority()) {
			$left = $previous->left;
			$previous->left = $op;
			$op->left = $left;
			$op->right = $next;
			return $previous;
		} else if ($next instanceof ArithmeticOperatorElement && $next->getPriority() < $op->getPriority()) {
			$left = $next->left;
			$next->left = $op;
			$op->left = $previous;
			$op->right = $left;
			return $next;
		} else {
			$op->left = $previous;
			$op->right = $next;
			return $op;
		}
	}

	public function output(CompilerState $tpl) {
		$result = $this->optimize($tpl);

		if (!($result instanceof PrimitiveElement))
			throw new TemplateCompileException("Could not arithmetic values at runtime.");

		$result->output($tpl);
	}

}
