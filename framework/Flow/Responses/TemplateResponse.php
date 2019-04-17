<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * TemplateResponse.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

class TemplateResponse extends Response {
	private $built;
	private $args;
	private $gzip;

	public function __construct($controllerResponse, array $args = []) {
		parent::__construct($controllerResponse);
		$this->args = $args;
	}

	public function build(Kernel $kernel, Request $request) {
		$this->args['assets'] = $kernel->getAssetsRegister();
		$this->args['route'] = $kernel->getRouteDatabase();
		$this->args['language'] = $kernel->getLanguageRepository();
		if ($kernel->getConfig()->authentication_enabled) {
			$auth = $kernel->getAuthenticationManager();
			$this->args['auth'] = $auth;
			$this->args['user'] = $auth->getActiveUser();
		}
		$this->args['request'] = $request;
		$this->built = $kernel->getTemplateEngine()->fetch($this->controllerResponse, $this->args);

		$this->gzip = __ENV__ != 'dev' && strpos($request->server->HTTP_ACCEPT_ENCODING, 'gzip') !== false;
		if ($this->gzip)
			$this->built = gzencode($this->built, 9);
	}

	protected function sendHeaders() {
		parent::sendHeaders();
		header('Content-Type: text/html; charset=utf-8');

		if ($this->gzip)
			header('Content-Encoding: gzip');
	}

	protected function sendBody() {
		echo $this->built;
	}

}
