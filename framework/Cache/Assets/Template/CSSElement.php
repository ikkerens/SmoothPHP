<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CSSElement.php
 */

namespace SmoothPHP\Framework\Cache\Assets\Template;

use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Core\Lock;
use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\TemplateCompiler;
use tubalmartin\CssMin\Minifier;

class CSSElement extends Element {
	const FORMAT = '<link rel="stylesheet" type="text/css" href="%s" />';
	const COMPILED_PATH = __ROOT__ . 'cache/css/compiled.%s.css';

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd) {
		if (__ENV__ == 'dev')
			$chain->addElement(new DebugCSSElement());
		else
			$chain->addElement(new self());
	}

	public function optimize(CompilerState $tpl) {
		return $this;
	}

	public function output(CompilerState $tpl) {
		/* @var $assetsRegister AssetsRegister */
		$assetsRegister = $tpl->vars->assets->getValue();

		$files = [];

		foreach (array_unique($assetsRegister->getCSSFiles()) as $css) {
			if (strtolower(substr($css, 0, 4)) == 'http') {
				echo sprintf(self::FORMAT, $css);
				continue;
			}

			$files[] = $assetsRegister->getCSSPath($css);
		}

		if (count($files) == 0)
			return;

		$hash = md5(array_reduce($files, function ($carry, $file) {
			return $carry . ',' . $file . filemtime($file);
		}));

		if (!file_exists(sprintf(self::COMPILED_PATH, $hash))) {
			$lock = new Lock('compiled.css.' . $hash);

			if ($lock->lock()) {
				$contents = '';
				array_walk($files, function ($file) use ($assetsRegister, &$contents) {
					$contents .= ' ' . file_get_contents($file);
				});

				$cssmin = new Minifier();
				$optimized = $cssmin->run($contents);

				$path = sprintf(self::COMPILED_PATH, $hash);
				file_put_contents($path, $optimized);
				file_put_contents($path . '.gz', gzencode($optimized, 9));
			}
		}

		global $kernel;
		$path = $kernel->getRouteDatabase()->buildPath('assets_css_compiled', $hash);

		header('Link: <' . $path . '>; rel=preload; as=style', false);
		echo sprintf(self::FORMAT, $path);
	}

}