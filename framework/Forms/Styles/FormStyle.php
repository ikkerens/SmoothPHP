<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FormStyle.php
 */

namespace SmoothPHP\Framework\Forms\Styles;

use SmoothPHP\Framework\Forms\Containers\FieldGroup;
use SmoothPHP\Framework\Forms\Containers\FormContainer;
use SmoothPHP\Framework\Forms\Containers\FormHeader;
use SmoothPHP\Framework\Forms\Containers\Type;

interface FormStyle {
	public function buildForm(FormHeader $header, FormContainer $body);

	public function buildTypeElement($label, Type $type);

	public function buildFieldGroup($label, FieldGroup $parent, array $children);
}