<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * HTMLResponse.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

class HTMLResponse extends Response {

	public function build(Kernel $kernel, Request $request) {
	}

	protected function sendHeaders() {
		parent::sendHeaders();
		header('Content-Type: text/html; charset=utf-8');
	}

	protected function sendBody() {
		echo $this->controllerResponse;
	}

}
