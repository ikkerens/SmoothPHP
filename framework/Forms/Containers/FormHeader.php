<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FormHeader.php
 */

namespace SmoothPHP\Framework\Forms\Containers;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraint;
use SmoothPHP\Framework\Forms\Form;

class FormHeader extends Constraint {

	const SESSION_KEY = 'sm_formtokens';

	private $form;
	private $options;

	public function __construct(Form $form, array $options) {
		$this->form = $form;

		$this->options = array_replace_recursive([
			'token' => true,
			'attr'  => [
				'method'  => 'post',
				'class'   => 'smoothform',
				'enctype' => 'multipart/form-data'
			]
		], $options);
	}

	public function __toString() {
		$tokenInput = '';
		if ($this->options['token']) {
			if (!isset($_SESSION[self::SESSION_KEY]))
				$_SESSION[self::SESSION_KEY] = [];

			$formToken = md5(uniqid(rand(), true));
			$_SESSION[self::SESSION_KEY][] = $formToken;

			$tokenInput = sprintf('<input type="hidden" id="_token" name="_token" value="%s" />', $formToken);
		}

		$htmlAttributes = [];
		$attributes = $this->options['attr'];

		$attributes['action'] = $this->form->getAction();

		foreach ($attributes as $key => $attribute)
			if (isset($attribute) && strlen($attribute) > 0)
				$htmlAttributes[] = sprintf('%s="%s"', $key, addcslashes($attribute, '"'));

		return sprintf('<form %s>%s', implode(' ', $htmlAttributes), $tokenInput);
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		if ($this->options['token']) {
			$tokens = isset($_SESSION[self::SESSION_KEY]) ? $_SESSION[self::SESSION_KEY] : [];

			$key = array_search($request->post->_token, $tokens, true);
			if ($key === false) {
				global $kernel;
				$form->addErrorMessage($kernel->getLanguageRepository()->getEntry('smooth_form_token'));
				return;
			}
			unset($_SESSION[self::SESSION_KEY][$key]);
		}
	}

}