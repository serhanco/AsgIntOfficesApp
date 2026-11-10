<?php
/**
 * Sitemap for search engines (/sitemap.xml), built from the database on every request, so it is always current:
 * a new office or event appears as soon as it is saved, a closed office or unpublished event disappears.
 * What is listed is decided in includes/sitemap-data.php; every page is given in all site languages (?lang=xx)
 * plus the bare address (x-default, language from the browser), with hreflang links between the versions.
 * People get the same list as a page at /sitemap.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sitemap-data.php';

$entries = sitemapEntries();

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

// Compress here too (about 2 MB → 20 KB), so it does not depend on the server's mod_deflate.
// ob_gzhandler only compresses when the client asks for it and sets Content-Encoding / Vary itself.
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

$x = fn(string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($entries as $entry) {
    $alternates = seoAlternates($entry['path']);
    $links = '';
    foreach ($alternates as $hreflang => $url) {
        $links .= '    <xhtml:link rel="alternate" hreflang="' . $x($hreflang) . '" href="' . $x($url) . '"/>' . "\n";
    }
    foreach ($alternates as $url) {
        echo "  <url>\n    <loc>" . $x($url) . "</loc>\n";
        if ($entry['lastmod']) echo '    <lastmod>' . $entry['lastmod'] . "</lastmod>\n";
        echo $links . "  </url>\n";
    }
}
echo "</urlset>\n";
