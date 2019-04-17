<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * XML.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use DOMDocument;
use DOMElement;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

class XML extends Response implements AlternateErrorResponse {

	const XMLNS = '_namespace';
	const XMLATTR = '_attribute_';

	private $built;

	public function buildErrorResponse($message) {
		$this->controllerResponse = [
			'error' => $message
		];
	}

	public function build(Kernel $kernel, Request $request) {
		if (is_array($this->controllerResponse))
			$this->built = self::fromArray($this->controllerResponse)->saveXML();
		else if ($this->controllerResponse instanceof DOMDocument)
			$this->built = $this->controllerResponse->saveXML();
		else
			throw new \InvalidArgumentException('Returned controller response is not an array or DOMDocument.');
	}

	protected function sendHeaders() {
		parent::sendHeaders();
		header('Content-Type: text/xml; charset=utf-8');
	}

	protected function sendBody() {
		echo $this->built;
	}

	public static function fromArray(array $arrayDoc) {
		$doc = new DOMDocument('1.0', 'UTF-8');
		self::transformArrayToDOMNode($doc, $doc, $arrayDoc);
		return $doc;
	}

	private static function transformArrayToDOMNode(DOMDocument $doc, \DOMNode $parent, array &$array) {
		foreach ($array as $key => $value) {
			self::createElement($doc, $parent, $key, $value);
		}
	}

	private static function createElement(DOMDocument $doc, \DOMNode $parent, $key, $value) {
		$elementArguments = [];
		$method = 'createElement';
		if (is_array($value) && isset($value['_namespace'])) {
			$elementArguments[] = $value['_namespace'];
			$method .= 'NS';
			unset($value['_namespace']);
		}

		if (substr($key, -2) != '[]') {
			$elementArguments[] = $key;

			if (!is_array($value))
				$elementArguments[] = $value;

			/* @var $element DOMElement */
			$element = call_user_func_array([$doc, $method], $elementArguments);

			if (is_array($value)) {
				$filteredArray = array_filter($value, function ($value, $key) use (&$element) {
					if (substr($key, 0, strlen(self::XMLATTR)) == self::XMLATTR) {
						$element->setAttribute(substr($key, strlen(self::XMLATTR)), $value);
						return false;
					}

					return true;
				}, ARRAY_FILTER_USE_BOTH);

				self::transformArrayToDOMNode($doc, $element, $filteredArray);
			}

			$parent->appendChild($element);
		} else {
			$key = substr($key, 0, -2);
			$elementArguments[] = $key;

			foreach ($value as $child)
				self::createElement($doc, $parent, $key, $child);
		}
	}

}