<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Robots.php
 */

namespace SmoothPHP\Framework\Flow\Requests;

use ReflectionClass;
use SmoothPHP\Framework\Core\Abstracts\Controller;
use SmoothPHP\Framework\Flow\Responses\FileStream;
use SmoothPHP\Framework\Flow\Responses\PlainTextResponse;
use SmoothPHP\Framework\Flow\Routing\RouteDatabase;

class Robots extends Controller {
	const CACHE_FILE_FORMAT = __ROOT__ . 'cache/robots.%s.txt';

	const AUTO = 0;
	const ALLOW = 1;
	const DISALLOW = 2;
	const HIDE = 3;

	public static function registerRoute(RouteDatabase $route) {
		$route->register([
			'name'         => 'robots_txt',
			'path'         => '/robots.txt',
			'controller'   => self::class,
			'call'         => 'getRobots',
			'content-type' => FileStream::class,
			'robots'       => self::HIDE,
			'internal'     => true
		]);
	}

	public function getRobots(Request $request, RouteDatabase $db) {
		$md5 = md5_file((new ReflectionClass(new \Website()))->getFileName());
		$cacheFile = sprintf(self::CACHE_FILE_FORMAT, $md5);

		if (!file_exists($cacheFile)) {
			// Delete old cache files
			array_map('unlink', glob(sprintf(self::CACHE_FILE_FORMAT, '*'), GLOB_NOSORT));
			$robots = $this->generateRobotsArray($request, $db);

			$robotsStr = implode("\n", $robots);

			$fh = fopen($cacheFile, 'w+');
			fwrite($fh, $robotsStr);
			fclose($fh);
		} else {
			$robotsStr = file_get_contents($cacheFile);
		}

		return new PlainTextResponse($robotsStr);
	}

	private function generateRobotsArray(Request $request, RouteDatabase $db) {
		$robots = [];
		foreach ($db->getAllRoutes() as $route) {
			if ($route['domain'] == RouteDatabase::WILDCARD_INPUT || $route['domain'] == $request->server->HTTP_HOST) {
				$url = str_replace(RouteDatabase::VARARGS_INPUT, '', str_replace(RouteDatabase::WILDCARD_INPUT, '*', $route['path']));
				switch ($route['robots']) {
					case Robots::HIDE:
						continue 2; // Continue the foreach
					case Robots::DISALLOW:
						$term = 'Disallow';
						break;
					case Robots::ALLOW:
						$term = 'Allow';
						break;
					default:
						if (isset($route['authentication']))
							$term = 'Disallow';
						else
							$term = 'Allow';
				}

				$robots[] = sprintf('%s: %s', $term, $url);
			}
		}

		sort($robots);
		array_unshift($robots, 'User-agent: *');

		return $robots;
	}

}