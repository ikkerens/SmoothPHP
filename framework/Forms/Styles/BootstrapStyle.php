<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * BootstrapStyle.php
 */

namespace SmoothPHP\Framework\Forms\Styles;

use SmoothPHP\Framework\Forms\Containers\FieldGroup;
use SmoothPHP\Framework\Forms\Containers\FormContainer;
use SmoothPHP\Framework\Forms\Containers\FormHeader;
use SmoothPHP\Framework\Forms\Containers\Type;

class BootstrapStyle implements FormStyle {

	public function buildForm(FormHeader $header, FormContainer $body) {
		return ['header' => $header, 'inputs' => $body];
	}

	public function buildTypeElement($label, Type $type) {
		if ($type->getAttribute('class') == null)
			$type->setAttribute('class', 'form-control');
		else {
			$classes = array_filter((array)$type->getAttribute('class'));
			$classes[] = ' form-control';
			$type->setAttribute('class', implode(' ', $classes));
		}

		return [
			'start' => '<div class="form-group">',
			'label' => $label,
			'input' => $type,
			'end'   => '</div>'
		];
	}

	public function buildFieldGroup($label, FieldGroup $parent, array $children) {
		return array_merge(['startgroup' => '<div class="input-group">'], $children, ['endgroup' => '</div>']);
	}
}