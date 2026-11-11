<?php
if (!defined('APP_INSTALLED')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/functions.php';
}

http_response_code(404);
$pageTitle = __('404_title');
$currentPage = '';
$pageTemplate = 'not_found';
$trackContext = [];
$needsMap = false;
$noIndex = true;
$metaDescription = __('404_subtitle');

require __DIR__ . '/includes/v2/pages/404.php';
