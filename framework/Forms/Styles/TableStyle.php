<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * TableStyle.php
 */

namespace SmoothPHP\Framework\Forms\Styles;

use SmoothPHP\Framework\Forms\Containers\FieldGroup;
use SmoothPHP\Framework\Forms\Containers\FormContainer;
use SmoothPHP\Framework\Forms\Containers\FormHeader;
use SmoothPHP\Framework\Forms\Containers\Type;

class TableStyle implements FormStyle {

	public function buildForm(FormHeader $header, FormContainer $body) {
		return [
			'header'     => $header,
			'tablestart' => '<table>',
			'inputs'     => $body,
			'tableend'   => '</table>',
			'footer'     => '</form>'
		];
	}

	public function buildTypeElement($label, Type $type) {
		return [
			'rowstart'     => '<tr><td>',
			'label'        => $label,
			'rowseparator' => '</td><td>',
			'input'        => $type,
			'rowend'       => '</td></tr>'
		];
	}

	public function buildFieldGroup($label, FieldGroup $parent, array $childDefinitions) {
		$children = [];

		$first = true;
		foreach ($childDefinitions as $child) {
			$children[] = new FormContainer([
				'groupseparator' => $first ? '' : sprintf('</td></tr><tr class="fieldgroup_%s"><td></td><td>', $parent->getFieldName()),
				'input'          => $child
			]);
			$first = false;
		}

		return [
			'rowstart'     => sprintf('<tr class="fieldgroup_%s"><td>', $parent->getFieldName()),
			'label'        => $label,
			'rowseparator' => '</td><td>',
			'children'     => new FormContainer($children),
			'rowend'       => '</td></tr>'
		];
	}

}