/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * permissions.pg.query.sql
 */

SELECT "permission" FROM "permissions" WHERE "userId" = %d AND "permission" IS NOT NULL
UNION DISTINCT
SELECT "permission" FROM "permissions" WHERE "group" IN (SELECT "group" FROM "permissions" WHERE "userId" = %r AND "group" IS NOT NULL) AND "permission" IS NOT NULL