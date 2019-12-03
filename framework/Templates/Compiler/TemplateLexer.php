<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * TemplateLexer.php
 */

namespace SmoothPHP\Framework\Templates\Compiler;

class TemplateLexer {
	private $pointer;
	private $content;

	public function __construct($content) {
		$this->content = $content;
		$this->pointer = 0;
	}

	public function next() {
		if ($this->pointer == strlen($this->content))
			return false;
		return $this->content[$this->pointer++];
	}

	public function isAnyOf() {
		if ($this->pointer == strlen($this->content))
			return false;

		return in_array($this->content[$this->pointer], func_get_args());
	}

	public function isWhitespace() {
		return $this->isAnyOf(' ', "\n", "\r", "\t");
	}

	public function skipWhitespace() {
		while ($this->isWhitespace())
			if (!$this->next())
				return;
	}

	public function peekSingle() {
		if ($this->pointer == strlen($this->content))
			return false;
		return $this->content[$this->pointer];
	}

	public function peek($compare) {
		$length = strlen($compare);
		if (($this->pointer + $length) > strlen($this->content))
			return false;

		$characters = substr($this->content, $this->pointer, $length);
		if (mb_strtolower($characters) === mb_strtolower($compare)) {
			$this->pointer += $length;
			return true;
		} else
			return false;
	}

	public function readAlphaNumeric() {
		$rawString = '';

		while (true) {
			$char = $this->peekSingle();
			if (ctype_alnum($char) || $char === '_')
				$rawString .= $this->content[$this->pointer++];
			else
				return $rawString;
		}
	}

	public function readRaw($stackEnd = null, $stackEscape = null) {
		$rawString = '';

		while (true) {
			if ($stackEscape != null && $this->peek($stackEscape)) {
				$rawString .= $stackEnd;
			} else if ($stackEnd != null && $this->peek($stackEnd)) {
				return $rawString;
			} else {
				$char = $this->next();
				if ($char !== false)
					$rawString .= $char;
				else
					return $rawString;
			}
		}
	}

	public function remainder() {
		return substr($this->content, $this->pointer);
	}

	public function getDebugSurroundings($incriminating) {
		$surround = 10;
		$start = $this->pointer - $surround;
		return sprintf('%s%s%s',
			substr($this->content, max(0, $start), $surround + min(0, $start)),
			$incriminating,
			substr($this->content, $this->pointer + strlen($incriminating), 10)
		);
	}

	public function getRawContent() {
		return $this->content;
	}
}