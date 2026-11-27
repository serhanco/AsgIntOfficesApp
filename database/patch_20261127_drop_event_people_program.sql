-- Removes event participants (Katılımcılar), the shared people list (Kişiler) and the optional day-by-day program.
-- Serhan 2026-11-27: these are not needed. The site and the admin stopped reading and writing these tables
-- in the same release, so this patch can be applied any time after it is live.
-- apply_update.php backs up people, event_people and event_sessions (JSON) before running it.
-- Safe to run twice. (Keep this file free of semicolons except at the end of statements: apply_update.php splits on them.)
DROP TABLE IF EXISTS `event_people`;
DROP TABLE IF EXISTS `event_sessions`;
DROP TABLE IF EXISTS `people`;
