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
require_once __DIR__ . '/includes/office_photos.php';
// Prefer an office with photos as the sample, so the photo fields show real values
$photosByOffice = office_photos_for(array_column($offices, 'id'));
$sampleOffice = $offices[0] ?? null;
foreach ($offices as $o) { if (!empty($photosByOffice[(int)$o['id']])) { $sampleOffice = $o; break; } }
$sample = $sampleOffice ? apiOfficePayload($sampleOffice, $photosByOffice[(int)$sampleOffice['id']] ?? []) : null;
$endpoint = rtrim(getBaseUrl(), '/') . '/api/offices';
$sampleJson = json_encode([
    'success' => true,
    'count' => $officeCount,
    'generated_at' => date('c'),
    'data' => $sample ? [$sample, '...'] : [],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$sampleJson = str_replace('"..."', '...', $sampleJson);

require __DIR__ . '/includes/v2/pages/api-docs.php';
