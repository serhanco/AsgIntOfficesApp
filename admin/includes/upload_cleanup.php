<?php
/**
 * Unused upload cleanup
 * Acıbadem International Offices App
 *
 * Files uploaded from the admin panel (images and PDFs under assets/images/uploads/) stay on the server when an
 * image is replaced or a record is deleted. This removes such files once nothing in the database has pointed
 * at them for a while.
 *
 * A file is deleted only when BOTH are true:
 *   - its name appears in no text column of any table in the database (offices, events, people, settings, …),
 *   - it is older than UPLOAD_CLEANUP_GRACE_DAYS (so a just-replaced image can still be put back, and a
 *     restored database backup from the last weeks still finds its images).
 * When the database cannot be read completely the run stops without deleting anything.
 *
 * It runs by itself at most once a day when an admin opens the dashboard; no cron job is needed.
 */

const UPLOAD_CLEANUP_GRACE_DAYS = 30;
const UPLOAD_CLEANUP_INTERVAL = 86400; // seconds between two automatic runs

/** Absolute path of the uploads folder. */
function upload_cleanup_dir(): string {
    return dirname(__DIR__, 2) . '/assets/images/uploads';
}

/** Names this app gives uploaded files: <subdir>_<YYYYMMDD>_<12 random hex>.<ext>. Anything else in the folder is never touched. */
const UPLOAD_NAME_PATTERN = '/^[a-z0-9_-]+_(\d{8}_[0-9a-f]{12})\.(?:webp|jpe?g|png|pdf)$/i';
/** The unique part of such a name (date + random hex), looked for in database text. */
const UPLOAD_KEY_PATTERN = '/_(\d{8}_[0-9a-f]{12})\./i';

/**
 * The unique part of every uploaded file name mentioned anywhere in the database, as [lowercase key => true].
 * Scans all text-like columns of all tables, so new tables or fields that store an upload are covered automatically.
 * Throws when the database cannot be read, so the caller deletes nothing.
 */
function upload_cleanup_referenced(PDO $db): array {
    $cols = $db->query(
        "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND DATA_TYPE IN ('char','varchar','tinytext','text','mediumtext','longtext','json')"
    )->fetchAll(PDO::FETCH_NUM);
    if (!$cols) {
        throw new RuntimeException('Veritabanı sütunları okunamadı.');
    }

    $quote = fn(string $id) => '`' . str_replace('`', '``', $id) . '`';
    $found = [];
    foreach ($cols as [$table, $col]) {
        $val = 'CAST(' . $quote($col) . ' AS CHAR)';
        $stmt = $db->query("SELECT $val FROM " . $quote($table) . " WHERE $val REGEXP '_[0-9]{8}_[0-9a-f]{12}[.]'");
        while (($v = $stmt->fetchColumn()) !== false) {
            if (preg_match_all(UPLOAD_KEY_PATTERN, (string)$v, $m)) {
                foreach ($m[1] as $key) $found[strtolower($key)] = true;
            }
        }
    }
    return $found;
}

/**
 * Delete uploaded files that nothing refers to and that are older than the grace period.
 * With $dryRun the files are only listed. Returns ['deleted' => [relative paths], 'bytes' => freed bytes, 'kept' => count].
 */
function cleanup_unused_uploads(PDO $db, bool $dryRun = false, int $graceDays = UPLOAD_CLEANUP_GRACE_DAYS): array {
    $result = ['deleted' => [], 'bytes' => 0, 'kept' => 0];
    $dir = upload_cleanup_dir();
    if (!is_dir($dir)) return $result;

    $referenced = upload_cleanup_referenced($db); // throws on any read error: nothing is deleted
    $cutoff = time() - $graceDays * 86400;

    foreach (glob($dir . '/*/*') ?: [] as $path) {
        $name = basename($path);
        if (!is_file($path) || !preg_match(UPLOAD_NAME_PATTERN, $name, $m)) continue;
        if (isset($referenced[strtolower($m[1])]) || (int)@filemtime($path) > $cutoff) {
            $result['kept']++;
            continue;
        }
        $size = (int)@filesize($path);
        if ($dryRun || @unlink($path)) {
            $result['deleted'][] = substr($path, strlen($dir) + 1);
            $result['bytes'] += $size;
        }
    }
    return $result;
}

/** Run the cleanup if the last automatic run was more than a day ago. Never throws; errors only skip the run. */
function maybe_cleanup_unused_uploads(PDO $db): void {
    $marker = upload_cleanup_dir() . '/.last_cleanup';
    if (!is_dir(dirname($marker))) return;
    if (is_file($marker) && time() - (int)@filemtime($marker) < UPLOAD_CLEANUP_INTERVAL) return;
    @touch($marker); // claim the run first so two admins opening the dashboard do not both start it
    try {
        $r = cleanup_unused_uploads($db);
        @file_put_contents($marker, json_encode([
            'at' => date('c'), 'deleted' => count($r['deleted']), 'bytes' => $r['bytes'], 'kept' => $r['kept'],
        ]));
    } catch (Throwable $e) {
        @file_put_contents($marker, json_encode(['at' => date('c'), 'error' => $e->getMessage()]));
    }
}
