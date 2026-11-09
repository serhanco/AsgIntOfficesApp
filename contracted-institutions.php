<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$seoPath = '/contracted-institutions';
seoRedirectToClean($seoPath);

$pageTitle = __('inst_title');
$currentPage = 'contracted-institutions';
$metaDescription = __('inst_subtitle');

$institutions = require __DIR__ . '/includes/institutions.php';

require __DIR__ . '/includes/v2/pages/institutions.php';
