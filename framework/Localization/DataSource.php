<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * DataSource.php
 */

namespace SmoothPHP\Framework\Localization;

interface DataSource {

	public function getAvailableLanguages();

	public function getEntry($language, $key);

}