<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * JSLanguage.php
 */

namespace SmoothPHP\Framework\Localization;

class JSLanguage {
	private $keys = [];

	public function getEntry($key, $language = null) {
		if ($language != null) {
			global $kernel;
			return $kernel->getLanguageRepository()->getEntry($key, $language);
		}

		$this->keys[] = $key;
		return 'window.SmoothLanguage[\'' . $key . '\']';
	}

	public function __get($name) {
		return $this->getEntry($name);
	}

	public function getKeys() {
		return $this->keys;
	}
}