<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FunctionOperatorElement.php
 */

namespace SmoothPHP\Framework\Templates\Elements\Operators;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Commands\VariableElement;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;

class FunctionOperatorElement extends Element {
	private static $cacheableFunctions;

	private $function;
	private $args;

	public function __construct($function, Chain $args) {
		if (!isset(self::$cacheableFunctions))
			self::fillCacheableFunctions();

		if ($function == 'isset') {
			$arrArgs = $args->getAll();
			if (count($arrArgs) != 1 || !($arrArgs[0] instanceof VariableElement))
				throw new TemplateCompileException('isset() only accepts 1 variable as argument');
			else {
				$args->pop();
				$args->addElement(new PrimitiveElement($arrArgs[0]->getVarName()));
				$this->function = [FunctionOperatorElement::class, '__builtin_isset'];
				$this->args = $args;
				return;
			}
		} // else

		$this->function = $function;
		$this->args = $args;
	}

	public function __wakeup() {
		if (!isset(self::$cacheableFunctions))
			self::fillCacheableFunctions();
	}

	public function getFunctionName() {
		return $this->function;
	}

	public function getPrimitiveArgs(CompilerState $tpl) {
		$args = [];

		foreach ($this->args->getAll() as $arg) {
			$arg = $arg->optimize($tpl);

			if (!($arg instanceof PrimitiveElement))
				throw new TemplateCompileException("Could not deduce function argument at runtime.");

			$args[] = $arg->getValue();
		}

		return $args;
	}

	public function optimize(CompilerState $tpl) {
		$simpleArgs = true;
		$args = $this->args->getAll();
		$optimizedChain = new Chain();
		$resolvedArgs = [];

		for ($i = 0; $i < count($args); $i++) {
			$args[$i] = $args[$i]->optimize($tpl);
			$optimizedChain->addElement($args[$i]);

			if (!($args[$i] instanceof PrimitiveElement))
				$simpleArgs = false;
			else
				$resolvedArgs[] = $args[$i]->getValue();
		}

		if (($tpl->performCalls || in_array($this->function, self::$cacheableFunctions)) && $simpleArgs) {
			if (is_array($this->function)) {
				if ($tpl->isUncertain())
					return new self($this->function, $optimizedChain);
				array_unshift($resolvedArgs, $tpl); // If we're dealing with a builtin function, push compilerstate
			}
			return new PrimitiveElement(call_user_func_array($this->function, $resolvedArgs));
		} else
			return new self($this->function, $optimizedChain);
	}

	public function output(CompilerState $tpl) {
		echo call_user_func_array($this->function, $this->getPrimitiveArgs($tpl));
	}

	private static function fillCacheableFunctions() {
		self::$cacheableFunctions = [
			/* Math functions */
			'abs', 'acos', 'acosh', 'asin', 'asinh', 'atan2', 'atan', 'atanh', 'base_convert', 'ceil', 'cos', 'cosh', 'dechex', 'decoct', 'deg2rad', 'exp', 'expm1', 'floor', 'fmod', 'getrandmax', 'hexdec', 'hypot', 'intdiv', 'is_finite', 'is_infinite', 'is_nan', 'lcg_value', 'log10', 'log1p', 'log', 'max', 'min', 'mt_getrandmax', 'octdec', 'pi', 'pow', 'rad2deg', 'round', 'sin', 'sinh', 'sqrt', 'tan', 'tanh',
			/* String functions */
			'addcslashes', 'addslashes', 'chop', 'chr', 'chunk_split', 'convert_cyr_string', 'convert_uudecode', 'convert_uuencode', 'count_chars', 'crc32', 'explode', 'hebrev', 'hebrevc', 'html_entity_decode', 'htmlentities', 'htmlspecialchars_decode', 'htmlspecialchars', 'implode', 'join', 'lcfirst', 'levenshtein', 'localeconv', 'ltrim', 'md5', 'metaphone', 'money_format', 'nl2br', 'number_format', 'ord', 'parse_str', 'print', 'printf', 'quoted_printable_decode', 'quoted_printable_encode', 'quotemeta', 'rtrim', 'sha1', 'similar_text', 'soundex', 'sprintf', 'sscanf', 'str_getcsv', 'str_ireplacce', 'str_pad', 'str_repeat', 'str_replace', 'str_rot13', 'str_shuffle', 'str_word_count', 'strcasecmp', 'strchr', 'strcmp', 'strcoll', 'strcspn', 'strip_tags', 'stripcslashes', 'stripos', 'stripslashes', 'stristr', 'strlen', 'strnatcasecmp', 'strnatcmp', 'strncasecmp', 'strncmp', 'strpbrk', 'strpos', 'strrchr', 'strrev', 'strripos', 'strrpos', 'strspn', 'strstr', 'strtok', 'strtolower', 'strtoupper', 'strtr', 'substr_compare', 'substr_count', 'subtr_replace', 'substr', 'trim', 'ucfirst', 'ucwords', 'vsprintf', 'wordwrap', 'mb_strtolower',
			/* Variable functions */
			'boolval', 'doubleval', 'empty', 'floatval', 'gettype', 'intval', 'is_array', 'is_bool', 'is_callable', 'is_double', 'is_float', 'is_int', 'is_integer', 'is_long', 'is_null', 'is_numeric', 'is_object', 'is_real', 'is_resource', 'is_scalar', 'is_string', 'print_r', 'strval', 'var_dump',
			/* Uncategorized functions */
			'urlencode'
		];
	}

	private static function __builtin_isset(CompilerState $tpl, $varName) {
		return isset($tpl->vars->{$varName});
	}

}
