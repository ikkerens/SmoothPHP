/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * authentication.my.sql
 */

CREATE TABLE `users` (
    `id`       INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `email`    VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255)        NOT NULL
)
    ENGINE = InnoDB;

CREATE TABLE `permissions` (
    `id`         INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `userId`     INT(11)                      DEFAULT NULL,
    `group`      VARCHAR(255)                 DEFAULT NULL,
    `permission` VARCHAR(255)                 DEFAULT NULL
)
    ENGINE = InnoDB;

CREATE TABLE `loginsessions` (
    `id`             INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `ip`             VARCHAR(45)         NOT NULL,
    `token`          VARCHAR(255)        NOT NULL,
    `lastUpdate`     INT(11)             NOT NULL,
    `failedAttempts` INT(11)             NOT NULL
)
    ENGINE = MEMORY;

CREATE TABLE `sessions` (
    `id`         INT(11) PRIMARY KEY                 NOT NULL AUTO_INCREMENT,
    `userId`     INT(11)                             NOT NULL,
    `lastActive` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `ip`         VARCHAR(50)                         NOT NULL,
    `selector`   VARCHAR(255)                        NOT NULL,
    `validator`  VARCHAR(255)                        NOT NULL
)
    ENGINE = MEMORY;

CREATE TABLE `longlivedsessions` (
    `id`              INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `userId`          INT(11)             NOT NULL,
    `activeSessionId` INT(11)             NOT NULL,
    `selector`        VARCHAR(255)        NOT NULL,
    `validator`       VARCHAR(255)        NOT NULL
)
    ENGINE = InnoDB;

ALTER TABLE `permissions`
    ADD UNIQUE INDEX `userGroup` (`userId`, `group`),
    ADD UNIQUE INDEX `userPerm` (`userId`, `permission`),
    ADD UNIQUE INDEX `groupPerm` (`group`, `permission`),
    ADD INDEX (`userId`),
    ADD CONSTRAINT `fk_userid` FOREIGN KEY (`userId`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;
