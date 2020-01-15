<?php
/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AvatarResponse.php
 */

namespace Test\Model\Response;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Flow\Responses\FileStream;

class AvatarResponse extends FileStream {

	public function __construct($data) {
		parent::__construct([
			'type'     => 'image/png',
			'filename' => 'avatar.png',
			'expires'  => 86400,
			'cache'    => true,
			'cors'     => true,

			'size' => strlen($data),
			'data' => $data
		]);
	}

	public function build(Kernel $kernel, Request $request) {
		$this->request = $request;
	}

	protected function sendBody() {
		echo $this->controllerResponse['data'];
	}

}