<?php
/**
 * Language & i18n Helper
 * Acıbadem International Offices App
 *
 * Priority chain:
 *   1. ?lang= URL param  (user explicit choice, saved to a cookie)
 *   2. user_lang cookie  (persisted preference)
 *   3. HTTP Accept-Language header (the browser/device language list, in q order)
 *   4. 'en' fallback
 *
 * Missing keys in a language file fall back to English.
 */

/**
 * Supported languages, in the order shown in the switcher.
 *   name     native name shown in the switcher
 *   flag     flagcdn.com country code for the switcher flag
 *   dir      text direction of the UI in this language
 *   html     value for <html lang>
 */
define('LANGUAGES', [
    'en' => ['name' => 'English',         'flag' => 'gb', 'dir' => 'ltr', 'html' => 'en'],
    'tr' => ['name' => 'Türkçe',          'flag' => 'tr', 'dir' => 'ltr', 'html' => 'tr'],
    'de' => ['name' => 'Deutsch',         'flag' => 'de', 'dir' => 'ltr', 'html' => 'de'],
    'fr' => ['name' => 'Français',        'flag' => 'fr', 'dir' => 'ltr', 'html' => 'fr'],
    'ru' => ['name' => 'Русский',         'flag' => 'ru', 'dir' => 'ltr', 'html' => 'ru'],
    'uk' => ['name' => 'Українська',      'flag' => 'ua', 'dir' => 'ltr', 'html' => 'uk'],
    'ar' => ['name' => 'العربية',         'flag' => 'sa', 'dir' => 'rtl', 'html' => 'ar'],
    'fa' => ['name' => 'فارسی',           'flag' => 'ir', 'dir' => 'rtl', 'html' => 'fa'],
    'az' => ['name' => 'Azərbaycan dili', 'flag' => 'az', 'dir' => 'ltr', 'html' => 'az'],
    'ka' => ['name' => 'ქართული',         'flag' => 'ge', 'dir' => 'ltr', 'html' => 'ka'],
    'ro' => ['name' => 'Română',          'flag' => 'ro', 'dir' => 'ltr', 'html' => 'ro'],
    'bg' => ['name' => 'Български',       'flag' => 'bg', 'dir' => 'ltr', 'html' => 'bg'],
    'sq' => ['name' => 'Shqip',           'flag' => 'al', 'dir' => 'ltr', 'html' => 'sq'],
    'sr' => ['name' => 'Srpski',          'flag' => 'rs', 'dir' => 'ltr', 'html' => 'sr-Latn'],
    'bs' => ['name' => 'Bosanski',        'flag' => 'ba', 'dir' => 'ltr', 'html' => 'bs'],
    'hr' => ['name' => 'Hrvatski',        'flag' => 'hr', 'dir' => 'ltr', 'html' => 'hr'],
    'mk' => ['name' => 'Македонски',      'flag' => 'mk', 'dir' => 'ltr', 'html' => 'mk'],
]);
define('SUPPORTED_LANGS', array_keys(LANGUAGES));
define('DEFAULT_LANG', 'en');

/**
 * Browser language subtags that should open one of our languages.
 * (Montenegrin and old Serbo-Croatian read Srpski; Dari reads Persian.)
 */
define('LANG_ALIASES', ['cnr' => 'sr', 'sh' => 'sr', 'prs' => 'fa']);

/**
 * Pick the best supported language from an Accept-Language header,
 * honouring q-values ("ru-RU,ru;q=0.9,en;q=0.8" → 'ru').
 */
function detectLangFromHeader(string $header): ?string {
    $candidates = [];
    foreach (explode(',', $header) as $i => $entry) {
        $parts = explode(';', trim($entry));
        $tag = strtolower(trim($parts[0]));
        if ($tag === '' || $tag === '*') continue;
        $q = 1.0;
        foreach (array_slice($parts, 1) as $param) {
            if (preg_match('/^\s*q\s*=\s*([0-9.]+)\s*$/i', $param, $m)) $q = (float)$m[1];
        }
        if ($q <= 0) continue;
        $candidates[] = ['tag' => $tag, 'q' => $q, 'i' => $i];
    }
    // Highest q first; keep header order for equal q
    usort($candidates, fn($a, $b) => [$b['q'], $a['i']] <=> [$a['q'], $b['i']]);

    foreach ($candidates as $c) {
        $base = explode('-', $c['tag'])[0];
        $base = LANG_ALIASES[$base] ?? $base;
        if (in_array($base, SUPPORTED_LANGS, true)) return $base;
    }
    return null;
}

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
        // Persist to cookie (1 year) — no output yet, headers not sent
        if (!headers_sent()) {
            setcookie('user_lang', $lang, ['expires' => time() + 365 * 24 * 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        }
    }

    // 2. Cookie
    if ($lang === null && isset($_COOKIE['user_lang']) && in_array($_COOKIE['user_lang'], $supported, true)) {
        $lang = $_COOKIE['user_lang'];
    }

    // 3. Browser / device language list
    if ($lang === null && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = detectLangFromHeader($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    // 4. Fallback
    if ($lang === null) {
        $lang = DEFAULT_LANG;
    }

    // The page depends on these request headers; tell caches so
    if (!headers_sent()) {
        header('Vary: Accept-Language, Cookie', false);
    }

    // Load translations, English underneath so a missing key never shows raw
    $translations = require __DIR__ . '/../lang/' . DEFAULT_LANG . '.php';
    if ($lang !== DEFAULT_LANG) {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        if (file_exists($file)) {
            $translations = array_merge($translations, require $file);
        }
    }

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

/** Text direction of the current UI language: 'ltr' or 'rtl'. */
function langDir(): string {
    return LANGUAGES[$GLOBALS['current_lang']]['dir'] ?? 'ltr';
}

/** Value for <html lang> of the current UI language. */
function langHtml(): string {
    return LANGUAGES[$GLOBALS['current_lang']]['html'] ?? $GLOBALS['current_lang'];
}

/** "Forward" arrow icon for the current UI direction (points left in RTL). */
function arrowIcon(): string {
    return langDir() === 'rtl' ? 'ph-arrow-left' : 'ph-arrow-right';
}

/**
 * Direction of free text entered in the admin panel (events, names...),
 * taken from its first letter: Arabic/Persian/Hebrew text is 'rtl'.
 * Lets local-language content sit correctly inside any UI language.
 */
function textDir(?string $text): string {
    if ($text !== null && preg_match('/\p{L}/u', $text, $m)) {
        return preg_match('/[\p{Arabic}\p{Hebrew}\p{Syriac}\p{Thaana}\p{Nko}]/u', $m[0]) ? 'rtl' : 'ltr';
    }
    return langDir();
}

// Shorthand for current language code (used in header <html lang="...">)
$current_lang = $GLOBALS['current_lang'];
