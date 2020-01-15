<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AlternateErrorResponse.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

interface AlternateErrorResponse {

	public function buildErrorResponse($message);

}