<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Constraint.php
 */

namespace SmoothPHP\Framework\Forms;

use SmoothPHP\Framework\Flow\Requests\Request;

abstract class Constraint {

	public function setOptions(array &$options) {
	}

	public abstract function checkConstraint(Request $request, $name, $label, $value, Form $form);

}