-- Events whose registration/details live on an outside page: cards and lists link straight to that page (link_url).
-- After the event ends, our own short event page opens instead (outside pages often close).
-- Safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)
SET @add_external = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE events ADD COLUMN external_only TINYINT(1) NOT NULL DEFAULT 0 AFTER link_url', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'external_only');
PREPARE add_external_stmt FROM @add_external;
EXECUTE add_external_stmt;
DEALLOCATE PREPARE add_external_stmt;
