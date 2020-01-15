<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Reinstall.php
 */

namespace SmoothPHP\Framework\Core\CLI;

use SmoothPHP\Framework\Core\Kernel;

class Reinstall extends Command {

	public function getDescription() {
		return 'Executes both uninstall and install.';
	}

	public function handle(Kernel $kernel, array $argv) {
		if (file_exists(__ROOT__ . '/production.lock')) {
			printf('Can not run reinstall script because production.lock exists.' . PHP_EOL);
			return;
		}

		(new Uninstall())->handle($kernel, $argv);
		(new Install())->handle($kernel, $argv);
	}

}