<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ControllerCall.php
 */

namespace SmoothPHP\Framework\Flow\Routing;

use ReflectionMethod;
use SmoothPHP\Framework\Authentication\AuthenticationManager;
use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Core\Abstracts\Controller;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Database\Database;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Responses\Response;
use SmoothPHP\Framework\Localization\LanguageRepository;

class ControllerCall {
	private $instanceRefs;
	private $parameters;

	private $callable;
	private $controllerArgs;

	public function __construct(Controller $controller, $call) {
		$this->instanceRefs = [];
		$this->controllerArgs = [];
		$this->parameters = [];

		$this->callable = [$controller, $call];
		$method = new ReflectionMethod(get_class($controller), $call);
		$i = -1;

		foreach ($method->getParameters() as $parameter) {
			$className = $parameter->getClass() ? $parameter->getClass()->name : null;
			switch ($className) {
				case Request::class:
				case Kernel::class:
				case RouteDatabase::class:
				case AssetsRegister::class:
				case LanguageRepository::class:
				case Database::class:
				case AuthenticationManager::class:
					$this->controllerArgs[] = &$this->getRef($className);
					break;
				default: // Mixed-type arg, url-argument
					$this->parameters[++$i] = null;
					$this->controllerArgs[] = &$this->parameters[$i];
					break;
			}
		}
	}

	private function &getRef($clazz) {
		if (!isset($this->instanceRefs[$clazz]))
			$this->instanceRefs[$clazz] = false;

		return $this->instanceRefs[$clazz];
	}

	public function getController() {
		return $this->callable[0];
	}

	/**
	 * @return Response|mixed
	 */
	public function performCall(Kernel $kernel, Request $request, array $args) {
		$this->setRef(Kernel::class, function () use ($kernel) {
			return $kernel;
		});
		$this->setRef(Request::class, function () use ($request) {
			return $request;
		});
		$this->setRef(RouteDatabase::class, function () use ($kernel) {
			return $kernel->getRouteDatabase();
		});
		$this->setRef(AssetsRegister::class, function () use ($kernel) {
			return $kernel->getAssetsRegister();
		});
		$this->setRef(LanguageRepository::class, function () use ($kernel) {
			return $kernel->getLanguageRepository();
		});
		$this->setRef(Database::class, function () use ($kernel) {
			return $kernel->getDatabase();
		});
		$this->setRef(AuthenticationManager::class, function () use ($kernel) {
			return $kernel->getAuthenticationManager();
		});

		$i = 0;
		foreach ($args as $arg) {
			$this->parameters[$i++] = $arg;
		}

		return call_user_func_array($this->callable, $this->controllerArgs);
	}

	private function setRef($clazz, callable $builder) {
		if (isset($this->instanceRefs[$clazz]))
			$this->instanceRefs[$clazz] = $builder();
	}

}