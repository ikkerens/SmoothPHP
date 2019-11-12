<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * FileStream.php
 */

namespace SmoothPHP\Framework\Flow\Responses;

use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Flow\Requests\Request;

class FileStream extends Response {

	const CACHE_DATE = 'D, d M Y H:i:s \G\M\T';
	const BINARY_SEPARATOR = 'Smooth_Binary_Separator';

	/* @var Request */
	protected $request;

	private $range = null;
	private $length;

	public function build(Kernel $kernel, Request $request) {
		$this->request = $request;
		$options = is_array($this->controllerResponse) ? $this->controllerResponse : ['url' => $this->controllerResponse];
		$this->controllerResponse = array_merge([
			'type'    => 'application/octet-stream',
			'expires' => 86400,
			'cache'   => false,
			'cors'    => true,
			'range'   => true,
		], $options);

		if (isset($options['url'])) {
			$urlParts = explode('/', $options['url']);
			$this->controllerResponse['filename'] = end($urlParts);

			// Check if a local file exists
			if (!file_exists($this->controllerResponse['url'])) {
				// No? Let's check if the file starts with HTTP instead
				if (mb_strtolower(substr($this->controllerResponse['url'], 0, 4)) == 'http') {
					// It does, get the headers to verify if it exists and get some useful headers
					$headers = get_headers($this->controllerResponse['url']);
					$response = (int)substr($headers[0], 9, 3);

					// Success check
					if ($response >= 200 && $response < 300) {
						// Okay, the resource exists, get the content length for later usage

						foreach ($headers as $header) {
							if (strpos(strtoupper($header), 'HTTP/') !== false)
								continue;

							list($key, $value) = explode(': ', $header);
							switch (mb_strtolower($key)) {
								case 'content-length':
									$this->controllerResponse['size'] = (int)$value;
							}
						}

						// Return without throwing
						return;
					}
				}
				throw new \RuntimeException("File does not exist!");
			}
		}

		$this->length = isset($this->controllerResponse['size']) ? $this->controllerResponse['size'] : filesize($this->controllerResponse['url']);

		if ($this->controllerResponse['range'] && $this->request->server->HTTP_RANGE) {
			if (!preg_match('/^bytes=\d*-\d*(?:,\s*\d*-\d*)*$/', $this->request->server->HTTP_RANGE)) {
				http_response_code(416);
				header('Content-Range: bytes */' . $this->length);
				exit();
			}

			$this->range = [];

			$fullEnd = $this->length - 1;

			$ranges = explode(',', substr($_SERVER['HTTP_RANGE'], 6));
			foreach ($ranges as $range) {
				$end = $fullEnd;

				if ($range == '-')
					$start = $this->length - substr($range, 1);
				else {
					$range = explode('-', $range);
					$start = $range[0];
					$end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $this->length;
				}
				$end = ($end > $fullEnd) ? $fullEnd : $end;

				if ($start > $end || $start > ($this->length - 1) || $end >= $this->length) {
					http_response_code(416);
					header('Content-Range: bytes ' . $start . '-' . $end . '/' . $this->length);
					exit();
				}

				$this->range[] = [$start, $end];
			}
		}
	}

	protected function sendHeaders() {
		parent::sendHeaders();

		header('Content-Disposition: ' . (
			strpos($this->controllerResponse['type'], 'text/') === 0
			|| strpos($this->controllerResponse['type'], 'image/') === 0
			|| strpos($this->controllerResponse['type'], 'video/') === 0
				? 'inline' : 'attachment') . '; filename="' . $this->controllerResponse['filename'] . '"');
		header('Content-Type: ' . $this->controllerResponse['type']);
		if (!is_array($this->range))
			header('Content-Length: ' . $this->length);
		if ($this->controllerResponse['cors'])
			header('Access-Control-Allow-Origin: *');

		if ($this->controllerResponse['cache']) {
			if (__ENV__ != 'dev') {
				header('Cache-Control: max-age=' . $this->controllerResponse['expires'] . ', private');
				header('Expires: ' . gmdate(self::CACHE_DATE, time() + $this->controllerResponse['expires']));
				header('Pragma: private');
			} else {
				header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
				header('Expires: ' . gmdate(self::CACHE_DATE, 0));
			}

			if (isset($this->controllerResponse['url'])) {
				$eTag = file_hash($this->controllerResponse['url']);
				$lastModified = filemtime($this->controllerResponse['url']);

				header('Last-Modified: ' . gmdate(self::CACHE_DATE, $lastModified));
				header('ETag: W/"' . $eTag . '"');

				if ($this->request->server->HTTP_IF_MODIFIED_SINCE && $lastModified > strtotime($this->request->server->HTTP_IF_MODIFIED_SINCE)) {
					http_response_code(304);
					exit();
				}
				if ($this->request->server->HTTP_IF_NONE_MATCH && $this->request->server->HTTP_IF_NONE_MATCH == $eTag) {
					http_response_code(304);
					exit();
				}
			}

			if (isset($this->controllerResponse['data'])) {
				$eTag = md5($this->controllerResponse['data']);
				header('ETag: "' . $eTag . '"');

				if ($this->request->server->HTTP_IF_NONE_MATCH && $this->request->server->HTTP_IF_NONE_MATCH == $eTag) {
					http_response_code(304);
					exit();
				}
			}
		}

		if ($this->controllerResponse['range'])
			header('Accept-Ranges: 0-' . $this->length);
		else
			header('Accept-Ranges: none');
		if (is_array($this->range)) {
			http_response_code(206);
			if (count($this->range) > 1)
				header('Content-Type: multipart/byteranges; boundary=' . self::BINARY_SEPARATOR);
			else {
				list($start, $end) = $this->range[0];
				header('Content-Range: bytes ' . $start . '-' . $end . '/' . $this->length);
			}
		}
	}

	protected function sendBody() {
		set_time_limit(0);

		if (isset($this->controllerResponse['data'])) {
			$source = &$this->controllerResponse['data'];
		} else
			$source = fopen($this->controllerResponse['url'], 'rb');

		try {
			$start = 0;
			$end = $this->length;
			if (is_array($this->range)) {
				if (count($this->range) > 1) {
					foreach ($this->range as $segment) {
						list($start, $end) = $segment;
						printf("--%s\r\nContent-Type: %s\r\nContent-Range: bytes %d-%d/%d\r\n\r\n",
							self::BINARY_SEPARATOR, $this->controllerResponse['type'],
							$start, $end, $this->length);
						$this->echoRange($source, $start, $end);
						echo "\r\n";
					}

					printf('--%s--', self::BINARY_SEPARATOR);

					return;
				}

				list($start, $end) = $this->range[0];
			}

			$this->echoRange($source, $start, $end);
		} finally {
			if (is_resource($source))
				fclose($source);
		}
	}

	private function echoRange(&$source, $start, $end) {
		if (is_resource($source)) {
			$remaining = $end - $start + 1;

			fseek($source, $start);
			while (!feof($source) && $remaining > 0) {
				$read = min(8 * 1024 * 1024, $remaining);
				$remaining -= $read;

				echo fread($source, $read);
				ob_flush();
				flush();
			}
		} else {
			$data = substr($source, $start, $end - $start + 1);
			echo $data;
			ob_flush();
			flush();
			unset($data);
		}
	}

}
