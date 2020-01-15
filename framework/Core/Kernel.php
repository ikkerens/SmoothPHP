<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Kernel.php
 */

namespace SmoothPHP\Framework\Core;

use SmoothPHP\Framework\Authentication\AuthenticationManager;
use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Core\Abstracts\WebPrototype;
use SmoothPHP\Framework\Core\Cron\CronManager;
use SmoothPHP\Framework\Database\Database;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Requests\Robots;
use SmoothPHP\Framework\Flow\Responses\AlternateErrorResponse;
use SmoothPHP\Framework\Flow\Responses\PlainTextResponse;
use SmoothPHP\Framework\Flow\Routing\RouteDatabase;
use SmoothPHP\Framework\Localization\FileDataSource;
use SmoothPHP\Framework\Localization\LanguageRepository;
use SmoothPHP\Framework\Templates\TemplateEngine;

class Kernel {
	// Site data
	private $config;
	private $routeDatabase;

	// Runtime
	private $errorHandler, $ignoreRouteContentType;
	private $templateEngine;
	private $assetsRegister;
	private $database;
	private $authentication;
	private $languagerepo;

	public function __construct() {
		$this->config = new Config();
		$this->authentication = new AuthenticationManager();
		$this->assetsRegister = new AssetsRegister();
		$this->errorHandler = [$this, 'handleError'];
	}

	public function registerCron(CronManager $mgr) {
		if ($this->config->authentication_enabled) {
			$mgr->newJob('authentication_clean_loginsessions', '@hourly', function (Kernel $kernel) {
				$kernel->getDatabase()->prepareFile('cleanup_loginsessions')->execute();
			});
			$mgr->newJob('authentication_clean_sessions', '@hourly', function (Kernel $kernel) {
				$kernel->getDatabase()->prepareFile('cleanup_sessions')->execute();
			});
		}
	}

	public function loadPrototype(WebPrototype $prototype) {
		$this->__wakeup();

		if (!is_dir(__ROOT__ . 'framework/meta/vendor/'))
			die('SmoothPHP is not yet installed. Run composer install in ' . realpath(__ROOT__ . 'framework/../'));

		$this->routeDatabase = new RouteDatabase();
		$this->languagerepo = new LanguageRepository($this);
		$this->languagerepo->addSource(new FileDataSource(__ROOT__ . 'framework/meta/assets/strings/'));
		$this->templateEngine = new TemplateEngine();
		$this->assetsRegister->initialize($this);
		$prototype->initialize($this);
		if ($this->config->authentication_enabled)
			$this->authentication->initialize($this);
		else
			$this->authentication = null;
		$this->languagerepo->addSource(new FileDataSource(__ROOT__ . 'src/assets/strings/'));
		if ($this->config->enable_robots)
			Robots::registerRoute($this->routeDatabase);
		$prototype->registerRoutes($this->routeDatabase);
		$this->routeDatabase->initializeControllers($this);
	}

	public function __wakeup() {
		if (__ENV__ != 'dev') {
			global $classLoader;
			$classLoader->loadFromComposer(__ROOT__ . 'framework/meta/');
		}
	}

	public function error($message) {
		global $request;
		if (!$this->ignoreRouteContentType && isset($request->meta->route)) {
			$type = new $request->meta->route['content-type'](null);
			if ($type instanceof AlternateErrorResponse) {
				$type->buildErrorResponse($message);
				return $type;
			}
		}

		return call_user_func($this->errorHandler, $message);
	}

	/**
	 * Overwrites the default plain text error handler.
	 * @param $errorHandler string|array The callable function that accepts 1 argument for the message. Do not pass closures here.
	 * @param bool $ignoreContentType
	 */
	public function setErrorHandler($errorHandler, $ignoreContentType = false) {
		if (!is_callable($errorHandler))
			throw new \InvalidArgumentException('$errorHandler is not callable');

		$this->errorHandler = $errorHandler;
		$this->ignoreRouteContentType = $ignoreContentType;
	}

	private function handleError($message) {
		return new PlainTextResponse($message);
	}

	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return RouteDatabase
	 */
	public function getRouteDatabase() {
		return $this->routeDatabase;
	}

	/**
	 * @return TemplateEngine
	 */
	public function getTemplateEngine() {
		return $this->templateEngine;
	}

	/**
	 * @return AssetsRegister
	 */
	public function getAssetsRegister() {
		return $this->assetsRegister;
	}

	/**
	 * @return Database
	 */
	public function getDatabase() {
		if (!$this->config->db_enabled)
			throw new \RuntimeException("Database is not enabled");
		if (!isset($this->database))
			$this->database = new Database($this->config);
		return $this->database;
	}

	/**
	 * @return AuthenticationManager
	 */
	public function getAuthenticationManager() {
		if (!$this->config->authentication_enabled)
			throw new \RuntimeException("Authentication is not enabled");
		return $this->authentication;
	}

	/**
	 * @return LanguageRepository
	 */
	public function getLanguageRepository() {
		return $this->languagerepo;
	}

	/**
	 * @param Request $request The request that determines how the response is made, and how it is given.
	 * @return \SmoothPHP\Framework\Flow\Responses\Response
	 */
	public function getResponse(Request $request) {
		$resolvedRoute = $this->routeDatabase->resolve($request);
		if (!$resolvedRoute) {
			http_response_code(404);
			$response = $this->error($this->languagerepo->getEntry('smooth_error_404'));
			$response->build($this, $request);
			return $response;
		}
		return $resolvedRoute->buildResponse($this, $request);
	}

}
