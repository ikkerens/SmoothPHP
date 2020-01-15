/*
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * authentication.pg.sql
 */

CREATE TABLE users (
    "id"       SERIAL PRIMARY KEY,
    "email"    VARCHAR(255) UNIQUE NOT NULL,
    "password" VARCHAR(255)        NOT NULL
);

CREATE TABLE permissions (
    "id"         SERIAL PRIMARY KEY,
    "userId"     INT          DEFAULT NULL,
    "group"      VARCHAR(255) DEFAULT NULL,
    "permission" VARCHAR(255) DEFAULT NULL
);

CREATE TABLE loginsessions (
    "id"             SERIAL PRIMARY KEY,
    "ip"             VARCHAR(45)  NOT NULL,
    "token"          VARCHAR(255) NOT NULL,
    "lastUpdate"     INT          NOT NULL,
    "failedAttempts" INT          NOT NULL
);

CREATE TABLE sessions (
    "id"         SERIAL PRIMARY KEY,
    "userId"     INT          NOT NULL,
    "lastActive" TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "ip"         VARCHAR(50)  NOT NULL,
    "selector"   VARCHAR(255) NOT NULL,
    "validator"  VARCHAR(255) NOT NULL
);

CREATE TABLE longlivedsessions (
    "id"              SERIAL PRIMARY KEY,
    "userId"          INT          NOT NULL,
    "activeSessionId" INT          NOT NULL,
    "selector"        VARCHAR(255) NOT NULL,
    "validator"       VARCHAR(255) NOT NULL
);

ALTER TABLE permissions
    ADD CONSTRAINT "userGroup" UNIQUE ("userId", "group"),
    ADD CONSTRAINT "userPerm" UNIQUE ("userId", "permission"),
    ADD CONSTRAINT "groupPerm" UNIQUE ("group", "permission"),
    ADD CONSTRAINT "userId" UNIQUE ("userId"),
    ADD CONSTRAINT "fk_userid" FOREIGN KEY ("userId") REFERENCES "users"("id")
        ON DELETE CASCADE
        ON UPDATE CASCADE;
