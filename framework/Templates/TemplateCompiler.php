<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * TemplateCompiler.php
 */

namespace SmoothPHP\Framework\Templates;

use SmoothPHP\Framework\Cache\Assets\Template as Assets;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Commands\BlockElement;
use SmoothPHP\Framework\Templates\Elements\Operators\ArithmeticOperatorElement;
use SmoothPHP\Framework\Templates\Elements\Operators\DereferenceOperatorElement;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;

class TemplateCompiler {
	const DELIMITER_START = '{';
	const DELIMITER_END = '}';

	private $commands, $operators;

	public function __construct() {
		// All these commands and operators will have the following method called:
		// static handle(TemplateCompiler, TemplateLexer $command, TemplateLexer $lexer, Chain, $stackEnd);
		$this->commands = [
			'ignore' => Elements\Commands\IgnoreElement::class,

			'include' => Elements\Commands\IncludeElement::class,
			'assign'  => Elements\Commands\AssignElement::class,
			'block'   => BlockElement::class,
			'if'      => Elements\Commands\IfElement::class,
			'else'    => Elements\Commands\ElseElement::class,
			'elseif'  => Elements\Commands\ElseIfElement::class,
			'while'   => Elements\Commands\WhileElement::class,
			'foreach' => Elements\Commands\ForeachElement::class,

			'javascript' => Assets\JSElement::class,
			'css'        => Assets\CSSElement::class
		];
		$this->operators = [
			'+'  => ArithmeticOperatorElement::class,
			'-'  => ArithmeticOperatorElement::class,
			'*'  => ArithmeticOperatorElement::class,
			'/'  => ArithmeticOperatorElement::class,
			'='  => ArithmeticOperatorElement::class,
			'|'  => ArithmeticOperatorElement::class,
			'\'' => Elements\PrimitiveElement::class,
			'"'  => Elements\PrimitiveElement::class,
			'!'  => Elements\Operators\InEqualsOperatorElement::class,
			'&'  => Elements\Operators\BinaryAndOperatorElement::class,
			':'  => Elements\Operators\DereferenceOperatorElement::class,
			'<'  => ArithmeticOperatorElement::class,
			'>'  => ArithmeticOperatorElement::class,
		];
	}

	public function compile($file) {
		return $this->compileSource(file_get_contents($file));
	}

	public function compileSource($source) {
		$lexer = new TemplateLexer($source);

		$chain = new Chain();
		$this->read($lexer, $chain);

		$tpl = new Compiler\CompilerState();
		$chain = $chain->optimize($tpl);
		$tpl->finishing = true;
		$chain = $chain->optimize($tpl);

		return $chain;
	}

	public function read(TemplateLexer $lexer, Chain $chain, $stackEnd = null) {
		$rawString = '';

		$finishString = function () use (&$chain, &$rawString) {
			if (strlen(trim($rawString)) > 0)
				$chain->addElement(new Elements\PrimitiveElement($rawString));
			$rawString = '';
		};

		while (true) {
			if ($stackEnd !== null && $lexer->peek($stackEnd)) {
				$finishString();
				return;
			} else if ($lexer->peek(self::DELIMITER_START)) {
				if ($lexer->peek(' $', false))
					$lexer->skipWhitespace();
				else if ($lexer->isWhitespace()) {
					$rawString .= self::DELIMITER_START;
					continue;
				}

				$finishString();

				if ($lexer->peek('*')) {
					$comment = $lexer->readRaw('*' . self::DELIMITER_END);
					if (__ENV__ == 'dev')
						$chain->addElement(new PrimitiveElement(sprintf('<!-- %s -->', trim($comment))));
					continue;
				}

				$command = '';
				while (!$lexer->peek(self::DELIMITER_END)) {
					$char = $lexer->next();
					if ($char !== false)
						$command .= $char;
					else
						throw new TemplateCompileException("Template syntax error, unfinished command: '" . self::DELIMITER_START . $command . "'' around '" . $lexer->getDebugSurroundings(self::DELIMITER_START . $command) . "'.");
				}

				$finishString();

				$this->handleCommand(new TemplateLexer($command), $lexer, $chain);
			} else {
				$char = $lexer->next();
				if ($char !== false)
					$rawString .= $char;
				else {
					$finishString();
					return;
				}
			}
		}
	}

	public function handleCommand(TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd = null) {
		$command->skipWhitespace();

		if ($stackEnd != null && $command->peek($stackEnd)) {
			return true;
		} else if ($command->peek('(')) {
			$elements = new Chain();
			$this->handleCommand($command, $lexer, $elements, ')');
			$chain->addElement(new Elements\ParenthesisElement(self::flatten($elements)));
		} else if ($command->peek('$')) {
			$chain->addElement(new Elements\Commands\VariableElement($command->readAlphaNumeric()));
		} else {
			$next = $command->readAlphaNumeric();

			if (isset($this->commands[$next]))
				call_user_func([$this->commands[$next], 'handle'], $this, $command, $lexer, $chain, $stackEnd);
			else if (strlen(trim($next)) > 0) {
				$chain->addElement(new Elements\PrimitiveElement($next, true));

				if ($command->peek('(')) {
					// Function call
					$name = $chain->pop();
					if (!($name instanceof Elements\PrimitiveElement))
						throw new TemplateCompileException("Attempting to call a function without a name around " . $command->getDebugSurroundings('(') . ".");

					$deref = false;
					if ($chain->previous() instanceof DereferenceOperatorElement)
						$deref = $chain->previous();

					$args = new Chain();
					do {
						if ($this->handleCommand($command, $lexer, $args, ')'))
							break;
					} while ($command->skipWhitespace() || $command->peek(','));

					$function = new Elements\Operators\FunctionOperatorElement($name->getValue(), $args);
					if ($deref !== false)
						$deref->setRight($function);
					else
						$chain->addElement($function);
				} else if ($chain->previous(2) instanceof DereferenceOperatorElement) {
					$chain->previous(2)->setRight($chain->pop());
				}
			} else {
				$command->skipWhitespace();
				if (isset($this->operators[$command->peekSingle()]))
					call_user_func([$this->operators[$command->peekSingle()], 'handle'], $this, $command, $lexer, $chain, $stackEnd);
				else if (strlen($command->peekSingle()) > 0)
					throw new TemplateCompileException("Unknown operator '" . $command->peekSingle() . "' as found in '" . $command->getDebugSurroundings($command->peekSingle()) . "'");
			}
		}

		$command->skipWhitespace();
		if ($command->peekSingle() && $command->peekSingle() != ',') // Do we have more to read?
			return $this->handleCommand($command, $lexer, $chain, $stackEnd);

		if ($stackEnd != null)
			return $command->peek($stackEnd);
		else
			return false;
	}

	public static function flatten($chain) {
		if ($chain instanceof Chain) {
			$all = $chain->getAll();
			if (count($all) == 1)
				return self::flatten(current($all));
		}

		return $chain;
	}

}
