<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * index.php
 */

define('__ENV__', 'dev'); // Options: dev, debug, prod

use SmoothPHP\Framework\Flow\Requests\Request;

{
	$loader = require_once '../framework/Bootstrap.php';
	$loader(new Website());
}

$request = Request::createFromGlobals();
$response = $kernel->getResponse($request);
$response->send();