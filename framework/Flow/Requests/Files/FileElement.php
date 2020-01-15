<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileElement.php
 */

namespace SmoothPHP\Framework\Flow\Requests\Files;

/**
 * @property string name
 * @property string tmp_name
 * @property string location
 * @property int error
 * @property int size
 */
class FileElement {
	private $file;

	public function __construct(array $file) {
		$this->file = $file;
	}

	public function __get($name) {
		switch ($name) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'location':
				$name = 'tmp_name';
			case 'name':
			case 'tmp_name':
			case 'error':
			case 'size':
				return $this->file[$name];
		}
	}

	public function isUploaded() {
		return $this->file['error'] != UPLOAD_ERR_NO_FILE;
	}

	public function saveAs($path) {
		if (!move_uploaded_file($this->file['tmp_name'], $path))
			throw new \RuntimeException('Could not move uploaded file.');
	}

}