<?php
/** API documentation for developers (/api). English only, kept out of search results; the footer API link points here. */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$seoPath = '/api';
seoRedirectToClean($seoPath);

$pageTitle = 'Offices API';
$currentPage = 'api-docs';
$metaDescription = 'Public JSON API with the contact details of the Acıbadem international offices.';
$noIndex = true;

// Live numbers and a real sample record, so the page never goes stale
$offices = getAllOffices(true);
$officeCount = count($offices);
$countryCount = count(array_unique(array_column($offices, 'country_code')));
$sample = $offices ? apiOfficePayload($offices[0]) : null;
$endpoint = rtrim(getBaseUrl(), '/') . '/api/offices';
$sampleJson = json_encode([
    'success' => true,
    'count' => $officeCount,
    'generated_at' => date('c'),
    'data' => $sample ? [$sample, '...'] : [],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$sampleJson = str_replace('"..."', '...', $sampleJson);

require __DIR__ . '/includes/v2/pages/api-docs.php';
