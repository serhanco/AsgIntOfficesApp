<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/finder-config.php';

$offices = getAllOffices();
$pageTitle = __('home_title');
$currentPage = 'home';
$needsMap = false;
$metaDescription = __('site_name') . ' - ' . __('meta_default');

$finderConfig = finderConfig($offices);
$serverCountry = $finderConfig['serverCountry'];
$testCountry = $finderConfig['testCountry'];

require __DIR__ . '/includes/v2/pages/home.php';
