<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * RecaptchaType.php
 */

namespace SmoothPHP\Framework\Forms\Types;

use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Containers\Type;
use SmoothPHP\Framework\Forms\Form;

class RecaptchaType extends Type {

	public function __construct($field) {
		parent::__construct($field);
		global $kernel;
		$this->options = array_replace_recursive($this->options, [
			'attr' => [
				'class'        => 'g-recaptcha',
				'data-sitekey' => $kernel->getConfig()->recaptcha_site_key
			]
		]);
	}

	public function initialize(array $options) {
		$this->options = array_merge_recursive($this->options, $options);

		global $kernel;
		$kernel->getAssetsRegister()->addJS('https://www.google.com/recaptcha/api.js');
	}

	public function checkConstraint(Request $request, $name, $label, $value, Form $form) {
		global $kernel;
		$context = stream_context_create([
			'http' => [
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query([
					'secret'   => $kernel->getConfig()->recaptcha_site_secret,
					'response' => $request->post->get('g-recaptcha-response'),
					'remoteip' => $request->getIP()
				])
			]
		]);
		$response = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context));

		if (!$response->success)
			$form->addErrorMessage(sprintf($kernel->getLanguageRepository()->getEntry('smooth_form_captcha'), $this->options['label']));
	}

	public function __toString() {
		return sprintf('<div %s></div>', $this->transformAttributes($this->options['attr']));
	}

}