<?php
/**
 * Where the server keeps files it creates itself (admin uploads, database backups)
 * Acıbadem International Offices App
 *
 * The live site is deployed from GitHub: on every update of `main` the hosting rebuilds the site folder from the
 * repository and removes everything that is not in it. Files written by the app inside that folder (uploaded event
 * images, people photos, PDFs, database backups) were wiped by every deploy. So they now live in a folder NEXT TO
 * the site folder, which the deploy never touches:
 *
 *   .../domains/acibadem.world/public_html      ← site (rebuilt by each deploy)
 *   .../domains/acibadem.world/asg-storage/     ← uploads/<subdir>/<file>, backups/
 *
 * The database keeps storing upload paths as "assets/images/uploads/<subdir>/<file>", so addresses never change:
 * a request for such a file that is not in the site folder is answered by media.php from the storage folder
 * (see the rule in .htaccess).
 *
 * The folder can be set with STORAGE_DIR in config.php. When the folder next to the site cannot be created, files
 * go to "storage/" inside the site folder (not in git, blocked from the web) and the dashboard shows a warning,
 * because that place is not safe from deploys.
 */

const UPLOADS_URL_PREFIX = 'assets/images/uploads/';

/** Absolute path of the app root (the deployed site folder). */
function app_root_dir(): string {
    return dirname(__DIR__);
}

/** Absolute path of the folder for server-made files; created on first use. */
function storage_dir(): string {
    static $dir = null;
    if ($dir !== null) return $dir;

    $candidates = [];
    if (defined('STORAGE_DIR') && STORAGE_DIR !== '') $candidates[] = rtrim(STORAGE_DIR, '/\\');
    $candidates[] = dirname(app_root_dir()) . '/asg-storage';
    foreach ($candidates as $c) {
        if ((is_dir($c) || @mkdir($c, 0755, true)) && is_writable($c)) return $dir = $c;
    }
    // Last resort inside the site folder (gitignored, denied in .htaccess); not safe from deploys
    $dir = app_root_dir() . '/storage';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

/** True when the storage folder is outside the deployed site folder (i.e. deploys cannot delete it). */
function storage_is_safe(): bool {
    $root = realpath(app_root_dir());
    $dir = realpath(storage_dir());
    return $root && $dir && strpos($dir . '/', $root . '/') !== 0;
}

/** Absolute path of the uploads folder (holds one subfolder per kind: events, people, files, …). */
function uploads_dir(): string {
    return storage_dir() . '/uploads';
}

/** Absolute path of the database backup folder used by apply_update.php. */
function backups_dir(): string {
    return storage_dir() . '/backups';
}

/** The old place of uploads inside the site folder (still served directly by the web server if files are there). */
function legacy_uploads_dir(): string {
    return app_root_dir() . '/' . rtrim(UPLOADS_URL_PREFIX, '/');
}

/**
 * Absolute path of a stored upload path such as "assets/images/uploads/events/x.webp", or null when the path is
 * not an upload or the file is missing. Looks in the storage folder first, then in the old place.
 */
function upload_file_path(?string $relPath): ?string {
    $rel = ltrim(trim((string)$relPath), '/');
    if (strpos($rel, UPLOADS_URL_PREFIX) !== 0) return null;
    $tail = substr($rel, strlen(UPLOADS_URL_PREFIX));
    if ($tail === '' || strpos($tail, '..') !== false || strpos($tail, "\0") !== false) return null;
    foreach ([uploads_dir(), legacy_uploads_dir()] as $base) {
        if (is_file("$base/$tail")) return "$base/$tail";
    }
    return null;
}

/**
 * Move uploads found in the old place (e.g. after restoring a hosting backup of public_html) into the storage
 * folder, keeping their dates. Only files with names this app gives are moved; .htaccess stays.
 * Returns the number of files moved. Never throws.
 */
function migrate_legacy_uploads(): int {
    if (!storage_is_safe()) return 0;
    $moved = 0;
    foreach (glob(legacy_uploads_dir() . '/*/*') ?: [] as $path) {
        $name = basename($path);
        if (!is_file($path) || $name[0] === '.' || !preg_match('/\.(webp|jpe?g|png|pdf)$/i', $name)) continue;
        $sub = basename(dirname($path));
        $destDir = uploads_dir() . '/' . $sub;
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) continue;
        $dest = "$destDir/$name";
        if (is_file($dest)) { // already there (e.g. moved earlier): keep the copy in storage
            @unlink($path);
            continue;
        }
        $mtime = (int)@filemtime($path);
        if (@rename($path, $dest) || (@copy($path, $dest) && @unlink($path))) {
            if ($mtime) @touch($dest, $mtime);
            $moved++;
        }
    }
    return $moved;
}
