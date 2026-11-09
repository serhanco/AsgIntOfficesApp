<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$seoPath = '/offices';
seoRedirectToClean($seoPath);

$offices = getAllOffices();
$countries = getCountriesWithCount();
$totalOffices = count($offices);
$totalCountries = count($countries);

$pageTitle = __('offices_title');
$currentPage = 'offices';
$needsMap = false;
$metaDescription = __('offices_subtitle', $totalOffices, $totalCountries);

// Group by country
$grouped = [];
foreach ($offices as $o) {
    $grouped[$o['country']][] = $o;
}
ksort($grouped);

require __DIR__ . '/includes/v2/pages/offices.php';
