-- Events as a second main entity next to offices.
-- An event has one or more stops (event_locations). A stop is held at an office,
-- or at another venue (hotel, congress centre, fair) with the office as its contact.
-- Existing office_activities rows are copied as single-stop events. The old table stays as it is.
-- (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)

CREATE TABLE IF NOT EXISTS `office_activities` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `office_id` INT UNSIGNED NOT NULL,
    `tag_key` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `activity_date` DATE DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
    INDEX `idx_office_activity` (`office_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(190) NOT NULL,
    `tag_key` VARCHAR(100) NOT NULL DEFAULT 'act_tag_doctor',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `link_url` VARCHAR(500) DEFAULT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `legacy_activity_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_event_slug` (`slug`),
    UNIQUE KEY `uk_event_legacy` (`legacy_activity_id`),
    INDEX `idx_event_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_locations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `office_id` INT UNSIGNED DEFAULT NULL,
    `venue_name` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(120) DEFAULT NULL,
    `country_code` VARCHAR(5) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `latitude` DECIMAL(10,7) DEFAULT NULL,
    `longitude` DECIMAL(10,7) DEFAULT NULL,
    `is_online` TINYINT(1) NOT NULL DEFAULT 0,
    `starts_on` DATE DEFAULT NULL,
    `ends_on` DATE DEFAULT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL,
    INDEX `idx_location_event` (`event_id`, `sort_order`),
    INDEX `idx_location_office` (`office_id`),
    INDEX `idx_location_dates` (`starts_on`, `ends_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `events` (`slug`, `tag_key`, `title`, `description`, `sort_order`, `is_published`, `legacy_activity_id`)
SELECT CONCAT('event-', a.`id`), a.`tag_key`, a.`title`, a.`description`, a.`sort_order`, 1, a.`id`
FROM `office_activities` a;

INSERT INTO `event_locations` (`event_id`, `office_id`, `starts_on`)
SELECT e.`id`, a.`office_id`, a.`activity_date`
FROM `office_activities` a
JOIN `events` e ON e.`legacy_activity_id` = a.`id`
WHERE NOT EXISTS (SELECT 1 FROM `event_locations` l WHERE l.`event_id` = e.`id`);
