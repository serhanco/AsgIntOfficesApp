-- Office photos: up to 5 per office, managed in the admin office screen and shared in the offices API.
-- path = large copy (up to 1600 px), path_md / path_sm = 800 px and 400 px copies (empty for photos copied from the old field).
-- The cover photo is also kept in offices.image_url (the top image of the office page).
CREATE TABLE IF NOT EXISTS `office_photos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `office_id` INT UNSIGNED NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `path_md` VARCHAR(500) DEFAULT NULL,
    `path_sm` VARCHAR(500) DEFAULT NULL,
    `width` INT UNSIGNED DEFAULT NULL,
    `height` INT UNSIGNED DEFAULT NULL,
    `alt_text` VARCHAR(200) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_office_order` (`office_id`, `sort_order`),
    FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The image each office already has becomes its first photo and cover, so nothing changes on the site
INSERT INTO `office_photos` (`office_id`, `path`, `sort_order`, `is_cover`)
SELECT o.`id`, o.`image_url`, 0, 1 FROM `offices` o
WHERE o.`image_url` IS NOT NULL AND TRIM(o.`image_url`) <> ''
  AND NOT EXISTS (SELECT 1 FROM `office_photos` p WHERE p.`office_id` = o.`id`);
