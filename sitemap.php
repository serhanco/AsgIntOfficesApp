<?php
/**
 * Sitemap for search engines (/sitemap.xml), built from the database on every request, so it is always current:
 * a new office or event appears as soon as it is saved, a closed office or unpublished event disappears.
 *
 * Contents: the public pages, every active office and every listed, published event, each in all site
 * languages (?lang=xx) plus the bare address (x-default, language from the browser), with hreflang links
 * between the versions. Left out: admin, API and setup addresses, link-only and internal events, events
 * that currently forward to an outside registration page, and test/preview addresses (?demo, ?cc, ?at, ?page).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

/** Y-m-d of a database timestamp, or null. */
function sitemapDate($ts): ?string {
    $t = $ts ? strtotime((string)$ts) : false;
    return $t ? date('Y-m-d', $t) : null;
}

$offices = getAllOffices();
$events = array_values(array_filter(getEvents(), fn($ev) => !$ev['href_ext'] && $ev['visibility'] === 'listed'));

// Last change of each event (events.updated_at); its stops count too
$eventDates = [];
if ($events) {
    try {
        foreach (getDb()->query('SELECT id, updated_at FROM events')->fetchAll() as $r) $eventDates[(int)$r['id']] = sitemapDate($r['updated_at']);
        foreach (getDb()->query('SELECT event_id, MAX(updated_at) AS u FROM event_locations GROUP BY event_id')->fetchAll() as $r) {
            $d = sitemapDate($r['u']);
            $id = (int)$r['event_id'];
            if ($d && (!isset($eventDates[$id]) || $d > $eventDates[$id])) $eventDates[$id] = $d;
        }
    } catch (\Throwable $e) {
        // Dates are optional; the addresses are listed without them
    }
}

$latest = fn(array $dates) => ($dates = array_filter($dates)) ? max($dates) : null;
$latestOffice = $latest(array_map(fn($o) => sitemapDate($o['updated_at'] ?? null), $offices));
$latestEvent = $latest(array_map(fn($ev) => $eventDates[$ev['id']] ?? null, $events));
$latestAny = $latest([$latestOffice, $latestEvent]);

// [path, last change]
$pages = [
    ['/', $latestAny],
    ['/offices', $latestOffice],
    ['/map', $latestOffice],
    ['/nearest', $latestOffice],
    ['/events', $latestEvent],
    ['/contracted-institutions', null],
];
foreach ($offices as $o) {
    $pages[] = ['/office/' . $o['slug'], sitemapDate($o['updated_at'] ?? null)];
}
foreach ($events as $ev) {
    $pages[] = ['/event/' . $ev['slug'], $eventDates[$ev['id']] ?? null];
}

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

$x = fn(string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($pages as [$path, $lastmod]) {
    $alternates = seoAlternates($path);
    $links = '';
    foreach ($alternates as $hreflang => $url) {
        $links .= '    <xhtml:link rel="alternate" hreflang="' . $x($hreflang) . '" href="' . $x($url) . '"/>' . "\n";
    }
    foreach ($alternates as $url) {
        echo "  <url>\n    <loc>" . $x($url) . "</loc>\n";
        if ($lastmod) echo '    <lastmod>' . $lastmod . "</lastmod>\n";
        echo $links . "  </url>\n";
    }
}
echo "</urlset>\n";
