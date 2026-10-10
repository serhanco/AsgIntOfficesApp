<?php
// Ensure variables have default values if not set
$pageTitle       = $pageTitle ?? __('site_name');
$currentPage     = $currentPage ?? 'home';
$needsMap        = $needsMap ?? false;
$metaDescription = $metaDescription ?? __('meta_default');
$ogImage         = $ogImage ?? (getBaseUrl() . '/assets/images/og-logo.png');

// Current lang is set by language.php (already required via functions.php)
$current_lang = $GLOBALS['current_lang'] ?? 'en';

// Build lang switcher URL helper
function langUrl(string $lang): string {
    $params = $_GET;
    $params['lang'] = $lang;
    $qs = http_build_query($params);
    return strtok($_SERVER['REQUEST_URI'], '?') . '?' . $qs;
}

// Language menu: flag + name pill that opens a two-column list (same on desktop and mobile)
function renderLangMenu(): void {
    $current = $GLOBALS['current_lang'];
    $cur = LANGUAGES[$current];
    echo '<div class="relative" data-lang-menu>';
    echo '<button type="button" class="flex items-center gap-2 px-2.5 sm:px-3 py-1.5 rounded-full border border-white/20 text-sm font-medium text-white hover:bg-white/10 transition-colors" aria-haspopup="true" aria-expanded="false" aria-label="' . e(__('lang_switcher_label')) . '" data-lang-toggle>'
       . '<img src="https://flagcdn.com/w40/' . e($cur['flag']) . '.png" alt="" width="20" height="15" class="w-5 h-[15px] object-cover rounded-[3px] flex-shrink-0">'
       . '<span class="hidden sm:inline lg:hidden xl:inline" lang="' . e($cur['html']) . '" dir="' . e($cur['dir']) . '">' . e($cur['name']) . '</span>'
       . '<span class="hidden lg:inline xl:hidden">' . strtoupper(e($current)) . '</span>'
       . '<i class="ph ph-caret-down text-xs"></i>'
       . '</button>';
    echo '<div class="hidden fixed inset-x-3 top-[4.5rem] lg:absolute lg:inset-x-auto lg:end-0 lg:top-full lg:mt-3 lg:w-[26rem] z-50 max-h-[75vh] overflow-y-auto bg-white rounded-2xl shadow-2xl border border-gray-100 p-2" data-lang-panel>';
    echo '<ul class="grid grid-cols-2 gap-1">';
    foreach (LANGUAGES as $code => $l) {
        $active = $code === $current;
        $cls = $active ? 'bg-gray-50 text-[#0c2d74] font-bold' : 'text-gray-800 hover:bg-gray-50 hover:text-[#0c2d74] font-medium';
        echo '<li><a href="' . e(langUrl($code)) . '" hreflang="' . e($l['html']) . '"'
           . ($active ? ' aria-current="true"' : '')
           . ' class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-2.5 rounded-xl text-sm transition-colors ' . $cls . '">'
           . '<img src="https://flagcdn.com/w40/' . e($l['flag']) . '.png" alt="" width="24" height="18" class="w-6 h-[18px] object-cover rounded-[4px] shadow-sm ring-1 ring-black/5 flex-shrink-0" loading="lazy">'
           . '<span lang="' . e($l['html']) . '" dir="' . e($l['dir']) . '" class="truncate">' . e($l['name']) . '</span>'
           . '</a></li>';
    }
    echo '</ul></div></div>';
}
?>
<!DOCTYPE html>
<html lang="<?= e(langHtml()) ?>" dir="<?= langDir() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= __('site_name') ?></title>
    
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> | <?= __('site_name') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= getBaseUrl() ?>/assets/images/favicon.png">
    <link rel="icon" type="image/x-icon" href="<?= getBaseUrl() ?>/assets/images/favicon.ico">
    
    <?php $ga4Id = ga4Id(); ?>
    <?php if ($ga4Id !== '' && !isAdminVisitor()): ?>
    <!-- Google tag (gtag.js) — set define('GA4_ID', '') in config.php to turn off -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4Id) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= e($ga4Id) ?>');
    </script>
    <?php endif; ?>

    <!-- Google Fonts (Arabic/Persian and Georgian faces only download when such text is on the page) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;500;600;700;800&family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (prebuilt: run `npm run build:css` after changing classes) -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.2"></script>
    
    <?php if ($needsMap): ?>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <?php endif; ?>
    
    <!-- UI strings used by assets/js/app.js -->
    <script>window.APP_I18N = <?= json_encode([
        'geoUnsupported' => __('js_geo_unsupported'),
        'geoDenied'      => __('js_geo_denied'),
        'viewDetails'    => __('map_view_details'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/style.css">
    <?= customCode('head_code') ?>
</head>
<body class="font-sans bg-gray-50 min-h-screen antialiased">
    <?= customCode('body_code') ?>
    <!-- Top accent bar -->
    <div class="h-[3px] w-full bg-gradient-to-r from-[#0c2d74] to-[#1a4ba0]"></div>
    
    <!-- Header / Nav -->
    <header class="bg-acibadem-blue sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink min-w-0">
                    <a href="<?= getBaseUrl() ?>/" class="flex items-center">
                        <img class="h-6 sm:h-8 w-auto max-w-full object-contain" src="<?= getBaseUrl() ?>/assets/images/acibadem-white-logo.webp" alt="Acıbadem Logo">
                    </a>
                </div>
                
                <!-- Desktop Nav -->
                <nav class="hidden lg:flex items-center gap-1">
                    <a href="<?= getBaseUrl() ?>/?find=1" class="flex items-center px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors <?= $currentPage === 'home' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        <i class="ph ph-target text-lg me-2"></i><?= __('nav_nearest') ?>
                    </a>
                    <a href="<?= getBaseUrl() ?>/map" class="flex items-center px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors <?= $currentPage === 'map' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        <i class="ph ph-globe-hemisphere-west text-lg me-2"></i><?= __('nav_map') ?>
                    </a>
                    <a href="<?= getBaseUrl() ?>/offices" class="flex items-center px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors <?= ($currentPage === 'offices' || $currentPage === 'office') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        <i class="ph ph-buildings text-lg me-2"></i><?= __('nav_all_offices') ?>
                    </a>

                    <div class="ms-3"><?php renderLangMenu(); ?></div>
                </nav>
                
                <!-- Mobile Nav Toggle -->
                <div class="lg:hidden flex items-center gap-1 sm:gap-2 flex-shrink-0 ms-3">
                    <?php renderLangMenu(); ?>
                    <button id="burger-btn" type="button" class="text-white hover:text-gray-200 focus:outline-none p-2" onclick="toggleMobileMenu()" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobile-menu">
                        <i class="ph ph-list text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Nav Menu -->
        <div id="mobile-menu" class="hidden lg:hidden bg-acibadem-blue border-t border-white/10">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?= getBaseUrl() ?>/?find=1" class="flex items-center px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'home' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i class="ph ph-target text-xl me-3"></i><?= __('nav_nearest') ?>
                </a>
                <a href="<?= getBaseUrl() ?>/map" class="flex items-center px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'map' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i class="ph ph-globe-hemisphere-west text-xl me-3"></i><?= __('nav_map') ?>
                </a>
                <a href="<?= getBaseUrl() ?>/offices" class="flex items-center px-3 py-2 rounded-md text-base font-medium <?= ($currentPage === 'offices' || $currentPage === 'office') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i class="ph ph-buildings text-xl me-3"></i><?= __('nav_all_offices') ?>
                </a>
            </div>
        </div>
    </header>
