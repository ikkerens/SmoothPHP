<?php

/* !
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * * * *
 * Copyright (C) 2016 Rens Rikkerink
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * * * *
 * FormContainer.php
 * Container for array elements that will pass on calls to each individual element.
 */

namespace SmoothPHP\Framework\Forms\Containers;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;

class FormContainer implements Constraint {
    private $backing;

    public function __construct(array $backing) {
        $this->backing = $backing;
    }

    public function __get($name) {
        return $this->backing[$name];
    }

    public function __toString() {
        $result = '';
        foreach($this->backing as $element)
            $result .= $element;
        return $result;
    }

    public function checkConstraint(Request $request, $name, $value, array &$failReasons) {
        foreach($this->backing as $element)
            if ($element instanceof Constraint) {
                if ($element instanceof Type)
                    $value = $request->post->get($element->getFieldName());
                $element->checkConstraint($request, null, $value, $failReasons);
            }
    }

}