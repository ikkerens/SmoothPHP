<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Request.php
 */

namespace SmoothPHP\Framework\Flow\Requests;

use Exception;
use SmoothPHP\Framework\Flow\Requests\Files\FileSource;

/**
 * @property VariableSource get
 * @property VariableSource post
 * @property VariableSource server
 * @property FileSource files
 */
class Request {
	private $getr, $postr, $serverr, $filesr;
	public $meta;

	/**
	 * @return Request
	 */
	public static function createFromGlobals() {
		return new Request($_GET, $_POST, $_SERVER, $_FILES);
	}

	public function __construct(array $get, array $post, array $server, array $files = []) {
		$this->getr = new VariableSource($get);
		$this->postr = new VariableSource($post);
		$this->serverr = new VariableSource($server);
		$this->filesr = new FileSource($files);
		$this->meta = new \stdClass();
	}

	/**
	 * @param $scope
	 * @return VariableSource
	 * @throws Exception
	 */
	public function __get($scope) {
		switch ($scope) {
			case "get":
			case "post":
			case "server":
			case "files":
				return $this->{$scope . 'r'};
			default:
				throw new Exception("Invalid scope.");
		}
	}

	public function isSecure() {
		return $this->serverr->has('HTTPS') && $this->serverr->HTTPS == 'on';
	}

}
