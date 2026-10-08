<?php
/**
 * Acıbadem Micro App - Safe Patch & Backup Script
 *
 * 1. Creates a JSON backup of the data tables in /backups/ (blocked from the web)
 * 2. Applies every database/patch_*.sql file that has not been applied yet.
 *    Applied patches are recorded in the `schema_patches` table, so running
 *    this script again never re-runs an old patch (e.g. one that TRUNCATEs offices).
 *
 * Who can run it:
 *   - From the command line:     php apply_update.php [--force=patch_file.sql]
 *   - A logged-in admin:         /apply_update.php (shows pending patches + Run button)
 *   - With the config key:       /apply_update.php?key=<UPDATE_KEY>  (only if UPDATE_KEY is set in config.php)
 *
 * Re-running an already applied patch (--force / "Yeniden çalıştır") is possible
 * only from the command line or as a logged-in admin.
 */

$isCli = PHP_SAPI === 'cli';

require_once __DIR__ . '/admin/includes/auth.php'; // config, db, session, CSRF check on POST

$keyOk = !$isCli
    && defined('UPDATE_KEY') && is_string(UPDATE_KEY) && UPDATE_KEY !== ''
    && is_string($_GET['key'] ?? null) && hash_equals(UPDATE_KEY, $_GET['key']);
$isAdmin = !$isCli && is_admin_logged_in();

if (!$isCli && !$isAdmin && !$keyOk) {
    http_response_code(403);
    die('<div style="font-family:sans-serif; text-align:center; padding: 50px; color: red;"><h2>Unauthorized</h2><p>Admin olarak giriş yapın: <a href="admin/login.php">admin/login.php</a></p></div>');
}

$db = getDb();
$patchDir = __DIR__ . '/database';

// ------------------------------------------------------------
// Patch tracking
// ------------------------------------------------------------
$trackingExists = (bool)$db->query("SHOW TABLES LIKE 'schema_patches'")->fetchColumn();
if (!$trackingExists) {
    $db->exec("CREATE TABLE IF NOT EXISTS `schema_patches` (
        `filename` VARCHAR(255) NOT NULL PRIMARY KEY,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // First run after tracking was introduced: if offices already hold data, the
    // office data patch was applied before. Mark it so it never wipes live data.
    $officeCount = (int)$db->query("SELECT COUNT(*) FROM offices")->fetchColumn();
    if ($officeCount > 0 && is_file($patchDir . '/patch_offices_data.sql')) {
        $db->prepare("INSERT IGNORE INTO schema_patches (filename) VALUES (?)")->execute(['patch_offices_data.sql']);
    }
}

$applied = $db->query("SELECT filename FROM schema_patches")->fetchAll(PDO::FETCH_COLUMN);
$allPatches = array_map('basename', glob($patchDir . '/patch_*.sql') ?: []);
sort($allPatches);
$pending = array_values(array_diff($allPatches, $applied));

// Which patches to run in this request
$force = null;
if ($isCli) {
    foreach ($argv ?? [] as $arg) {
        if (strpos($arg, '--force=') === 0) $force = substr($arg, 8);
    }
} elseif ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $force = $_POST['force'] ?? null;
}
if ($force !== null && $force !== '' && !in_array($force, $allPatches, true)) {
    die('Bilinmeyen patch: ' . htmlspecialchars((string)$force));
}

$shouldRun = $isCli || $keyOk || ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST');
$toRun = ($force !== null && $force !== '') ? [$force] : $pending;

// ------------------------------------------------------------
// Output helpers
// ------------------------------------------------------------
function out(string $html): void {
    global $isCli;
    echo $isCli ? html_entity_decode(strip_tags($html)) . PHP_EOL : $html;
}

if (!$isCli) {
    echo '<div style="font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 40px;">';
    echo '<h2>⚙️ System Update & Backup</h2><hr><br>';
}

// ------------------------------------------------------------
// Status page (admin GET): list patches and offer a Run button
// ------------------------------------------------------------
if (!$shouldRun) {
    out('<p><b>Bekleyen patch:</b> ' . ($pending ? htmlspecialchars(implode(', ', $pending)) : 'yok') . '</p>');
    if ($pending) {
        echo '<form method="POST">' . csrf_field() . '<button type="submit" style="padding:8px 16px">Yedek al ve bekleyenleri uygula</button></form>';
    }
    if ($applied) {
        echo '<h3>Uygulanmış patch\'ler</h3><ul>';
        foreach ($applied as $f) {
            echo '<li>' . htmlspecialchars($f) . ' <form method="POST" style="display:inline" onsubmit="return confirm(\'Bu patch yeniden çalıştırılacak. Emin misiniz?\');">'
                . csrf_field() . '<input type="hidden" name="force" value="' . htmlspecialchars($f) . '"><button type="submit">Yeniden çalıştır</button></form></li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    exit;
}

if (!$toRun) {
    out('<p>✅ Uygulanacak yeni patch yok. Veritabanı güncel.</p>');
    if (!$isCli) echo '</div>';
    exit;
}

try {
    // ==========================================
    // 1. BACKUP PROCESS
    // ==========================================
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0750, true);
    }
    // Deny web access even if the root .htaccess is missing
    if (!is_file($backupDir . '/.htaccess')) {
        file_put_contents($backupDir . '/.htaccess', "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
    }

    $timestamp = date('Ymd_His');
    $backupFile = $backupDir . '/db_backup_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.json';

    $backupData = [];
    foreach (['offices', 'office_teams', 'office_activities'] as $table) {
        try {
            $backupData[$table] = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {} // Ignore if table doesn't exist yet
    }

    file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    out("<p style='color: green;'>✅ <b>Backup created:</b> backups/" . basename($backupFile) . "</p>");

    // ==========================================
    // 2. APPLY SQL PATCHES
    // ==========================================
    $mark = $db->prepare("REPLACE INTO schema_patches (filename, applied_at) VALUES (?, NOW())");

    foreach ($toRun as $patch) {
        out("<h3>Applying " . htmlspecialchars($patch) . "...</h3>");
        $sql = file_get_contents($patchDir . '/' . $patch);

        // Split by semicolon because PDO might not emulate multiple queries natively on all servers
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        $successCount = 0;
        $failed = false;
        foreach ($statements as $stmtSql) {
            try {
                $db->exec($stmtSql);
                $successCount++;
            } catch (\PDOException $e) {
                $failed = true;
                out("<p style='color: red;'>❌ <b>Error executing statement:</b> " . htmlspecialchars($e->getMessage()) . "</p>");
                out("<pre style='background:#f4f4f4; padding:10px; font-size:12px; overflow-x:auto;'>" . htmlspecialchars(substr($stmtSql, 0, 500)) . (strlen($stmtSql) > 500 ? '...' : '') . "</pre>");
            }
        }

        if ($failed) {
            out("<p style='color: orange;'>⚠️ <b>" . htmlspecialchars($patch) . " hatalarla bitti</b> ($successCount statements executed). Uygulandı olarak işaretlenmedi; düzeltip tekrar çalıştırın.</p>");
        } else {
            $mark->execute([$patch]);
            out("<p style='color: green;'>✅ <b>Patch applied successfully!</b> ($successCount statements executed)</p>");
        }
    }

} catch (\Throwable $e) {
    out("<p style='color: red;'>❌ <b>Critical Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>");
}

if (!$isCli) echo '<br><hr></div>';
