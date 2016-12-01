<?php

/* !
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * * * *
 * Copyright (C) 2016 Rens Rikkerink
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * * * *
 * PasswordType.php
 * Type for html's input[type=password]
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;

class PasswordType extends StringType{

    public function __construct($field) {
        parent::__construct($field);

        global $kernel;
        $this->attributes = array_replace_recursive($this->attributes, array(
            'attr' => array(
                'type' => 'password',
                'placeholder' => $kernel->getLanguageRepository()->getEntry('smooth_form_password')
            )
        ));
    }

    public function checkConstraint(Request $request, $name, $value, array &$failReasons) {
        parent::checkConstraint($request, $name, $value, $failReasons);
        // Make sure we never send back the password
        unset($this->attributes['attr']['value']);
    }

}