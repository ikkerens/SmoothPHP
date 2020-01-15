<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CronHandler.php
 */

namespace SmoothPHP\Framework\Core\Cron;

abstract class CronHandler {

	public abstract function save(array $jobs);

}