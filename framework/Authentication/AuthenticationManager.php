<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AuthenticationManager.php
 */

namespace SmoothPHP\Framework\Authentication;

use RuntimeException;
use SmoothPHP\Framework\Authentication\Sessions\ActiveSession;
use SmoothPHP\Framework\Authentication\Sessions\LoginSession;
use SmoothPHP\Framework\Authentication\Sessions\LongLivedSession;
use SmoothPHP\Framework\Authentication\UserTypes\AbstractUser;
use SmoothPHP\Framework\Authentication\UserTypes\AnonymousUser;
use SmoothPHP\Framework\Authentication\UserTypes\User;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Database\Mapper\DBObjectMapper;
use SmoothPHP\Framework\Database\Statements\SQLStatement;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Responses\RedirectResponse;
use SmoothPHP\Framework\Flow\Responses\Response;
use SmoothPHP\Framework\Forms\Constraints\MaximumLengthConstraint;
use SmoothPHP\Framework\Forms\Form;
use SmoothPHP\Framework\Forms\FormBuilder;
use SmoothPHP\Framework\Forms\Types as Types;

// A lot of security mechanics in this class were derived from StackOverflow
// http://stackoverflow.com/a/477578
class AuthenticationManager {

	const SESSION_KEY_LOGINTOKEN = 'sm_logintoken';

	// Login flow
	/* @var DBObjectMapper */
	private $loginSessionMap, $activeSessionMap, $longLivedMap, $userMap;
	/* @var SQLStatement */
	private $permissionsQuery, $sessionLastActiveQuery;
	/* @var Form */
	private $defaultForm;

	// Active user
	/* @var $user User|AbstractUser */
	private $user, $session, $permissions;

	// Customisation
	private $authSchemes = [];

	public function initialize(Kernel $kernel) {
		$db = $kernel->getDatabase();
		$engine = $db->getEngine();
		$this->loginSessionMap = $db->map(LoginSession::class);
		$this->activeSessionMap = $db->map(ActiveSession::class);
		$this->longLivedMap = $db->map(LongLivedSession::class);
		$this->userMap = $this->userMap ?: $db->map(User::class);
		$this->permissionsQuery = $db->prepareFile('permissions', true);
		$this->sessionLastActiveQuery = $db->prepare('UPDATE ' . $engine->quote('sessions') . ' SET ' . $engine->quote('lastActive') . ' = NOW() WHERE ' . $engine->quote('id') . ' = %d', false);

		$formBuilder = new FormBuilder();
		$language = $kernel->getLanguageRepository();

		$formBuilder->add('_logintoken', Types\HiddenType::class);
		$formBuilder->add('email', Types\EmailType::class, [
			'label' => $language->smooth_form_email_label
		]);
		$formBuilder->add('password', Types\PasswordType::class, [
			'label'       => $language->smooth_form_password,
			'constraints' => [
				new MaximumLengthConstraint(72) // For hashing we use BCRYPT, which is limited to 72 characters
			]
		]);
		if ($kernel->getConfig()->authentication_rememberme) {
			$formBuilder->add('rememberme', Types\CheckboxType::class, [
				'label'      => $language->smooth_form_rememberme,
				'mergelabel' => false
			]);
		}
		$formBuilder->add('submit', Types\SubmitType::class, [
			'label' => $language->smooth_form_submit
		]);

		$this->defaultForm = $formBuilder->getForm();

		$this->permissions = [];
	}

	public function addAuthenticationScheme($scheme, $callable) {
		if (!is_callable($callable))
			throw new RuntimeException('Passed $callable is not callable');
		$this->authSchemes[$scheme] = $callable;
	}

	public function setUserClass($clazz) {
		$classDef = new \ReflectionClass($clazz);

		if (!$classDef->isSubclassOf(User::class))
			throw new RuntimeException('Class ' . $clazz . ' does not derive from ' . User::class);

		global $kernel;
		$this->userMap = $kernel->getDatabase()->map($clazz);
	}

	public function getLoginForm(Request $request) {
		// Do we have a known login session yet?
		$session = $this->loginSessionMap->fetch([
			'_separator' => 'OR', // Either match
			'token'      => $request->post->_logintoken ?: (isset($_SESSION[self::SESSION_KEY_LOGINTOKEN]) ? $_SESSION[self::SESSION_KEY_LOGINTOKEN] : '-'),
			'ip'         => $request->server->REMOTE_ADDR
		]);
		// If not, create a new login session
		if (!$session)
			$session = $this->assignLoginSession($request);

		global $kernel;
		if ($kernel->getConfig()->authentication_loginroute != null && $this->defaultForm->getAction(false) === null)
			$this->defaultForm->setAction($kernel->getConfig()->authentication_loginroute);
		$this->defaultForm->setValue('_logintoken', $session->getToken());

		return $this->defaultForm;
	}

	/**
	 * @param Request $request The request to compare the login form against.
	 * @param bool $createSession Should a session be created upon success? If not, you will have to call #assignSession yourself later.
	 * @return bool|User User instance on success, false on failure.
	 */
	public function checkLoginResult(Request $request, $createSession = true) {
		$form = $this->defaultForm;
		$form->validate($request);

		if ($form->hasResult() && $form->isValid()) {
			/* @var $session LoginSession */
			$session = $this->loginSessionMap->fetch([
				'token' => $request->post->_logintoken,
				'ip'    => $request->server->REMOTE_ADDR
			]);

			if (!$session) {
				$form->addErrorMessage('Your session has expired, please try again.');
				$this->assignLoginSession($request);
				return false;
			}

			// Determine the timeout we enforce. 1 fail = 0, 2 fails = 2, 3 fails = 4, 4 fails = 8, >=5 fails = 16 sec
			$timeout = min($session->getFailedAttempts() < 2 ? 0 : pow(2, $session->getFailedAttempts() - 1), 16);
			$remaining = $timeout - (time() - $session->getLastUpdate());
			if ($remaining > 0) {
				$form->addErrorMessage('Please try again in ' . $remaining . ' seconds.');
				return false;
			}

			/* @var $user User */
			$user = $this->userMap->fetch([
				'email' => $request->post->get('email')
			]);

			if (!$user) {
				$form->addErrorMessage('Email and/or password are incorrect.');
				$session->increaseFailure();
				$this->loginSessionMap->insert($session);
				return false;
			}

			$authenticated = false;
			foreach ($this->authSchemes as $scheme => $callable) {
				if (strpos($user->password, $scheme . '://') === 0) {
					$result = $callable($request, $user, substr($user->password, strlen($scheme) + 3));
					if ($result)
						$authenticated = true;
				}
			}

			if (!$authenticated) {
				if (!password_verify($request->post->password, $user->password)) {
					$form->addErrorMessage('Email and/or password are incorrect.');
					$session->increaseFailure();
					$this->loginSessionMap->insert($session);
					return false;
				}

				if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
					$user->setPassword($request->post->password);
					$this->userMap->insert($user);
				}
			}

			$this->loginSessionMap->delete($session);
			if ($createSession) {
				global $kernel;
				$this->assignSession($user, $kernel->getConfig()->authentication_rememberme && $request->post->rememberme);
			}

			return $user;
		} else
			return false;
	}

	private function assignLoginSession(Request $request) {
		$session = new LoginSession($request);
		$this->loginSessionMap->insert($session);
		$_SESSION[self::SESSION_KEY_LOGINTOKEN] = $session->getToken();
		return $session;
	}

	public function assignSession(User $user, $longLivedSession = false) {
		$activeSession = new ActiveSession($user);
		$this->activeSessionMap->insert($activeSession);

		if ($longLivedSession) {
			$longLivedSession = new LongLivedSession($user, $activeSession);
			$this->longLivedMap->insert($longLivedSession);
		}
	}

	/**
	 * @return User|AbstractUser
	 */
	public function getActiveUser() {
		global $kernel;

		if (!$this->user) {
			$this->session = ActiveSession::readCookie($this->activeSessionMap);
			if ($this->session == null && $kernel->getConfig()->authentication_rememberme) {
				$longlived = LongLivedSession::readCookie($this->longLivedMap);

				if ($longlived != null) {
					$this->user = $this->userMap->fetch($longlived->getUserId());
					$this->activeSessionMap->delete($longlived->getActiveSessionId());
					$this->session = new ActiveSession($this->user);
					$this->activeSessionMap->insert($this->session);

					$longlived->updateSession($this->session);
					$this->longLivedMap->insert($longlived);
				}
			} else
				$this->user = $this->userMap->fetch($this->session->getUserId());

			if ($this->session == null)
				return new AnonymousUser();
			else
				$this->sessionLastActiveQuery->execute($this->session->getId());

			$this->getPermissions($this->user);
		}

		return $this->user;
	}

	/**
	 * @param int|User $user
	 * @return array
	 */
	public function getPermissions($user) {
		if ($user instanceof User)
			$user = $user->getId();
		if (!isset($this->permissions[$user]))
			$this->permissions[$user] = $this->permissionsQuery->execute($user)->getAsArray();
		return $this->permissions[$user];
	}

	public function hasPermission($permission, $user = false) {
		if (!$user)
			$user = $this->user;
		return in_array($permission, $this->getPermissions($user));
	}

	public function canGo() {
		$args = func_get_args();

		if (!isset($args[0]))
			throw new RuntimeException('AuthenticationManager#canGo(...) called with no arguments, requires at least 1.');

		global $kernel;
		$route = $kernel->getRouteDatabase()->getRoute($args[0]);
		return $this->verifyAccess(null, $route, array_splice($args, 1));
	}

	public function verifyAccess($request, array $routeOpts, array $parameters) {
		if (isset($routeOpts['authentication']) && $routeOpts['authentication'] !== false) {
			$user = $this->getActiveUser();

			// Plain boolean
			if ($routeOpts['authentication'] === true) {
				if (!$user->isLoggedIn())
					return $request ? $this->determineNoAccessAction($request, false) : false;
			} // Callable function
			else if (is_callable($routeOpts['authentication'])) {
				$response = call_user_func($routeOpts['authentication'], $routeOpts, $parameters);
				if ($response instanceof Response)
					return $request ? $response : false;
				else if ($response === false)
					return $request ? $this->determineNoAccessAction($request, $user->isLoggedIn()) : false;
			} // Permissions
			else {
				$required = (array)$routeOpts['authentication'];
				$missing = array_diff($required, $user->isLoggedIn() ? $this->permissions[$user->getId()] : []);
				if (count($missing) > 0)
					return $request ? $this->determineNoAccessAction($request, $user->isLoggedIn()) : false;
			}
		}

		return true; // Proceed as normal
	}

	private function determineNoAccessAction(Request $request, $isLoggedIn) {
		global $kernel;
		if (!$isLoggedIn && isset($kernel->getConfig()->authentication_loginroute))
			return new RedirectResponse($kernel->getRouteDatabase()->buildPath($kernel->getConfig()->authentication_loginroute) . '?' . http_build_query([
					'ref' => $request->server->REQUEST_URI
				]));

		return $kernel->error($kernel->getLanguageRepository()->getEntry('smooth_error_access'));
	}

	public function logout() {
		$this->getActiveUser();
		if ($this->session) {
			$this->activeSessionMap->delete($this->session);

			$longlived = $this->longLivedMap->fetch([
				'activeSessionId' => $this->session->getId()
			]);
			if ($longlived)
				$this->longLivedMap->delete($longlived);
		}

		setcookie(ActiveSession::SESSION_KEY, '-', 1, '/', cookie_domain(), false, false);
		setcookie(LongLivedSession::SESSION_KEY, '-', 1, '/', cookie_domain(), false, false);
	}

}