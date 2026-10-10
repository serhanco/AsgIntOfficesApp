<?php
/**
 * New design: page head + site header. Pages set $pageTitle, $currentPage,
 * $needsMap, $metaDescription (and optionally $ogImage, $extraHead) first.
 */
$pageTitle       = $pageTitle ?? __('site_name');
$currentPage     = $currentPage ?? 'home';
$needsMap        = $needsMap ?? false;
$metaDescription = $metaDescription ?? __('meta_default');
$ogImage         = $ogImage ?? (getBaseUrl() . '/assets/images/og-logo.png');
$extraHead       = $extraHead ?? '';
$current_lang    = $GLOBALS['current_lang'] ?? 'en';

function v2LangUrl(string $lang): string {
    $params = $_GET;
    unset($params['slug']);
    $params['lang'] = $lang;
    return strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($params);
}

/** Flag + language pill that opens a two-column list (open/close handled in assets/js/app.js). */
function v2LangMenu(string $id): void {
    $current = $GLOBALS['current_lang'];
    $cur = LANGUAGES[$current];
    ?>
    <div class="relative" data-lang-menu>
        <button type="button" class="lang-btn" aria-haspopup="true" aria-expanded="false" aria-controls="<?= e($id) ?>" aria-label="<?= e(__('lang_switcher_label')) ?>" data-lang-toggle>
            <img src="https://flagcdn.com/w40/<?= e($cur['flag']) ?>.png" alt="" width="20" height="15" class="w-5 h-[15px] object-cover rounded-[3px] flex-shrink-0">
            <span class="hidden sm:inline" lang="<?= e($cur['html']) ?>" dir="<?= e($cur['dir']) ?>"><?= e($cur['name']) ?></span>
            <i class="ph ph-caret-down text-xs" aria-hidden="true"></i>
        </button>
        <div id="<?= e($id) ?>" class="lang-panel hidden fixed inset-x-3 top-[calc(var(--hb,var(--header-h))+8px)] sm:absolute sm:inset-x-auto sm:end-0 sm:top-full sm:mt-3 sm:w-[26rem] z-50 max-h-[75vh] overflow-y-auto bg-white rounded-3xl shadow-lift border border-line p-2" data-lang-panel>
            <ul class="grid grid-cols-2 gap-1">
                <?php foreach (LANGUAGES as $code => $l): $active = $code === $current; ?>
                <li><a href="<?= e(v2LangUrl($code)) ?>" hreflang="<?= e($l['html']) ?>"<?= $active ? ' aria-current="true"' : '' ?>
                       class="flex items-center gap-2.5 sm:gap-3 px-3 py-2.5 rounded-2xl text-sm transition-colors <?= $active ? 'bg-aqua-soft text-navy font-bold' : 'text-ink hover:bg-surface hover:text-navy font-medium' ?>">
                    <img src="https://flagcdn.com/w40/<?= e($l['flag']) ?>.png" alt="" width="24" height="18" class="w-6 h-[18px] object-cover rounded-[4px] shadow-sm ring-1 ring-black/5 flex-shrink-0" loading="lazy">
                    <span lang="<?= e($l['html']) ?>" dir="<?= e($l['dir']) ?>" class="truncate"><?= e($l['name']) ?></span>
                    <?php if ($active): ?><i class="ph-fill ph-check-circle text-aqua ms-auto" aria-hidden="true"></i><?php endif; ?>
                </a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
}

$navItems = [
    ['url' => getBaseUrl() . '/?find=1', 'icon' => 'ph-target',                'label' => __('nav_nearest'),     'active' => $currentPage === 'home'],
    ['url' => getBaseUrl() . '/map',     'icon' => 'ph-globe-hemisphere-west', 'label' => __('nav_map'),         'active' => $currentPage === 'map'],
    ['url' => getBaseUrl() . '/offices', 'icon' => 'ph-buildings',             'label' => __('nav_all_offices'), 'active' => in_array($currentPage, ['offices', 'office'], true)],
    ['url' => getBaseUrl() . '/events',  'icon' => 'ph-calendar-star',         'label' => __('nav_events'),      'active' => $currentPage === 'events'],
];
?>
<!DOCTYPE html>
<html lang="<?= e(langHtml()) ?>" dir="<?= langDir() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($pageTitle) ?> | <?= __('site_name') ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#092c74">
    <meta property="og:title" content="<?= e($pageTitle) ?> | <?= __('site_name') ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="<?= getBaseUrl() ?>/assets/images/favicon.png">
    <link rel="icon" type="image/x-icon" href="<?= getBaseUrl() ?>/assets/images/favicon.ico">
    <script>document.documentElement.classList.add('js');if(!matchMedia('(prefers-reduced-motion: reduce)').matches)document.documentElement.classList.add('js-anim');</script>

    <?php $ga4Id = ga4Id(); ?>
    <?php if ($ga4Id !== '' && !isAdminVisitor() && analyticsAllowed()): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4Id) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= e($ga4Id) ?>');
    </script>
    <?php endif; ?>

    <!-- Fonts: Plus Jakarta Sans (brand), Inter for Cyrillic, Noto for Arabic/Persian and Georgian.
         Each face only downloads when the page has characters it is needed for. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://flagcdn.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;500;600;700;800&family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="preload" href="<?= asset('assets/fonts/phosphor-fill.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= asset('assets/fonts/phosphor-regular.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('assets/css/icons.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/v2.css') ?>">

    <?php if ($needsMap): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <?php endif; ?>

    <script>window.APP_I18N = <?= json_encode([
        'geoUnsupported' => __('js_geo_unsupported'),
        'geoDenied'      => __('js_geo_denied'),
        'viewDetails'    => __('map_view_details'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
    <script defer src="<?= asset('assets/js/app.js') ?>"></script>
    <script defer src="<?= asset('assets/js/v2.js') ?>"></script>
    <?= $extraHead ?>
    <?= customCode('head_code') ?>
</head>
<body class="v2 min-h-screen flex flex-col antialiased">
    <?= customCode('body_code') ?>
    <?php if (designPreview()): ?>
    <div class="preview-bar">
        <div class="wrap py-1.5 flex items-center justify-center gap-3 text-center" dir="ltr" lang="tr">
            <span><i class="ph-fill ph-sparkle" aria-hidden="true"></i> Yeni tasarım önizlemesi · ziyaretçiler hâlâ mevcut tasarımı görüyor</span>
            <a href="?design=v1">Mevcut tasarıma dön</a>
        </div>
    </div>
    <?php endif; ?>

    <header class="site-header" data-header>
        <div class="wrap site-header__bar">
            <a href="<?= getBaseUrl() ?>/" class="flex items-center min-w-0 flex-shrink" aria-label="Acıbadem">
                <img class="h-6 sm:h-7 lg:h-8 w-auto max-w-full object-contain" src="<?= getBaseUrl() ?>/assets/images/acibadem-white-logo.webp" alt="Acıbadem" width="200" height="32">
            </a>

            <nav class="hidden lg:flex items-center gap-1" aria-label="Main">
                <?php foreach ($navItems as $n): ?>
                <a href="<?= e($n['url']) ?>" class="nav-link"<?= $n['active'] ? ' aria-current="page"' : '' ?>>
                    <i class="ph <?= $n['icon'] ?>" aria-hidden="true"></i><?= $n['label'] ?>
                </a>
                <?php endforeach; ?>
            </nav>

            <div class="flex items-center gap-2 flex-shrink-0">
                <?php v2LangMenu('lang-panel'); ?>
                <button type="button" class="burger lg:hidden" aria-label="Menu" aria-expanded="false" aria-controls="site-drawer" data-burger><span></span></button>
            </div>
        </div>
    </header>

    <div id="site-drawer" class="drawer lg:hidden" data-drawer aria-hidden="true">
        <div class="drawer__panel">
            <nav class="grid gap-2" aria-label="Main">
                <?php foreach ($navItems as $n): ?>
                <a href="<?= e($n['url']) ?>" class="drawer__tile"<?= $n['active'] ? ' aria-current="page"' : '' ?>>
                    <i class="ph-fill <?= $n['icon'] ?>" aria-hidden="true"></i>
                    <span class="flex-1"><?= $n['label'] ?></span>
                    <i class="ph ph-arrow-right arrow text-white/50" aria-hidden="true"></i>
                </a>
                <?php endforeach; ?>
                <a href="<?= getBaseUrl() ?>/contracted-institutions" class="drawer__tile"<?= $currentPage === 'contracted-institutions' ? ' aria-current="page"' : '' ?>>
                    <i class="ph-fill ph-shield-check" aria-hidden="true"></i>
                    <span class="flex-1"><?= __('inst_title') ?></span>
                    <i class="ph ph-arrow-right arrow text-white/50" aria-hidden="true"></i>
                </a>
            </nav>
            <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs font-bold">
                <a href="https://wa.me/905359650466" target="_blank" rel="noopener" class="rounded-2xl bg-white/5 py-3 text-white"><i class="ph-fill ph-whatsapp-logo text-2xl text-[#4ee38a] block mb-1" aria-hidden="true"></i><?= __('js_whatsapp') ?></a>
                <a href="tel:+902164445544" class="rounded-2xl bg-white/5 py-3 text-white"><i class="ph-fill ph-phone text-2xl text-[#8fd6f3] block mb-1" aria-hidden="true"></i><?= __('js_call') ?></a>
                <a href="mailto:international@acibadem.com" class="rounded-2xl bg-white/5 py-3 text-white"><i class="ph-fill ph-envelope-simple text-2xl text-[#8fd6f3] block mb-1" aria-hidden="true"></i><?= __('js_email') ?></a>
            </div>
        </div>
    </div>

    <main id="main" class="flex-1">
