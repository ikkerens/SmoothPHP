<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Utilities.php
 */

/**
 * Convenience method that doesn't use an array-reference as argument.
 * @param mixed $array The array (or object to be casted to array) to get the last element of.
 * @return mixed The last element of the passed array.
 * @see end()
 */
function last($array) {
	$real = (array)$array;
	return end($real);
}

/**
 * Method that wraps md5_file with a simple caching mechanism to prevent calculating the same file multiple times
 * @param $filename string Path to the file
 * @return string|null md5 checksum or null if the file doesn't exist
 * @note Files in src/assets/ will be fast-tracked in prod, as they will have been pre-cached by Kernel initialization. This cache does not respect deletions.
 */
function file_hash($filename) {
	$filename = realpath($filename);

	if (__ENV__ == 'prod') {
		global $kernel;
		$hash = $kernel->getAssetsRegister()->getCachedFileHash($filename);
		if ($hash != null)
			return $hash;
	}

	if (!file_exists($filename))
		return null;

	return md5_file($filename);
}

/**
 * Recursively traverses a directory and calls action for each file and directory.
 * @param $folder string Path to folder to traverse.
 * @param $action callable Function that is called for each file and directory, first argument passed will be the relative path (including $folder), second argument will be a boolean which is true if the call is a directory.
 * @param int $depth Maximum recursive depth to follow
 * @warning While this function does support symlinks, it will not stop if there is symlink recursion.
 */
function traverse_path($folder, $action, $depth = -1) {
	$contents = @scandir($folder);
	if (!$contents)
		return;

	foreach ($contents as $file) {
		if ($file != '.' && $file != '..') {
			if (is_dir($folder . '/' . $file)) {
				if ($depth != 0)
					traverse_path($folder . '/' . $file, $action, $depth - 1);
				call_user_func($action, $folder . '/' . $file, true);
			} else {
				call_user_func($action, $folder . '/' . $file, false);
			}
		}
	}
}

function cookie_domain() {
	global $request;
	$domain = explode('.', $request->server->SERVER_NAME);
	if (count($domain) < 2)
		$cookieDomain = $request->server->SERVER_NAME;
	else
		$cookieDomain = sprintf('.%s.%s', $domain[count($domain) - 2], $domain[count($domain) - 1]);
	return $cookieDomain;
}

/*
 * If we don't have PHP7's random_bytes, fall back to openssl_random_pseudo_bytes
 */
if (!function_exists('random_bytes')) {
	function random_bytes($length) {
		return openssl_random_pseudo_bytes($length);
	}
}