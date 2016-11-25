<?php

/* !
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * * * *
 * Copyright (C) 2016 Rens Rikkerink
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * * * *
 * Type.php
 * Description
 */

namespace SmoothPHP\Framework\Forms\Containers;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Constraints\RequiredConstraint;

abstract class Type implements Constraint {
    protected $field;
    protected $attributes;
    private $constraints;

    public function __construct($field, array $attributes = array()) {
        $this->field = $field;
        $this->attributes = array_replace_recursive(array(
            'label' => self::getLabel($field),
            'required' => true,
            'attr' => array(
                'class' => ''
            ),
            'constraints' => array()
        ), $attributes);
    }

    public function buildConstraints() {
        $this->constraints = array();
        foreach($this->attributes['constraints'] as $constraint) {
            if ($constraint instanceof Constraint)
                $this->constraints[] = $constraint;
            else
                $this->constraints[] = new $constraint();
        }

        if ($this->attributes['required'])
            $this->constraints[] = new RequiredConstraint();
    }

    public function checkConstraint(Request $request, $name, $value, array &$failReasons) {
        foreach($this->constraints as $constraint)
            /* @var $constraint Constraint */
            $constraint->checkConstraint($request, $this->attributes['label'], $value, $failReasons);
    }

    public function getFieldName() {
        return $this->field;
    }

    public function generateLabel() {
        return sprintf('<label for="%s">%s</label>',
            $this->field,
            $this->attributes['label']);
    }

    public function __toString() {
        $htmlAttributes = array();
        $attributes = $this->attributes['attr'];

        $attributes['id'] = $this->field;
        $attributes['name'] = $this->field;

        foreach($attributes as $key => $attribute)
            if (isset($attribute) && strlen($attribute) > 0)
                $htmlAttributes[] = sprintf('%s="%s"', $key, addcslashes($attribute, '"'));

        return sprintf('<input %s />', implode(' ', $htmlAttributes));
    }

    protected static function getLabel($field) {
        $pieces = preg_split('/(?=[A-Z])/', $field);
        array_map('strtolower', $pieces);
        $pieces[0] = ucfirst($pieces[0]);

        return implode(' ', $pieces);
    }
}