<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * RouteDatabase.php
 */

namespace SmoothPHP\Framework\Flow\Routing;

use Exception;
use LogicException;
use SmoothPHP\Framework\Core\Abstracts\Controller;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Requests\Robots;
use SmoothPHP\Framework\Flow\Responses\PlainTextResponse;

class RouteDatabase {
	const HANDLER = '-EOP-';
	const WILDCARD_INPUT = '%';
	const VARARGS_INPUT = '...';

	private $defaults;
	private $routes;
	private $resolveCache;

	public function __construct() {
		$this->routes = [];
		$this->resolveCache = [];

		$this->defaults = [
			'method'       => 'GET',
			'domain'       => self::WILDCARD_INPUT,
			'https'        => HTTPS::IGNORE,
			'content-type' => PlainTextResponse::class,
			'robots'       => Robots::AUTO
		];
	}

	public function setDefault($field, $value) {
		$this->defaults[$field] = $value;
	}

	public function register(array $routeOptions) {
		$routeOpts = array_merge($this->defaults, $routeOptions);

		if (isset($routeOpts['controllercall']))
			throw new \RuntimeException('You can not explicitly declare the controllercall route option.');
		if (isset($this->routes[$routeOpts['name']]))
			throw new \RuntimeException('A route with name ' . $routeOpts['name'] . ' is already registered.');

		foreach (((array)$routeOpts['method']) as $method) {
			$path = explode('/', $routeOptions['path']);
			$path = array_merge([strtoupper($method), $routeOpts['domain']], $path);
			$path = array_values(array_filter($path, 'strlen'));

			$currentRecursive = &$this->resolveCache;
			for ($i = 0; $i < count($path); $i++) {
				$pathPart = $path[$i];

				if ($pathPart == self::VARARGS_INPUT && $i < (count($path) - 1))
					throw new LogicException('Route varargs can only be used at the end of the path');

				if (!isset($currentRecursive[$pathPart]))
					$currentRecursive[$pathPart] = [];

				$currentRecursive = &$currentRecursive[$pathPart];
			}

			if (isset($currentRecursive[self::HANDLER]) && !(isset($routeOpts['force']) && $routeOpts['force']))
				throw new LogicException(sprintf('Route with name "%s" is attempting to register a handler for url "%s", which is already defined. Give the route a \'force\' => true argument to override.', $routeOpts['name'], $routeOpts['path']));
			$currentRecursive[self::HANDLER] = &$routeOpts;
		}

		$this->routes[$routeOpts['name']] = &$routeOpts;
	}

	public function initializeControllers(Kernel $kernel) {
		$controllers = [];
		foreach ($this->routes as &$route) {
			if (!isset($controllers[$route['controller']])) {
				$controllers[$route['controller']] = new $route['controller']();
				$controllers[$route['controller']]->onInitialize($kernel);
			}
			$route['controllercall'] = new ControllerCall($controllers[$route['controller']], $route['call']);
		}
	}

	/**
	 * @param $request Request $request->server->REQUEST_URI and $request->server->REQUEST_METHOD are used to determine the route.
	 * @return ResolvedRoute|bool
	 */
	public function resolve(Request $request) {
		// Clean the URL of extra arguments
		$cleanedQuery = explode('?', $request->server->REQUEST_URI)[0];
		$cleanedQuery = explode('#', $cleanedQuery)[0];

		// Add the base data and split the request
		$base = [$request->server->REQUEST_METHOD, $request->server->SERVER_NAME];
		$resolveQuery = array_merge($base, array_filter(explode('/', $cleanedQuery), 'strlen'));

		// Find the route options needed for this url
		$parameters = [];
		$routeOpts = $this->findRoute(0, $this->resolveCache, $resolveQuery, $parameters);

		if (!is_array($routeOpts)) {
			// 404
			return false;
		}

		$request->meta->route = $routeOpts;
		return new ResolvedRoute($routeOpts, $parameters);
	}

	private function findRoute($depth, array &$cacheLevel, array &$query, array &$parameters) {
		if ($depth === count($query)) { // Are we at the end of the query?
			if (isset($cacheLevel[self::HANDLER]))
				return $cacheLevel[self::HANDLER]; // Notify that we found the handler
			else
				return false; // Go back 1 up in the stack, maybe we can resolve the query otherwise
		} else {
			$part = $query[$depth];

			if (isset($cacheLevel[$part])) {
				$result = $this->findRoute($depth + 1, $cacheLevel[$part], $query, $parameters);

				if (is_array($result))
					return $result;
			}

			if (isset($cacheLevel[self::WILDCARD_INPUT])) {
				if ($depth >= 2)
					array_push($parameters, $part); // Add it to the params list
				$result = $this->findRoute($depth + 1, $cacheLevel[self::WILDCARD_INPUT], $query, $parameters);

				if (is_array($result))
					return $result;
				else if ($depth >= 2)
					array_pop($parameters); // Nope that was not it
			}

			if (isset($cacheLevel[self::VARARGS_INPUT]) && isset($cacheLevel[self::VARARGS_INPUT][self::HANDLER])) {
				array_push($parameters, array_slice($query, $depth));
				return $cacheLevel[self::VARARGS_INPUT][self::HANDLER];
			}

			return false;
		}
	}

	/**
	 * @param string $name
	 * @return array|bool Route info
	 */
	public function getRoute($name) {
		if (isset($this->routes[$name]))
			return $this->routes[$name];
		else
			return false;
	}

	public function getAllRoutes() {
		return $this->routes;
	}

	/**
	 * @param $routeName
	 * @return Controller
	 */
	public function getController($routeName) {
		return $this->routes[$routeName]['controllercall']->getController();
	}

	public function buildPath() {
		list($route, $args) = $this->validateRoute(func_get_args());

		/* @var $request Request */
		global $request;
		switch ($route['https']) {
			case HTTPS::ENFORCE_ACTIVE:
				if (!$request->isSecure())
					return $this->assembleFullPath($route, $args);
				break;
			case HTTPS::ENFORCE_INACTIVE:
				if ($request->isSecure())
					return $this->assembleFullPath($route, $args);
				break;
			default:
		}

		return $this->assemblePath($route, $args);
	}

	public function buildFullPath() {
		list($route, $args) = $this->validateRoute(func_get_args());
		return $this->assembleFullPath($route, $args);
	}

	private function validateRoute(array $args) {
		if (count($args) < 1)
			throw new Exception('RouteDatabase#buildPath(...) called with no arguments, requires at least 1.');

		$route = $this->getRoute($args[0]);
		if (!$route)
			throw new Exception(sprintf('Route \'%s\' does not exist.', $args[0]));

		return [$route, array_slice($args, 1)];
	}

	private function assemblePath(array $route, array $args) {
		$path = $route['path'];
		for ($i = 0; $i < count($args); $i++) {
			$path = preg_replace('/' . preg_quote(self::WILDCARD_INPUT, '/') . '/', $args[$i], $path, 1, $count);
			if ($count == 1)
				continue;
			else {
				if (strpos($path, self::VARARGS_INPUT) !== false) {
					$varArgs = implode('/', array_splice($args, $i));
					$path = preg_replace('/' . preg_quote(self::VARARGS_INPUT, '/') . '/', $varArgs, $path);
					break;
				} else
					throw new Exception(sprintf('Not enough arguments given, route \'%s\' requested argument %d, %d given.', $route['name'], $i, count($args)));
			}
		}

		if (strpos($path, self::WILDCARD_INPUT) !== false || strpos($path, self::VARARGS_INPUT) !== false)
			throw new Exception('Not enough arguments given to path for route "' . $route['name'] . '".');

		return $path;
	}

	private function assembleFullPath(array $route, array $args) {
		$path = $this->assemblePath($route, $args);

		/* @var $request Request */
		global $request;
		switch ($route['https']) {
			case HTTPS::ENFORCE_ACTIVE:
				$protocol = 'https';
				break;
			case HTTPS::ENFORCE_INACTIVE:
				$protocol = 'http';
				break;
			default:
				$protocol = $request->isSecure() ? 'https' : 'http';
		}
		$host = $route['domain'] != self::WILDCARD_INPUT ? $route['domain'] : $request->server->HTTP_HOST;

		return sprintf('%s://%s%s', $protocol, $host, $path);
	}

}