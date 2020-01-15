<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * JSElement.php
 */

namespace SmoothPHP\Framework\Cache\Assets\Template;

use JShrink\Minifier;
use SmoothPHP\Framework\Cache\Assets\AssetsRegister;
use SmoothPHP\Framework\Templates\Compiler\CompilerState;
use SmoothPHP\Framework\Templates\Compiler\TemplateLexer;
use SmoothPHP\Framework\Templates\Elements\Chain;
use SmoothPHP\Framework\Templates\Elements\Element;
use SmoothPHP\Framework\Templates\TemplateCompiler;

class JSElement extends Element {
	const FORMAT = '<script type="text/javascript" src="%s"></script>';
	const LANG_FORMAT = '<script type="text/javascript">window.SmoothLanguage = %s;</script>';

	public static function handle(TemplateCompiler $compiler, TemplateLexer $command, TemplateLexer $lexer, Chain $chain, $stackEnd) {
		if (__ENV__ == 'dev')
			$chain->addElement(new DebugJSElement());
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

		foreach (array_unique($assetsRegister->getJSFiles()) as $js) {
			if (mb_strtolower(substr($js, 0, 4)) == 'http') {
				echo sprintf(self::FORMAT, $js);
				continue;
			}

			$files[] = $assetsRegister->getJSPath($js);
		}

		if (count($files) == 0)
			return;

		$hash = md5(array_reduce($files, function ($carry, $file) {
			return $carry . ',' . $file . file_hash($file);
		}));

		$url = $assetsRegister->getAssetDistributor()->getTextURL('js', $hash, function () use (&$files, &$assetsRegister) {
			$contents = '';
			array_walk($files, function ($file) use ($assetsRegister, &$contents) {
				$contents .= '; ' . file_get_contents($file);
			});

			return Minifier::minify($contents);
		});

		// Check if we have to provide any language keys
		$langKeys = $assetsRegister->getJSLanguageKeys();
		if (count($langKeys) > 0) {
			global $kernel;
			$language = [];
			$languageRepo = $kernel->getLanguageRepository();
			foreach ($langKeys as $key)
				$language[$key] = $languageRepo->getEntry($key);
			echo sprintf(JSElement::LANG_FORMAT, json_encode($language, JSON_FORCE_OBJECT));
		}

		header('Link: <' . $url . '>; rel=preload; as=script', false);
		echo sprintf(self::FORMAT, $url);
	}
}
