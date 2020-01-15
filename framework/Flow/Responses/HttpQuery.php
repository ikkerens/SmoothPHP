<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * HttpQuery.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

class HttpQuery extends Response implements AlternateErrorResponse {
	private $built;

	public function buildErrorResponse($message) {
		$this->controllerResponse = [
			'error' => $message
		];
	}

	public function build(Kernel $kernel, Request $request) {
		$this->built = http_build_query($this->controllerResponse);
	}

	protected function sendHeaders() {
		parent::sendHeaders();
		header('Content-Type: application/x-www-form-urlencoded; charset=utf-8');
	}

	protected function sendBody() {
		echo $this->built;
	}

}