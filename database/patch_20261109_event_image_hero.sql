-- Event cover image: it is always shown whole above the description. This flag also puts it behind the page title
-- (the dark top band), as a dimmed background. Off by default, also for existing events.
-- Safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)
SET @add_image_hero = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE events ADD COLUMN image_hero TINYINT(1) NOT NULL DEFAULT 0 AFTER image_url', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'image_hero');
PREPARE add_image_hero_stmt FROM @add_image_hero;
EXECUTE add_image_hero_stmt;
DEALLOCATE PREPARE add_image_hero_stmt;
