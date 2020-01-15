<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * ClassLoader.php
 */

namespace SmoothPHP\Framework\Core\ClassLoader;

interface ClassLoader {

	public function addPrefix($namespace, $path);

	public function loadFromComposer();

	public function register();

}