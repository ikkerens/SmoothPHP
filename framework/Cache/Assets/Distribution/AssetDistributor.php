<?php
/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * AssetDistributor.php
 */

namespace SmoothPHP\Framework\Cache\Assets\Distribution;

interface AssetDistributor {

	public function getTextURL($type, $hash, callable $contentProvider);

	public function getImageURL($cachedFile, $virtualPath);

	public function clearCache();

}