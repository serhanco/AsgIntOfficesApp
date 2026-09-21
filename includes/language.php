<?php
/**
 * Language & i18n Helper
 * Acıbadem International Offices App
 *
 * Priority chain:
 *   1. ?lang= URL param  (user explicit choice)
 *   2. user_lang cookie  (persisted preference)
 *   3. HTTP Accept-Language header (browser/device auto-detect)
 *   4. 'en' fallback
 */

define('SUPPORTED_LANGS', ['en', 'ru']);
define('DEFAULT_LANG', 'en');

/**
 * Detect and persist language, load translations.
 * Sets global $current_lang and $__translations.
 */
(function () {
    $supported = SUPPORTED_LANGS;
    $lang = null;

    // 1. URL parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], $supported, true)) {
        $lang = $_GET['lang'];
        // Persist to cookie (30 days) — no output yet, headers not sent
        setcookie('user_lang', $lang, time() + 30 * 24 * 3600, '/', '', false, true);
    }

    // 2. Cookie
    if ($lang === null && isset($_COOKIE['user_lang']) && in_array($_COOKIE['user_lang'], $supported, true)) {
        $lang = $_COOKIE['user_lang'];
    }

    // 3. HTTP Accept-Language auto-detect
    if ($lang === null && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        // Parse "ru-RU,ru;q=0.9,en;q=0.8" → ['ru', 'en', ...]
        $accepted = preg_split('/\s*,\s*/', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($accepted as $entry) {
            $tag = strtolower(substr(trim($entry), 0, 2));
            if (in_array($tag, $supported, true)) {
                $lang = $tag;
                break;
            }
        }
    }

    // 4. Fallback
    if ($lang === null) {
        $lang = DEFAULT_LANG;
    }

    // Load translation array
    $file = __DIR__ . '/../lang/' . $lang . '.php';
    if (!file_exists($file)) {
        $file = __DIR__ . '/../lang/' . DEFAULT_LANG . '.php';
        $lang = DEFAULT_LANG;
    }
    $translations = require $file;

    // Expose to global scope
    $GLOBALS['current_lang']    = $lang;
    $GLOBALS['__translations']  = $translations;
})();

/**
 * Translate a key.
 * Supports sprintf-style placeholders: __('offices_subtitle', 47, 30)
 *
 * @param string $key
 * @param mixed  ...$args  Optional sprintf arguments
 * @return string
 */
function __($key, ...$args): string
{
    $t = $GLOBALS['__translations'][$key] ?? $key;
    if (!empty($args)) {
        return vsprintf($t, $args);
    }
    return $t;
}

// Shorthand for current language code (used in header <html lang="...">)
$current_lang = $GLOBALS['current_lang'];
