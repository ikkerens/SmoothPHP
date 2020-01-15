<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Website.php
 */

use SmoothPHP\Framework\Core\Abstracts\WebPrototype;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Responses\HTMLResponse;
use SmoothPHP\Framework\Flow\Routing\RouteDatabase;
use Test\Controllers as Controllers;
use Test\Model\Response\AvatarResponse;
use Test\Model\TestUser;

class Website extends WebPrototype {

	public function initialize(Kernel $kernel) {
		// getConfig() will return a reference to the used config instance, any changes will be reflected back
		$config = $kernel->getConfig();

		$config->db_enabled = true;
		$config->db_database = 'test';
		$config->db_user = 'root';
		$config->db_password = 'root';

		$config->authentication_enabled = true;
		$config->authentication_loginroute = 'front_login';
		$kernel->getAuthenticationManager()->setUserClass(TestUser::class);

		// The function reference has to be serializable, hence the array notation
		// This function will be called by Kernel::error, the frameworks internal error handler
		// The second argument indicates whether the route content-type should be overridden,
		// otherwise the route's content-type *can* override the error output format.
		// A good example of this would be JSON.
		// By default, the error handler will use a PlainTextResponse
		$kernel->setErrorHandler([Website::class, 'customError'], false);
	}

	public static function customError($message) {
		return new HTMLResponse('<span style="color: blue">' . htmlentities($message) . '</span>');
	}

	public function registerRoutes(RouteDatabase $routes) {
		$routes->register([
			'name'       => 'front_login',
			'path'       => '/',
			'controller' => Controllers\TestController::class,
			'call'       => 'login',
			'method'     => ['GET', 'POST']
		]);

		$routes->register([
			'name'       => 'register',
			'path'       => '/register',
			'controller' => Controllers\RegisterController::class,
			'call'       => 'register',
			'method'     => ['GET', 'POST']
		]);

		$routes->register([
			'name'           => 'secure',
			'path'           => '/secure',
			'controller'     => Controllers\TestController::class,
			'call'           => 'secure',
			'authentication' => true
		]);

		$routes->register([
			'name'           => 'avatar',
			'path'           => '/avatar.png',
			'controller'     => Controllers\TestController::class,
			'call'           => 'avatar',
			'authentication' => true,
			'content-type'   => AvatarResponse::class
		]);

		$routes->register([
			'name'           => 'logout',
			'path'           => '/logout',
			'controller'     => Controllers\TestController::class,
			'call'           => 'logout',
			'authentication' => true
		]);
	}

}
