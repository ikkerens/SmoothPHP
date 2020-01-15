<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ForeachElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class ForeachElement extends Element {
	private $collection;
	private $keyName;
	private $valueName;
	private $body;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd = null) {
		$collection = new Chain();
		$compiler->handleCommand($command, $lexer, $collection, 'as');
		$command->skipWhitespace();
		$command->peek('$');
		$keyName = null;
		$valueName = $command->readAlphaNumeric();
		$command->skipWhitespace();
		if ($command->peekSingle() !== false) {
			$keyName = $valueName;
			$command->peek('$');
			$valueName = $command->readAlphaNumeric();
		}

		$body = new Chain();
		$compiler->read($lexer, $body, TemplateCompiler::DELIMITER_START . '/foreach' . TemplateCompiler::DELIMITER_END);
		$chain->addElement(new self(TemplateCompiler::flatten($collection), $keyName, $valueName, TemplateCompiler::flatten($body)));
	}

	public function __construct(Element $collection, $keyName, $valueName, Element $body) {
		$this->collection = $collection;
		$this->keyName = $keyName;
		$this->valueName = $valueName;
		$this->body = $body;
	}

	public function optimize(CompilerState $tpl) {
		$collection = $this->collection->optimize($tpl);
		$tpl->pushUncertainty();
		$body = $this->body->optimize($tpl);
		$tpl->popUncertainty();

		if ($collection instanceof PrimitiveElement)
			return self::runLoop($tpl, $collection, $body, $this->keyName, $this->valueName);

		return new self($collection, $this->keyName, $this->valueName, $body);
	}

	public function output(CompilerState $tpl) {
		$collection = $this->collection->optimize($tpl);
		$body = $this->body->optimize($tpl);

		if (!($collection instanceof PrimitiveElement))
			throw new TemplateCompileException("Could not foreach-loop over element.");

		self::runLoop($tpl, $collection, $body, $this->keyName, $this->valueName)->output($tpl);
	}

	private static function runLoop(CompilerState $tpl, PrimitiveElement $collection, Element $body, $keyName, $valueName) {
		$result = new Chain();

		$rawCollection = $collection->getValue();
		if (method_exists($rawCollection, '__iterate'))
			$rawCollection = $rawCollection->__iterate();

		foreach ($rawCollection as $key => $value) {
			$scope = $tpl->createSubScope();
			if ($keyName != null)
				$scope->vars->{$keyName} = new PrimitiveElement($key);
			$scope->vars->{$valueName} = new PrimitiveElement($value);

			$result->addElement($body->optimize($scope));
		}

		return $result->optimize($tpl);
	}
}