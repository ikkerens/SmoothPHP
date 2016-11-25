<?php

namespace SmoothPHP\Framework\Templates\Elements\Operators;

use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateCompileException;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;

class InverseOperatorElement extends Element {
    private $body;

    public function __construct(Element $body) {
        $this->body = $body;
    }

    public function optimize(CompilerState $tpl) {
        $this->body = $this->body->optimize($tpl);

        if ($this->body instanceof PrimitiveElement)
            return new PrimitiveElement(!$this->body->getValue());

        return $this;
    }

    public function output(CompilerState $tpl) {
        $result = $this->optimize($tpl);

        if (!($result instanceof PrimitiveElement))
            throw new TemplateCompileException("Could not determine inverse value at runtime.");

        $result->output($tpl);
    }

}