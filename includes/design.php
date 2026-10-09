<?php
/**
 * Site design switch: the redesigned public pages (includes/v2) run next to the
 * current ones until they are approved.
 *
 *   ?design=v2  opens the new design and remembers it in this browser (cookie)
 *   ?design=v1  goes back to the current design
 *
 * DESIGN_LIVE is what visitors without the cookie see. Flipping it to 'v2'
 * publishes the new design for everyone.
 */
const DESIGN_LIVE   = 'v1';
const DESIGN_COOKIE = 'asg_design';

function designVersion(): string {
    static $version = null;
    if ($version !== null) return $version;

    $asked = $_GET['design'] ?? null;
    if ($asked === 'v1' || $asked === 'v2') {
        if (!headers_sent()) {
            setcookie(DESIGN_COOKIE, $asked, [
                'expires'  => time() + 60 * 24 * 3600,
                'path'     => '/',
                'httponly' => true,
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
        }
        return $version = $asked;
    }
    $saved = $_COOKIE[DESIGN_COOKIE] ?? '';
    return $version = in_array($saved, ['v1', 'v2'], true) ? $saved : DESIGN_LIVE;
}

function designV2(): bool {
    return designVersion() === 'v2';
}

/** True while the new design is only a preview (not yet what visitors see). */
function designPreview(): bool {
    return designV2() && DESIGN_LIVE !== 'v2';
}

/**
 * URL of a local asset with a cache-busting version from its content
 * (scripts/build-icons.mjs writes the same version into icons.css for the icon fonts).
 */
function asset(string $path): string {
    static $versions = [];
    $path = ltrim($path, '/');
    $file = __DIR__ . '/../' . $path;
    if (!isset($versions[$path])) $versions[$path] = is_file($file) ? substr(md5_file($file), 0, 8) : '';
    return getBaseUrl() . '/' . $path . ($versions[$path] !== '' ? '?v=' . $versions[$path] : '');
}
