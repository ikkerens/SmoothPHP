<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * LanguageRepository.php
 */

namespace SmoothPHP\Framework\Localization;

use Locale;
use RuntimeException;
use SmoothPHP\Framework\Core\Kernel;

class LanguageRepository {
	const SESSION_KEY = 'sm_language';

	private $kernel;
	private $sources;

	public function __construct(Kernel $kernel) {
		$this->kernel = $kernel;
		$this->sources = [];
	}

	public function addSource(DataSource $source) {
		array_unshift($this->sources, $source);
	}

	public function setSessionLanguage($language) {
		$languages = [];
		foreach ($this->sources as $source)
			$languages = array_merge($languages, $source->getAvailableLanguages());
		if (!in_array($language, $languages))
			throw new RuntimeException("Language '" . $language . "' could not be found.");

		$_SESSION[self::SESSION_KEY] = $language;
		return true;
	}

	public function getLanguage() {
		return $this->detectLanguage();
	}

	public function getEntry($key, $language = null) {
		$language = $language ?: $this->detectLanguage();
		foreach ($this->sources as $source) {
			$entry = $source->getEntry($language, $key);
			if ($entry)
				return $entry;
		}

		return '#!lang-' . $language . ':' . $key;
	}

	public function __get($name) {
		return $this->getEntry($name);
	}

	private function detectLanguage() {
		if (isset($_SESSION[self::SESSION_KEY]) && !empty($_SESSION[self::SESSION_KEY]))
			return $_SESSION[self::SESSION_KEY];

		$language = $this->kernel->getConfig()->default_language;

		if ($this->kernel->getConfig()->detect_language) {
			$languages = [];
			foreach ($this->sources as $source)
				$languages = array_unique(array_merge($languages, $source->getAvailableLanguages()));
			$languages = array_change_key_case($languages, CASE_LOWER);

			if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
				$language = $this->kernel->getConfig()->default_language;
			else
				$language = Locale::lookup($languages,
					Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']),
					true,
					$this->kernel->getConfig()->default_language);
		}

		$_SESSION[self::SESSION_KEY] = $language;
		return $language;
	}
}
