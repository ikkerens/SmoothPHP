<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * BlockElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class BlockElement extends Element {
	const USAGE_UNSPECIFIED = 0;
	const USAGE_PREPEND = 1;
	const USAGE_APPEND = 2;
	const USAGE_CLONE = 3;

	private $name;
	private $usage;
	private $body;
	private $definer;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain) {
		$args = new Chain();
		$compiler->handleCommand($command, $lexer, $args);
		$args = $args->getAll();

		$usage = self::USAGE_UNSPECIFIED;
		if (isset($args[1])) {
			$args[1] = $args[1]->optimize(new CompilerState());
			switch ($args[1]->getValue()) {
				case 'prepend':
					$usage = self::USAGE_PREPEND;
					break;
				case 'append':
					$usage = self::USAGE_APPEND;
					break;
				case 'clone':
					$chain->addElement(new self($args[0], self::USAGE_CLONE, new PrimitiveElement()));
					return;
			}
		}

		$body = new Chain();
		$compiler->read($lexer, $body, TemplateCompiler::DELIMITER_START . '/block' . TemplateCompiler::DELIMITER_END);
		$chain->addElement(new self($args[0], $usage, TemplateCompiler::flatten($body)));
	}

	public function __construct(Element $name, $usage, Element $body) {
		$this->name = $name;
		$this->usage = $usage;
		$this->body = $body;
		$this->definer = false;
	}

	public function optimize(CompilerState $tpl) {
		if ($tpl->performCalls) {
			$this->name = $this->name->optimize($tpl);

			if (!($this->name instanceof PrimitiveElement))
				throw new TemplateCompileException("Could not determine block name at compile-time.");
			$name = $this->name->getValue();

			$this->body = $this->body->optimize($tpl);
			if ($this->usage == self::USAGE_UNSPECIFIED) {
				if (isset($tpl->blocks[$name])) {
					$tpl->blocks[$name]->body = $this->body;
				} else {
					$tpl->blocks[$name] = $this;
					$this->definer = true;
				}
			} else {
				if (!isset($tpl->blocks[$name]))
					throw new TemplateCompileException("Attempting to prepend/append to an unknown block '" . $this->name->getValue() . "'.");

				$blockEl = $tpl->blocks[$name];

				if ($this->usage == self::USAGE_CLONE)
					return $blockEl;

				$chain = new Chain();
				if ($this->usage == self::USAGE_PREPEND) {
					$chain->addElement($this->body);
					$chain->addElement($blockEl->body);
				} else if ($this->usage == self::USAGE_APPEND) {
					$chain->addElement($blockEl->body);
					$chain->addElement($this->body);
				}
				$blockEl->body = $chain->optimize($tpl);

				return new PrimitiveElement();
			}
		}

		return $this;
	}

	public function output(CompilerState $tpl) {
		if ($this->definer)
			$this->body->output($tpl);
	}
}