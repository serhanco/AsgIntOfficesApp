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
    seoForward($path, 301);
}

/** Has database/patch_20261109_url_redirects.sql been applied? Until then old addresses are simply not remembered. */
function seoRedirectsReady(): bool {
    static $ready = null;
    if ($ready === null) {
        try {
            $ready = (bool)getDb()->query("SHOW TABLES LIKE 'url_redirects'")->fetchColumn();
        } catch (\Throwable $e) {
            $ready = false;
        }
    }
    return $ready;
}

/**
 * Remember that $oldPath now lives at $newPath ('/office/baku' → '/office/baki', or → '/nearest' after a delete).
 * Older addresses that pointed to $oldPath are moved along, so a visitor never goes through a chain of redirects;
 * and if the new address used to be an old one, that old row is dropped (the live page wins anyway).
 */
function seoRememberMove(string $oldPath, string $newPath): void {
    if ($oldPath === $newPath || !seoRedirectsReady()) return;
    try {
        $db = getDb();
        $db->prepare('DELETE FROM url_redirects WHERE old_path = ?')->execute([$newPath]);
        $db->prepare('UPDATE url_redirects SET new_path = ? WHERE new_path = ?')->execute([$newPath, $oldPath]);
        $db->prepare('INSERT INTO url_redirects (old_path, new_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE new_path = VALUES(new_path)')
           ->execute([$oldPath, $newPath]);
    } catch (\Throwable $e) {
        // Never block an admin save because of this
    }
}

/** If $path is a remembered old address, forward (301) to where it lives now, keeping ?lang and other parameters. */
function seoFollowOldAddress(string $path): void {
    if (!seoRedirectsReady()) return;
    try {
        $stmt = getDb()->prepare('SELECT new_path FROM url_redirects WHERE old_path = ? LIMIT 1');
        $stmt->execute([$path]);
        $to = $stmt->fetchColumn();
    } catch (\Throwable $e) {
        return;
    }
    if (!$to) return;
    seoForward((string)$to, 301);
}

/** Send the visitor to a clean path of this site, keeping the query parameters (except the internal slug). */
function seoForward(string $path, int $code): void {
    $params = $_GET;
    unset($params['slug']);
    $query = http_build_query($params);
    header('Location: ' . seoUrl($path) . ($query !== '' ? '?' . $query : ''), true, $code);
    exit;
}

/** Address part used in office and event links: lower case, Latin letters, digits and hyphens. */
function seoSlug(string $text): string {
    $map = ['ş' => 's', 'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'i̇' => 'i', 'ö' => 'o', 'ü' => 'u', 'ə' => 'e', 'ß' => 'ss', 'ä' => 'a', 'â' => 'a',
            'ă' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'ô' => 'o', 'ć' => 'c',
            'č' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z'];
    $text = strtr(mb_strtolower($text, 'UTF-8'), $map);
    if (function_exists('iconv')) $text = (string)@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return generateSlug($text);
}
