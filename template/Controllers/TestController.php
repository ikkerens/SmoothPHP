<?php
/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * TestController.php
 */

namespace Test\Controllers;

use SmoothPHP\Framework\Authentication\AuthenticationManager;
use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Core\Abstracts\Controller;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;
use Test\Model\TestUser;

class TestController extends Controller {

	public function onInitialize(Kernel $kernel) {
		/*
		 * In this function you can pre-initialize your forms and SQL queries.
		 * In production mode, controllers will only be initialized once per server application restart,
		 * as controller instances are saved in the APC cache.
		 *
		 * Please note that controller variables have to be PHP-serializable.
		 */
	}

	public function login(AuthenticationManager $auth, Request $request) {
		if ($auth->checkLoginResult($request) || $auth->getActiveUser()->isLoggedIn())
			/*
			 * self::redirect is a method that returns a RedirectResponse and accepts two types of arguments
			 * redirect first checks if the first argument is a known route in Website.php, it then uses any additional arguments to fill in the route url
			 * if the route doesn't exist, it just simply redirects to whatever is given as the first argument
			 */
			return self::redirect('secure');

		// self::render returns a TemplateResponse
		return self::render('login.tpl', [
			'form' => $auth->getLoginForm($request)
		]);
	}

	public function secure() {
		// The route definition has already secured this method
		return self::render('secure.tpl');
	}

	public function avatar(AuthenticationManager $auth, AssetsRegister $assets) {
		/* @var TestUser */
		$user = $auth->getActiveUser();

		if (!$user->avatar)
			return self::redirect($assets->getImage('defaultavatar.png'));

		return $user->avatar;
	}

	public function logout(AuthenticationManager $auth) {
		$auth->logout();
		return self::redirect('front_login');
	}

}
