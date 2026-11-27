-- Contributor role: colleagues who log in to the admin only to enter events as drafts (an admin reviews and publishes them).
-- admin_users.role: 'admin' (everything, as before) or 'contributor'. events.created_by: who entered the event.
-- Safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)
SET @add_role = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE admin_users ADD COLUMN role VARCHAR(12) NOT NULL DEFAULT ''admin'' AFTER password_hash', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'role');
PREPARE add_role_stmt FROM @add_role;
EXECUTE add_role_stmt;
DEALLOCATE PREPARE add_role_stmt;
SET @add_created_by = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE events ADD COLUMN created_by INT UNSIGNED NULL DEFAULT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'created_by');
PREPARE add_created_by_stmt FROM @add_created_by;
EXECUTE add_created_by_stmt;
DEALLOCATE PREPARE add_created_by_stmt;
