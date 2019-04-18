<?php

namespace SmoothPHP\Framework\Cache\Assets\Distribution;

use SmoothPHP\Framework\Core\Lock;

class LocalAssetDistributor implements AssetDistributor {

	public function getTextURL($type, $hash, callable $contentProvider) {
		$assetPath = __ROOT__ . 'cache/' . $type . '/compiled.' . $hash . '.' . $type;
		if (!file_exists($assetPath)) {
			$lock = new Lock('compiled.' . $hash . '.' . $type);

			if ($lock->lock()) {
				$contents = $contentProvider();
				file_put_contents($assetPath, $contents);
				file_put_contents($assetPath . '.gz', gzencode($contents, 9));
			}
		}

		global $kernel;
		$path = $kernel->getRouteDatabase()->buildPath('assets_' . $type . '_compiled', $hash);
		return $path;
	}

	public function getImageURL($cachedFile, $virtualPath) {
		return $virtualPath;
	}

	public function clearCache() {
		// No action required, the rest of the cache clear will handle this part for us.
	}
}