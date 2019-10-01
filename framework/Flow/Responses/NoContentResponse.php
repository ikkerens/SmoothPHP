<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * NoContentResponse.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

class NoContentResponse extends Response {

	public function __construct() {
		parent::__construct(null);
	}

	public function build(Kernel $kernel, Request $request) {
	}

	protected function sendHeaders() {
		parent::sendHeaders();
		http_response_code(204);
	}

	protected function sendBody() {
	}

}