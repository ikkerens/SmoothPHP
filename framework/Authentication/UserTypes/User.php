<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * User.php
 */

namespace SmoothPHP\Framework\Authentication\UserTypes;

use SmoothPHP\Framework\Database\Mapper\MappedDBObject;

class User extends MappedDBObject implements AbstractUser {

	public $email;
	public $password;

	public function getTableName() {
		return 'users';
	}

	public function isLoggedIn() {
		return true;
	}

	public function setPassword($input) {
		$this->password = password_hash($input, PASSWORD_DEFAULT);
	}

	public static function getInstance() {
		global $kernel;
		return $kernel->getAuthenticationManager()->getActiveUser();
	}

}