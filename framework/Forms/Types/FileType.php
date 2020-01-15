<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Form;

class FileType extends Type {
	private $required;

	public function __construct($field) {
		parent::__construct($field);
		$this->options = array_replace_recursive($this->options, [
			'attr'     => [
				'type' => 'file'
			],
			'required' => true
		]);
	}

	public function initialize(array $options) {
		$this->required = !isset($options['required']) ? last($this->options['required']) : last($options['required']);
		$options['required'] = false;
		parent::initialize($options);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		global $kernel;
		$language = $kernel->getLanguageRepository();
		$label = $label ?: last($this->options['label']);

		if ($request->files->{$name} === false || !$request->files->{$name}->isUploaded()) {
			if ($this->required)
				$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_file_required'), $label));

			return;
		}

		switch ($request->files->{$name}->error) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_INI_SIZE:
				$form->addErrorMessage(sprintf($language->getEntry('smooth_form_file_size'), $label));
				return;
			default:
				$form->addErrorMessage(sprintf($language->getEntry('smooth_form_file_genericerror'), $label));
				return;
		}

		parent::checkConstraint($request, $name, $label, $value, $form);
	}

}