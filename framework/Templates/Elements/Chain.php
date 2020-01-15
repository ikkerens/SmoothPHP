<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Chain.php
 */

namespace SmoothPHP\Framework\Templates\Elements;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;

class Chain extends Element {
	private $chain;

	public function __construct(array $chain = []) {
		$this->chain = $chain;
	}

	public function addElement(Element $element) {
		$this->chain[] = $element;
	}

	public function previous($count = 1) {
		$index = count($this->chain) - $count;
		if (isset($this->chain[$index]))
			return $this->chain[$index];
		else
			return null;
	}

	public function pop() {
		return array_pop($this->chain);
	}

	public function getAll() {
		return $this->chain;
	}

	public function optimize(CompilerState $tpl) {
		$chain = [];
		$str = '';

		foreach ($this->chain as $piece) {
			$piece = $piece->optimize($tpl);
			if ($piece instanceof PrimitiveElement) {
				if ($piece->getValue() === false)
					$str .= '0';
				else
					$str .= $piece->getValue();
			} else {
				if (strlen(trim($str)) > 0) {
					$chain[] = new PrimitiveElement($str);
					$str = '';
				}
				$chain[] = $piece;
			}
		}

		if (strlen(trim($str)) > 0)
			$chain[] = new PrimitiveElement($str);

		$count = count($chain);
		if ($count == 0)
			return new PrimitiveElement();
		else if ($count == 1)
			return current($chain);
		else {
			return new self($chain);
		}
	}

	public function output(CompilerState $tpl) {
		array_map(function (Element $piece) use ($tpl) {
			$piece->output($tpl);
		}, $this->chain);
	}
}
