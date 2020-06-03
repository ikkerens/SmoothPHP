<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * Uninstall.php
 */

namespace SmoothPHP\Framework\Core\CLI;

use SmoothPHP\Framework\Core\Kernel;

class Uninstall extends Command {

	public function getDescription() {
		return 'Wipes the in-use database.';
	}

	public function handle(Kernel $kernel, array $argv) {
		if (file_exists(__ROOT__ . '/production.lock'))
			throw new \RuntimeException('Can not run uninstall script because production.lock exists.' . PHP_EOL);

		if (count($argv) < 1 || $argv[0] !== 'y') {
			printf('THIS METHOD WILL EMPTY DATABASE \'%s\', ARE YOU ABSOLUTELY SURE? [y/n]' . PHP_EOL, $kernel->getConfig()->db_database);
			$line = trim(fgets(STDIN));

			if ($line != 'y')
				throw new \RuntimeException('Cancelled.' . PHP_EOL);
		}

		(new Cache())->handle($kernel, []);
		$kernel->getDatabase()->getEngine()->wipe();

		print('Done clearing database.' . PHP_EOL);
	}

}
