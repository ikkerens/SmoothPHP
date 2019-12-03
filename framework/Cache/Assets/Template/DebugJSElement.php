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

use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Elements\Element;

class DebugJSElement extends Element {

	public function optimize(CompilerState $tpl) {
		return $this;
	}

	public function output(CompilerState $tpl) {
		global $kernel;
		/* @var $assetsRegister AssetsRegister */
		$assetsRegister = $tpl->vars->assets->getValue();
		$routes = $kernel->getRouteDatabase();

		// Check if we have to provide any language keys
		$langKeys = $assetsRegister->getJSLanguageKeys();
		if (count($langKeys) > 0) {
			$language = [];
			$languageRepo = $kernel->getLanguageRepository();
			foreach ($langKeys as $key)
				$language[$key] = $languageRepo->getEntry($key);
			echo sprintf(JSElement::LANG_FORMAT, json_encode($language, JSON_FORCE_OBJECT));
		}

		// And then include the JS files
		foreach (array_unique($assetsRegister->getJSFiles()) as $js) {
			if (mb_strtolower(substr($js, 0, 4)) != 'http')
				$js = call_user_func_array([$routes, 'buildPath'], array_merge(['assets_js'], explode('/', $js)));
			echo sprintf(JSElement::FORMAT, $js);
		}
	}

}