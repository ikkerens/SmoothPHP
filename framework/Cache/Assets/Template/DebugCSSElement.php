<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * DebugCSSElement.php
 */

namespace SmoothPHP\Framework\Cache\Assets\Template;

use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Elements\Element;

class DebugCSSElement extends Element {

	public function optimize(CompilerState $tpl) {
		return $this;
	}

	public function output(CompilerState $tpl) {
		global $kernel;
		/* @var $assetsRegister AssetsRegister */
		$assetsRegister = $tpl->vars->assets->getValue();
		foreach (array_unique($assetsRegister->getCSSFiles()) as $css) {
			if (strtolower(substr($css, 0, 4)) != 'http')
				$css = $kernel->getRouteDatabase()->buildPath('assets_css', $css);
			echo sprintf(CSSElement::FORMAT, $css);
		}
	}

}