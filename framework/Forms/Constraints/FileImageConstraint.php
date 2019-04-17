<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileImageConstraint.php
 */

namespace SmoothPHP\Framework\Forms\Constraints;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class FileImageConstraint extends Constraint {
	private $acceptMime;

	public function __construct($acceptMime = 'image/*') {
		$this->acceptMime = $acceptMime;
	}

	public function setOptions(array &$options) {
		$options['attr']['accept'] = $this->acceptMime;
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if (strpos($this->acceptMime, 'image/') !== false) {
			$imageType = exif_imagetype($request->files->{$name}->location);
			if ($imageType === false || !fnmatch($this->acceptMime, image_type_to_mime_type($imageType))) {
				global $kernel;
				$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_file_image_invalid'), $label));
			}
		}
	}

}