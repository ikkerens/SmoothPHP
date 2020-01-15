/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * 001-testuser.debug.sql
 */

# Inserts a user with email test@test.com and password test.
# You can easily obtain password hashes by using the CLI command: smoothphp hash
INSERT INTO `users` (`email`, `password`) VALUES ('test@test.com', '$2y$10$ksPgFolO8YPV3Cq4Q72squWMU6kEw50t58cJuEmlV.WYvmTAhzHP.')