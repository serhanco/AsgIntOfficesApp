<?php
/**
 * Serves admin uploads from the storage folder outside the site folder (see includes/storage.php).
 * .htaccess sends a request for assets/images/uploads/<subdir>/<file> here when the file is not in the site folder,
 * so image and PDF addresses stay the same as before.
 */
require_once __DIR__ . '/includes/storage.php';

$f = (string)($_GET['f'] ?? '');
$types = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
$path = null;
if (preg_match('#^([a-z0-9_-]+)/([a-z0-9][a-z0-9._-]*)\.([a-z]+)$#i', $f, $m) && isset($types[strtolower($m[3])])) {
    $candidate = uploads_dir() . '/' . $f;
    if (is_file($candidate)) $path = $candidate;
}

if ($path === null) {
    require __DIR__ . '/404.php';
    exit;
}

$mtime = (int)filemtime($path);
$size = (int)filesize($path);
$etag = '"' . dechex($mtime) . '-' . dechex($size) . '"';
$ext = strtolower($m[3]);

header('Content-Type: ' . $types[$ext]);
header('X-Content-Type-Options: nosniff');
// File names are unique (a replaced image gets a new name), so they can be cached for long
header('Cache-Control: public, max-age=2592000');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);
if ($ext === 'pdf') header('Content-Disposition: inline; filename="' . basename($path) . '"');

$inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$ims = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
if (($inm !== '' && trim($inm) === $etag) || ($inm === '' && $ims !== '' && strtotime($ims) >= $mtime)) {
    http_response_code(304);
    exit;
}

header('Content-Length: ' . $size);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') exit;
readfile($path);
