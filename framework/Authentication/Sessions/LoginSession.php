<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * LoginSession.php
 */

namespace SmoothPHP\Framework\Authentication\Sessions;

use SmoothPHP\Framework\Database\Mapper\MappedDBObject;
use SmoothPHP\Framework\Flow\Requests\Request;

class LoginSession extends MappedDBObject {

	private $ip;
	private $token;
	private $lastUpdate;
	private $failedAttempts;

	public function __construct(Request $request) {
		$this->ip = $request->getIP();
		$this->token = base64_encode(random_bytes(128));
		$this->lastUpdate = time();
		$this->failedAttempts = 0;
	}

	public function getTableName() {
		return 'loginsessions';
	}

	public function getToken() {
		return $this->token;
	}

	public function getLastUpdate() {
		return $this->lastUpdate;
	}

	public function getFailedAttempts() {
		return $this->failedAttempts;
	}

	public function increaseFailure() {
		$this->lastUpdate = time();
		$this->failedAttempts++;
	}

}