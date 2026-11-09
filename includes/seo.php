<?php
/**
 * Addresses for search engines: canonical address, language versions (hreflang) and old-address forwarding.
 *
 * Every public page has one clean address (/offices, /office/{slug}, /event/{slug} …).
 * Language versions are that address with ?lang=xx; the bare address picks the language from the visitor's
 * browser and is the x-default version. The same helpers build the <head> links and sitemap.xml, so both
 * always say the same thing.
 */

/** Full address of a clean path ('/', '/offices', '/office/baku'), optionally in one language. */
function seoUrl(string $path, ?string $lang = null): string {
    $url = getBaseUrl() . ($path === '/' ? '/' : $path);
    return $lang !== null ? $url . '?lang=' . $lang : $url;
}

/** hreflang => address for every language version of a page, plus x-default (the bare address). */
function seoAlternates(string $path): array {
    $out = [];
    foreach (LANGUAGES as $code => $l) {
        $out[$l['html']] = seoUrl($path, $code);
    }
    $out['x-default'] = seoUrl($path);
    return $out;
}

/**
 * Canonical address of the page being shown: its clean path, keeping only ?lang= (other parameters such as
 * ?view=calendar or ?find=1 show the same content and point back to the plain page).
 */
function seoCanonical(string $path): string {
    $lang = $_GET['lang'] ?? null;
    return seoUrl($path, is_string($lang) && in_array($lang, SUPPORTED_LANGS, true) ? $lang : null);
}

/** <link rel="canonical"> and <link rel="alternate" hreflang> tags for the <head>. */
function seoHeadLinks(string $path): string {
    $html = '<link rel="canonical" href="' . e(seoCanonical($path)) . '">' . "\n";
    foreach (seoAlternates($path) as $hreflang => $url) {
        $html .= '    <link rel="alternate" hreflang="' . e($hreflang) . '" href="' . e($url) . '">' . "\n";
    }
    return $html;
}

/**
 * Old or duplicate addresses of a page (/offices.php, /office.php?slug=baku, /offices/, /index.php …) forward
 * with a permanent redirect (301) to the clean address, keeping the other parameters (?lang, ?find, utm_* …),
 * so links and QR codes already in circulation keep working and count for one address.
 */
function seoRedirectToClean(string $path): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') return;
    if (PHP_SAPI === 'cli') return;

    $requested = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $basePath = rtrim((string)parse_url(getBaseUrl(), PHP_URL_PATH), '/');
    $clean = $basePath . ($path === '/' ? '/' : $path);
    if ($requested === $clean || rawurldecode($requested) === $clean) return;

    $params = $_GET;
    unset($params['slug']);
    $query = http_build_query($params);
    header('Location: ' . seoUrl($path) . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}
