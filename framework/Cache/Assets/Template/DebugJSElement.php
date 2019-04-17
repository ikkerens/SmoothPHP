<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * DebugJSElement.php
 */

namespace SmoothPHP\Framework\Cache\Assets\Template;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Elements\Element;

class DebugJSElement extends Element {

	public function optimize(CompilerState $tpl) {
		return $this;
	}

	public function output(CompilerState $tpl) {
		global $kernel;
		/* @var $assetsRegister \SmoothPHP\Framework\Cache\Assets\AssetsRegister */
		$assetsRegister = $tpl->vars->assets->getValue();
		$routes = $kernel->getRouteDatabase();

		foreach (array_unique($assetsRegister->getJSFiles()) as $js) {
			if (strtolower(substr($js, 0, 4)) != 'http')
				$js = call_user_func_array([$routes, 'buildPath'], array_merge(['assets_js'], explode('/', $js)));
			echo sprintf(JSElement::FORMAT, $js);
		}
	}

}