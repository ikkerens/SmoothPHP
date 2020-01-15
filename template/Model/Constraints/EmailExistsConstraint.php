<?php
/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * EmailExistsConstraint.php
 */

namespace Test\Model\Constraints;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class EmailExistsConstraint extends Constraint {
	private $checkQuery;

	public function __construct() {
		global $kernel;
		$this->checkQuery = $kernel->getDatabase()->prepare('SELECT `id` FROM `users` WHERE `email` = %s');
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if ($this->checkQuery->execute($value)->hasData())
			$form->addErrorMessage('That email address already exists!');
	}
}