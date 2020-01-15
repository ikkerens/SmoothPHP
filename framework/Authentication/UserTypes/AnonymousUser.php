<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AnonymousUser.php
 */

namespace SmoothPHP\Framework\Authentication\UserTypes;

class AnonymousUser implements AbstractUser {

	public function isLoggedIn() {
		return false;
	}

}