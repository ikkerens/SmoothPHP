<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * LongLivedSession.php
 */

namespace SmoothPHP\Framework\Authentication\Sessions;

use SmoothPHP\Framework\Authentication\UserTypes\User;
use SmoothPHP\Framework\Database\Mapper\DBObjectMapper;
use SmoothPHP\Framework\Database\Mapper\MappedDBObject;

class LongLivedSession extends MappedDBObject {

	const SESSION_KEY = 'sm_lses';

	private $userId;
	private $activeSessionId;
	private $selector;
	private $validator;

	public function __construct(User $user, ActiveSession $session) {
		$this->userId = $user->getId();
		$this->activeSessionId = $session->getId();
		$this->regenerateSecrets();
	}

	public function getTableName() {
		return 'longlivedsessions';
	}

	public function getUserId() {
		return $this->userId;
	}

	public function getActiveSessionId() {
		return $this->activeSessionId;
	}

	public function updateSession(ActiveSession $session) {
		$this->activeSessionId = $session->getId();
		$this->regenerateSecrets();
	}

	private function regenerateSecrets() {
		$this->selector = bin2hex(random_bytes(16));
		$validator = random_bytes(72);
		$this->validator = password_hash($validator, PASSWORD_DEFAULT);

		global $kernel;
		setcookie(self::SESSION_KEY, sprintf('%s:%s', $this->selector, base64_encode($validator)),
			time() + $kernel->getConfig()->authentication_longlived_age,
			'/', // This cookie applies to all sub-paths
			cookie_domain(), // Apply it to this host and all its subdomains
			false, // This cookie does not require HTTPS
			false); // This cookie can be transferred over non-HTTP
	}

	public static function readCookie(DBObjectMapper $map) {
		if (isset($_COOKIE[self::SESSION_KEY])) {
			$cookie = explode(':', $_COOKIE[self::SESSION_KEY]);

			if (count($cookie) != 2)
				return null;

			/* @var $session LongLivedSession */
			$session = $map->fetch([
				'selector' => $cookie[0]
			]);

			if (!$session)
				return null;

			if (!password_verify(base64_decode($cookie[1]), $session->validator))
				return null;

			return $session;
		}

		return null;
	}

}