/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * cleanup_loginsessions.my.query.sql
 */

DELETE FROM `loginsessions` WHERE `lastUpdate` < UNIX_TIMESTAMP((NOW() - INTERVAL 1 HOUR))