-- Separate WhatsApp number per office. Existing offices start with their phone number copied over.
-- The column is added only when it is missing, so the patch is safe to run on any install.
SET @add_whatsapp = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE offices ADD COLUMN whatsapp VARCHAR(255) NULL DEFAULT NULL AFTER phone', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offices' AND COLUMN_NAME = 'whatsapp');
PREPARE add_whatsapp_stmt FROM @add_whatsapp;
EXECUTE add_whatsapp_stmt;
DEALLOCATE PREPARE add_whatsapp_stmt;
UPDATE offices SET whatsapp = phone WHERE whatsapp IS NULL;
