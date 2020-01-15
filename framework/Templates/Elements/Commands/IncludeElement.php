<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * IncludeElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Commands;

use SmoothPHP\Framework\Cache\Builder\CacheExpiredException;
use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class IncludeElement extends Element {
	private $file;
	private $md5;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain) {
		$args = new Chain();
		$compiler->handleCommand($command, $lexer, $args);
		$args = $args->getAll();

		if (!($args[0] instanceof PrimitiveElement))
			throw new TemplateCompileException("Include file path could not be resolved at parse time.");

		$path = sprintf('%ssrc/templates/%s', __ROOT__, $args[0]->getValue());

		$include = new Chain();
		$compiler->read(new TemplateLexer(file_get_contents($path)), $include);

		$chain->addElement(new self($path));
		$chain->addElement($include);
	}

	public function __construct($file) {
		$this->file = $file;
		$this->md5 = file_hash($file);
	}

	public function __wakeup() {
		if (file_hash($this->file) != $this->md5)
			throw new CacheExpiredException();
	}

	public function optimize(CompilerState $tpl) {
		return $this;
	}

	public function output(CompilerState $tpl) {
		return '';
	}

}