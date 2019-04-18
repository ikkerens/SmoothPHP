<?php

namespace SmoothPHP\Framework\Cache\Assets\Distribution;

interface AssetDistributor {

	public function getTextURL($type, $hash, callable $contentProvider);

	public function getImageURL($cachedFile, $virtualPath);

	public function clearCache();

}