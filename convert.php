<?php /** @noinspection SqlResolve */

/*
 * https://discuss.flarum.org/d/8631-old-passwords
 * https://discuss.flarum.org/d/5791-masquerade-by-friendsofflarum-the-user-profile-builder
 */


$unb = 'dwforum.unb1_';
$flarum = 'flarum.flarum_';




echo <<< END
BEGIN;

-- user import. we ignore failing users - they seem to be spammers only
INSERT IGNORE INTO {$flarum}users
    (
     `id`,
     `username`,
     `email`,
     `is_email_confirmed`,
     `password`,
     `migratetoflarum_old_password`,
     `bio`,
     `avatar_url`,
     `joined_at`,
     `last_seen_at`
     )
SELECT
       `ID`,
       REGEXP_REPLACE(LOWER(`Name`), '[^a-z0-9_-]', '_'),
       `EMail`,
       `ValidatedEmail` != '',
       '',
       CONCAT('{"type":"kmd5", "password":"', `password`, '"}'),
       `About`,
       `Avatar`,
       FROM_UNIXTIME(RegDate),
       FROM_UNIXTIME(LastActivity)
  FROM {$unb}Users
;      

-- remove the gravatar marker, it's not an url
UPDATE {$flarum}users SET `avatar_url` = NULL WHERE `avatar_url` = 'gravatar';

-- FIXME import additional user data into masquerade profile

-- import all categories as secondary tags
INSERT INTO {$flarum}tags
    (
    `id`,
    `name`,
    `slug`,
    `description`
    )
SELECT
    `ID`,
    `Name`,
    REGEXP_REPLACE(LOWER(`Name`), '[^a-z0-9_-]', '-'),
    `Description`
  FROM {$unb}Forums
 WHERE Flags = 0
;

-- import threads FIXME figure out Options column in UNB
-- Part 1: we insert with the user id
INSERT IGNORE INTO {$flarum}discussions
    (
    `id`,
    `title`,
    `slug`,
    `created_at`,
    `user_id`
    )
SELECT
    `ID`,
    `Subject`,
    REGEXP_REPLACE(LOWER(`Subject`), '[^a-z0-9_-]', '-'),
    FROM_UNIXTIME(`Date`),
    User
  FROM {$unb}Threads
; 

-- Part 2: the same query, all exisiting IDs will be skipped bt we fill in missing ones with a NULL user
INSERT IGNORE INTO {$flarum}discussions
    (
    `id`,
    `title`,
    `slug`,
    `created_at`,
    `user_id`
    )
SELECT
    `ID`,
    `Subject`,
    REGEXP_REPLACE(LOWER(`Subject`), '[^a-z0-9_-]', '-'),
    FROM_UNIXTIME(`Date`),
    NULL
  FROM {$unb}Threads
; 


-- import posts FIXME convert BBCode?
INSERT INTO {$flarum}posts
    (
    `id`,
    `discussion_id`,
    `created_at`,
    `user_id`,
    `type`,
    `content`,
    `edited_at`,
    `edited_user_id`,
    `ip_address`
    )
SELECT
    `ID`,
    `Thread`,
    FROM_UNIXTIME(`Date`),
    IF(`User` > 0, `User`, NULL),
    'comment',
    `Msg`,
    FROM_UNIXTIME(`EditDate`),
    IF(`EditUser` > 0, `EditUser`, NULL),
    `IP`
  FROM {$unb}Posts
;

ROLLBACK;
END;

