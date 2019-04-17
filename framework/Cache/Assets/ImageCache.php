<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ImageCache.php
 */

namespace SmoothPHP\Framework\Cache\Assets;

use SmoothPHP\Framework\Cache\Builder\FileCacheProvider;
use SmoothPHP\Framework\Core\Lock;

class ImageCache {
	private $cacheFileFormat;

	public function __construct($folder) {
		$this->cacheFileFormat = __ROOT__ . 'cache/' . $folder . '/%s.%dx%d.%s.%s';
	}

	public function ensureCache($image, &$width = null, &$height = null) {
		$isOriginal = false;
		list($originalWidth, $originalHeight) = getimagesize($image);
		if ($width == null && $height != null)
			$width = $height * ($originalWidth / $originalHeight);
		else if ($width != null && $height == null)
			$height = $width * ($originalHeight / $originalWidth);
		else if ($width == null && $height == null) {
			$width = $originalWidth;
			$height = $originalHeight;
			$isOriginal = true;
		}

		$cacheFile = $this->getCachePath($image, $width, $height, $fileName);

		if (!is_dir(dirname($cacheFile)))
			mkdir(dirname($cacheFile), FileCacheProvider::PERMS, true);

		// Check if the cache exists
		if (file_exists($cacheFile))
			return $cacheFile;

		// If we get to this point, the above return has not returned.
		// Which means we have to generate a new cache
		$lock = new Lock(pathinfo($cacheFile, PATHINFO_BASENAME));

		if ($lock->lock()) {
			array_map('unlink', glob(sprintf($this->cacheFileFormat, $fileName, $width, $height, '*', pathinfo($image, PATHINFO_EXTENSION))));

			if ($isOriginal) {
				// Create a symlink
				symlink($image, $cacheFile);
			} else {
				// Generate a resized image file
				$ext = pathinfo($image, PATHINFO_EXTENSION);
				$source = call_user_func(sprintf('imagecreatefrom%s', $ext == 'jpg' ? 'jpeg' : $ext), $image);
				$target = imagecreatetruecolor($width, $height);

				// Make the image transparent to begin with
				imagealphablending($target, false);
				imagesavealpha($target, true);
				$transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
				imagefilledrectangle($target, 0, 0, $width, $height, $transparent);

				// Copy the old image in, sampling it
				imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

				// Write the new image
				imagepng($target, $cacheFile, 9);

				// Clean up the source
				imagedestroy($source);
				imagedestroy($target);
			}

			$lock->unlock();
		}

		return $cacheFile;
	}

	public function getCachePath($sourceFile, $width, $height, &$fileName = null) {
		$fileName = str_replace(['/', '\\'], ['_', '_'], str_replace(__ROOT__, '', $sourceFile));
		$checksum = file_hash($sourceFile);

		return sprintf($this->cacheFileFormat, $fileName, $width, $height, $checksum, pathinfo($sourceFile, PATHINFO_EXTENSION));
	}

}