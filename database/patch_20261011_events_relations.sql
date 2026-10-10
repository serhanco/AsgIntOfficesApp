-- Events, stage 1: who an event is for, what it relates to, who takes part and what the program is.
-- * events: audience (b2c / b2b), visibility (listed / link only / internal), featured rank (0 = none, 1 = first), head office flag, announcement file.
-- * event_relations: the offices and countries an event is linked to (a country means all of its offices).
-- * people + event_people: one shared list of doctors, executives and guests, with a role per event.
-- * event_sessions: optional day-by-day program.
-- Existing events keep working as they are: they start as patient events, listed, not featured.
-- Every statement is safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)

SET @add_event_columns = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE events ADD COLUMN audience VARCHAR(6) NOT NULL DEFAULT ''b2c'' AFTER tag_key, ADD COLUMN visibility VARCHAR(8) NOT NULL DEFAULT ''listed'' AFTER is_published, ADD COLUMN featured_rank TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER visibility, ADD COLUMN relates_hq TINYINT(1) NOT NULL DEFAULT 0 AFTER featured_rank, ADD COLUMN file_url VARCHAR(500) NULL DEFAULT NULL AFTER link_url', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'audience');
PREPARE add_event_columns_stmt FROM @add_event_columns;
EXECUTE add_event_columns_stmt;
DEALLOCATE PREPARE add_event_columns_stmt;

CREATE TABLE IF NOT EXISTS `event_relations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `office_id` INT UNSIGNED DEFAULT NULL,
    `country_code` VARCHAR(5) DEFAULT NULL,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
    INDEX `idx_relation_event` (`event_id`),
    INDEX `idx_relation_office` (`office_id`),
    INDEX `idx_relation_country` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `people` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `title` VARCHAR(200) DEFAULT NULL,
    `specialty` VARCHAR(200) DEFAULT NULL,
    `organization` VARCHAR(200) DEFAULT NULL,
    `photo_url` VARCHAR(500) DEFAULT NULL,
    `is_acibadem` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_people_name` (`name`),
    INDEX `idx_people_acibadem` (`is_acibadem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_people` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `person_id` INT UNSIGNED NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'speaker',
    `sort_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`person_id`) REFERENCES `people`(`id`) ON DELETE CASCADE,
    INDEX `idx_event_people_event` (`event_id`, `sort_order`),
    INDEX `idx_event_people_person` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `day` DATE DEFAULT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `kind` VARCHAR(8) NOT NULL DEFAULT 'session',
    `title` VARCHAR(255) NOT NULL,
    `speakers` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    INDEX `idx_session_event` (`event_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
