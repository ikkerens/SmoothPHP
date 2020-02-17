<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Response.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

abstract class Response {
	protected $controllerResponse;

	public function __construct($controllerResponse) {
		$this->controllerResponse = $controllerResponse;
	}

	public abstract function build(Kernel $kernel, Request $request);

	protected function sendHeaders() {
		global $kernel;
		foreach ($kernel->getConfig()->project_headers as $header)
			header($header);
	}

	protected abstract function sendBody();

	public function send() {
		header('X-Powered-By: SmoothPHP');
		$this->sendHeaders();
		$this->sendBody();
	}

}
