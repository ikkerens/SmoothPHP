<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * TemplateEngine.php
 */

namespace SmoothPHP\Framework\Templates;

use SmoothPHP\Framework\Cache\Builder\FileCacheProvider;
use SmoothPHP\Framework\Cache\Builder\RuntimeCacheProvider;
use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\Elements\PrimitiveElement;

class TemplateEngine {
	private $compiler;

	private $runtimeCache;

	public function __construct() {
		$this->compiler = new TemplateCompiler();

		$compileCache = new FileCacheProvider('ctpl', 'ctpl',
			[$this->compiler, 'compile'],
			[TemplateEngine::class, 'deserializeTemplate'],
			[TemplateEngine::class, 'serializeTemplate']);
		$this->runtimeCache = RuntimeCacheProvider::create([$compileCache, 'fetch']);
	}

	public function fetch($templateName, array $args) {
		$path = sprintf('%ssrc/templates/%s', __ROOT__, $templateName);
		$template = $this->runtimeCache->fetch($path);

		return $this->prepareOutput($template, $args);
	}

	public function simpleFetch($absoluteFile, array $args = []) {
		return $this->prepareOutput($this->compiler->compile($absoluteFile), $args, false);
	}

	public function simpleCompile($templateSource, array $args = []) {
		return $this->prepareOutput($this->compiler->compileSource($templateSource), $args, false);
	}

	private function prepareOutput(Element $template, array $args, $allowMinify = __ENV__ != 'dev') {
		$state = new CompilerState();
		$state->allowMinify = $allowMinify;
		foreach ($args as $key => $value)
			$state->vars->{$key} = new PrimitiveElement($value);
		$state->performCalls = true;
		$template = $template->optimize($state);

		// Gather output and return
		ob_start();
		$template->output($state);
		return ob_get_clean();
	}

	public static function deserializeTemplate($fileName) {
		return unserialize(gzinflate(file_get_contents($fileName)));
	}

	public static function serializeTemplate($fileName, $data) {
		file_put_contents($fileName, gzdeflate(serialize($data)));
	}

}