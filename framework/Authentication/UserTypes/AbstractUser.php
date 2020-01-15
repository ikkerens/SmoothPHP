<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AbstractUser.php
 */

namespace SmoothPHP\Framework\Authentication\UserTypes;

interface AbstractUser {

	public function isLoggedIn();

}