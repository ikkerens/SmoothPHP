/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * cleanup_sessions.pg.query.sql
 */

DELETE FROM "sessions" WHERE "lastActive" < (NOW() - INTERVAL '12 hours')