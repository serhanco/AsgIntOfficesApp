-- Old addresses that must keep working: when an office or event address (slug) is changed in the admin,
-- or the record is deleted, the old address is saved here and forwards (301) to the new one.
-- QR codes and ad links already in circulation keep working. Rows are written by the admin automatically.
-- Safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)
CREATE TABLE IF NOT EXISTS `url_redirects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `old_path` VARCHAR(255) NOT NULL,
    `new_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_old_path` (`old_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
