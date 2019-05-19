<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2019
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Config.php
 */

namespace SmoothPHP\Framework\Core;

use SmoothPHP\Framework\Core\Cron\Handlers\CronTab;
use SmoothPHP\Framework\Database\Engines\MySQL;

class Config {
	public $default_language = 'en_us';
	public $detect_language = true;
	public $alwaysSecure = false; // Set this to true if you have a loadbalancer that handles HTTP
	public $behindProxy = false; // Set this to true if the cookies should use X-Forwarded-For instead.

	public $date_format = 'l, d-M-Y H:i:s';
	public $image_inline_threshold = 10000;

	public $db_enabled = false;
	public $db_engine = MySQL::class;
	public $db_host = 'localhost';
	public $db_database = 'smoothphp';
	public $db_schema = 'public'; // Not used for MySQL
	public $db_port = 3306;
	public $db_user = 'root';
	public $db_password = '';
	public $db_parameters = ''; // Additional parameters to be passed to PDO

	public $authentication_enabled = false;
	public $authentication_loginroute = null;
	public $authentication_rememberme = true;
	public $authentication_longlived_age = 604800; // 1 week by default

	public $cron_handler = CronTab::class;
	public $enable_robots = true;

	public $recaptcha_site_key = '';
	public $recaptcha_site_secret = '';
}
