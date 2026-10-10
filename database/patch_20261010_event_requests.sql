-- Event requests sent from the public "request an event" form (and logged when a visitor opens WhatsApp or e-mail from it).
CREATE TABLE IF NOT EXISTS `event_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `type_key` VARCHAR(40) NOT NULL,
    `place` VARCHAR(200) NOT NULL DEFAULT '',
    `contact` VARCHAR(200) NOT NULL DEFAULT '',
    `channel` VARCHAR(12) NOT NULL DEFAULT 'form',
    `lang` VARCHAR(8) NOT NULL DEFAULT '',
    `country` VARCHAR(2) NOT NULL DEFAULT '',
    `status` VARCHAR(12) NOT NULL DEFAULT 'new',
    `note` TEXT NULL,
    `answered_at` DATETIME NULL,
    INDEX `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
