/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * permissions.my.query.sql
 */

SELECT `permission` FROM `permissions` WHERE `userId` = %d AND NOT ISNULL(`permission`)
UNION DISTINCT
SELECT `permission` FROM `permissions` WHERE `group` IN (SELECT `group` FROM `permissions` WHERE `userId` = %r AND NOT ISNULL(`group`)) AND NOT ISNULL(`permission`)