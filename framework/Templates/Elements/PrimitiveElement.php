<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * PrimitiveElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class PrimitiveElement extends Element {
	private $value;

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain) {
		$strStart = $command->next();
		$str = $command->readRaw($strStart, '\\' . $strStart);
		$chain->addElement(new self($str));
	}

	public function __construct($value = '', $tryParse = false) {
		if ($tryParse && is_string($value)) {
			if (is_numeric($value))
				$value = $value + 0;
			else {
				switch ($value) {
					case "true":
						$value = true;
						break;
					case "false":
						$value = false;
						break;
					case "null":
						$value = null;
						break;
				}
			}
		}
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

	public function optimize(CompilerState $tpl) {
		if ($tpl->allowMinify && is_string($this->value)) {
			$minified = $this->minify($this->value);
			if ($minified != $this->value)
				return new PrimitiveElement($minified);
		}
		return $this;
	}

	private function minify($buffer) {
		$search = [
			'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
			'/[^\S ]+\</s',     // strip whitespaces before tags, except space
			'/(\s)+/s',         // shorten multiple whitespace sequences
			'/<!--(.|\s)*?-->/' // Remove HTML comments
		];

		$replace = [
			'>',
			'<',
			'\\1',
			''
		];

		return preg_replace($search, $replace, $buffer);
	}

	public function output(CompilerState $tpl) {
		echo $this->value;
	}

}
