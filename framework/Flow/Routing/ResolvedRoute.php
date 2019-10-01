<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ResolvedRoute.php
 */

namespace SmoothPHP\Framework\Flow\Routing;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Responses\NoContentResponse;
use SmoothPHP\Framework\Flow\Responses\RedirectResponse;
use SmoothPHP\Framework\Flow\Responses\Response;

class ResolvedRoute {
	private $route;
	private $parameters;

	public function __construct(array &$route, array $parameters) {
		$this->route = $route;
		$this->parameters = $parameters;
	}

	public function buildResponse(Kernel $kernel, Request $request) {
		// If this is not an internal route (assets etc), start a php session.
		if (!isset($this->route['internal'])) {
			session_name('sm_sid');
			session_start();
		}

		// Do we have access to this route?
		if ($kernel->getConfig()->authentication_enabled) {
			$authResponse = $kernel->getAuthenticationManager()->verifyAccess($request, $this->route, $this->parameters);
			if ($authResponse instanceof Response) {
				$authResponse->build($kernel, $request);
				return $authResponse;
			}
		}

		// Does this route enforce SSL?
		if (__ENV__ == 'prod'
			&& ($this->route['https'] == HTTPS::ENFORCE_ACTIVE && !$request->isSecure())
			|| ($this->route['https'] == HTTPS::ENFORCE_INACTIVE && $request->isSecure())
		) {
			$response = new RedirectResponse($this->route['name'], $this->parameters);
			$response->build($kernel, $request);
			return $response;
		}

		// Build the response normally, wrap it in a try-catch clause if we're in production environment
		if (__ENV__ == 'prod')
			try {
				$response = $this->route['controllercall']->performCall($kernel, $request, $this->parameters);
			} catch (\Exception $e) {
				if (http_response_code() == 200)
					http_response_code(500);
				error_log($e);
				$response = $kernel->error($kernel->getLanguageRepository()->getEntry('smooth_error'));
			}
		else
			$response = $this->route['controllercall']->performCall($kernel, $request, $this->parameters);

		// If the response isn't already wrapped, wrap it in the specified content-type
		if ($response == null)
			$response = new NoContentResponse();
		else if (!($response instanceof Response))
			$response = new $this->route['content-type']($response);

		// Prepare the response (rendering etc)
		$response->build($kernel, $request);
		return $response;
	}
}