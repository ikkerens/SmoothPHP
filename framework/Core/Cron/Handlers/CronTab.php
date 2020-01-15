<?php

/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * CronTab.php
 */

namespace SmoothPHP\Framework\Core\Cron\Handlers;

use SmoothPHP\Framework\Core\Cron\CronHandler;
use SmoothPHP\Framework\Core\Cron\Job;

class CronTab extends CronHandler {
	const FORMAT = '%s %ssmoothphp cron run %s';

	const CRONTAB_BLOCK_MATCH = '$%s$m';
	const CRONTAB_HEADER = '# SmoothPHP crontab start - %s';
	const CRONTAB_FOOTER = '# SmoothPHP crontab end - %s';

	public function save(array $jobs) {
		$tmpCronReadFile = tempnam(sys_get_temp_dir(), 'smoothphp_crontab_temp_read');
		$tmpCronWriteFile = tempnam(sys_get_temp_dir(), 'smoothphp_crontab_temp_write');
		self::exec('crontab -l > ' . $tmpCronReadFile);

		$readHandle = fopen($tmpCronReadFile, 'r');
		$writeHandle = fopen($tmpCronWriteFile, 'w+');

		$started = false;
		while ($readHandle && !feof($readHandle)) {
			$line = fgets($readHandle);

			if (preg_match(sprintf(self::CRONTAB_BLOCK_MATCH, sprintf(self::CRONTAB_HEADER, __ROOT__)), $line)) {
				$started = true;
				continue;
			}
			if (preg_match(sprintf(self::CRONTAB_BLOCK_MATCH, sprintf(self::CRONTAB_FOOTER, __ROOT__)), $line)) {
				$started = false;
				continue;
			}

			if (!$started) {
				fwrite($writeHandle, $line);
			}
		}

		fclose($readHandle);
		@unlink($tmpCronReadFile);

		if (count($jobs) > 0) {
			fwrite($writeHandle, sprintf(self::CRONTAB_HEADER, __ROOT__) . "\n");
			foreach ($jobs as $job) {
				fwrite($writeHandle, self::toCrontab($job) . "\n");
			}
			fwrite($writeHandle, sprintf(self::CRONTAB_FOOTER, __ROOT__) . "\n");
		}

		echo self::exec('crontab ' . $tmpCronWriteFile);

		fclose($writeHandle);
		@unlink($tmpCronWriteFile);
	}

	private static function exec($command) {
		ob_start();
		system($command, $retVal);
		return ob_get_clean();
	}

	private static function toCrontab(Job $job) {
		return sprintf(self::FORMAT, $job->getTimer(), __ROOT__, $job->getName());
	}

}