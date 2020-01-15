<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CompilerState.php
 */

namespace SmoothPHP\Framework\Templates\Compiler;

class CompilerState {
	public $vars;
	public $blocks;
	public $uncertainDepth;
	public $finishing;
	public $performCalls;
	public $allowMinify;

	public function __construct() {
		$this->vars = new Scope();
		$this->blocks = [];
		$this->uncertainVars = 0;
		$this->finishing = false;
		$this->performCalls = false;
		$this->allowMinify = false;
	}

	public function createSubScope() {
		$copy = new self();

		$copy->vars = new Scope($this->vars);
		$copy->blocks = $this->blocks;
		$copy->finishing = $this->finishing;
		$copy->performCalls = $this->performCalls;
		$copy->allowMinify = $this->allowMinify;

		return $copy;
	}

	public function pushUncertainty() {
		$this->uncertainVars++;
	}

	public function popUncertainty() {
		$this->uncertainVars--;
	}

	public function isUncertain() {
		return $this->uncertainVars != 0;
	}
}
