<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileSizeConstraint.php
 */

namespace SmoothPHP\Framework\Forms\Constraints;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class FileSizeConstraint extends Constraint {
	private $fileSize;

	public function __construct($fileSize) {
		$this->fileSize = $fileSize;
	}

	public function setOptions(array &$options) {
		// TODO Add javascript file-size checking
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if ($request->files->{$name}->size > $this->fileSize) {
			global $kernel;
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_file_size'), $label));
		}
	}

}