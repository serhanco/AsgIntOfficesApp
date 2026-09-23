CREATE TABLE IF NOT EXISTS `office_teams` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `office_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `role_key` VARCHAR(100) NOT NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `is_online` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
    INDEX `idx_office_team` (`office_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
