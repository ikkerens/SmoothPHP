<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Element.php
 */

namespace SmoothPHP\Framework\Templates\Elements;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;

abstract class Element {

	/**
	 * Creates a new optimized version of  this Element, potentially returning a different type.
	 * By contract, this method is not allowed to modify its own instance.
	 * @param CompilerState $tpl The current compiler state.
	 * @return Element The optimized element.
	 */
	abstract function optimize(CompilerState $tpl);

	/**
	 * Outputs the content of this element using "echo" or other output methods.
	 * @param CompilerState $tpl
	 */
	abstract function output(CompilerState $tpl);

}
