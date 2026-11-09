-- Optional short text for search engines and link previews (WhatsApp, Facebook …) of an event page.
-- Empty: the start of the event description is used, as before.
-- Safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)
SET @add_meta_desc = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE events ADD COLUMN meta_description VARCHAR(255) NULL AFTER description', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'meta_description');
PREPARE add_meta_desc_stmt FROM @add_meta_desc;
EXECUTE add_meta_desc_stmt;
DEALLOCATE PREPARE add_meta_desc_stmt;
