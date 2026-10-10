-- Site-wide settings edited in the admin (custom header/footer code, tracking ID).
CREATE TABLE IF NOT EXISTS `site_settings` (
    `skey` VARCHAR(64) NOT NULL PRIMARY KEY,
    `svalue` MEDIUMTEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
