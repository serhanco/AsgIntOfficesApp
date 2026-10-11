<?php
/**
 * Nearest office (/nearest): the finder as a page of its own. Linked from the map, the office list and the
 * footer; /nearest?find=1 asks for the location right away. A new page, so it only exists in the new design.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/finder-config.php';

$offices = getAllOffices();
$finderConfig = finderConfig($offices);
$pageTitle = __('nav_nearest');
$currentPage = 'nearest';
$needsMap = false;
$metaDescription = __('home_offices_p');

require __DIR__ . '/includes/v2/pages/nearest.php';
