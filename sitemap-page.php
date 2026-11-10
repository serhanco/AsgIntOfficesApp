<?php
/** Sitemap for people (/sitemap): the same list as /sitemap.xml, as links. */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sitemap-data.php';

$seoPath = '/sitemap';
seoRedirectToClean($seoPath);

$pageTitle = __('sitemap_title');
$currentPage = 'sitemap';
$metaDescription = __('sitemap_subtitle');
$noIndex = true; // the list for search engines is /sitemap.xml

$groups = ['pages' => [], 'offices' => [], 'events' => []];
foreach (sitemapEntries() as $entry) {
    if (isset($groups[$entry['group']])) $groups[$entry['group']][] = $entry;
}
$officesByCountry = [];
foreach ($groups['offices'] as $entry) $officesByCountry[$entry['country']][] = $entry;
ksort($officesByCountry);

require __DIR__ . '/includes/v2/pages/sitemap.php';
