<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Controller.php
 */

namespace SmoothPHP\Framework\Core\Abstracts;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Responses\RedirectResponse;
use SmoothPHP\Framework\Flow\Responses\TemplateResponse;

abstract class Controller {

	public function onInitialize(Kernel $kernel) {
	}

	protected static function render($template, array $templateArgs = []) {
		return new TemplateResponse($template, $templateArgs);
	}

	protected static function redirect() {
		$route = func_get_arg(0);
		$args = func_get_args();
		$args = array_splice($args, 1);
		return new RedirectResponse($route, $args);
	}

}