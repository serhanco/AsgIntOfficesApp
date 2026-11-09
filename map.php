<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$seoPath = '/map';
seoRedirectToClean($seoPath);

$offices = getAllOffices();
$pageTitle = __('map_title');
$currentPage = 'map';
$needsMap = true;
$metaDescription = __('map_floating_p');

require __DIR__ . '/includes/v2/pages/map.php';
