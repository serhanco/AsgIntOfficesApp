<?php
/**
 * Nearest-office finder — test page (/nearest-beta).
 * Same home page, with the new finder: country guess without asking for location,
 * same-country priority, "no office in your country" card, city search, countdown to the map.
 * Not linked from the menu; the live home page (index.php) is unchanged.
 *
 * Test parameters:
 *   ?cc=az          pretend the visitor is in this country (ISO code)
 *   ?at=40.4,49.8   pretend the browser location is this point
 *   ?at=deny        pretend location access was refused
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$offices = getAllOffices();
$pageTitle = __('home_title');
$currentPage = 'home';
$needsMap = false;
$metaDescription = __('site_name') . ' - ' . __('meta_default');

// Country from the CDN, when the site runs behind Cloudflare (header absent otherwise)
$serverCountry = strtolower($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '');
if (!preg_match('/^[a-z]{2}$/', $serverCountry) || $serverCountry === 'xx' || $serverCountry === 't1') {
    $serverCountry = '';
}

$testCountry = strtolower($_GET['cc'] ?? '');
if (!preg_match('/^[a-z]{2}$/', $testCountry)) $testCountry = '';

$testAt = null;
$at = $_GET['at'] ?? '';
if ($at === 'deny') {
    $testAt = 'deny';
} elseif (preg_match('/^(-?\d{1,2}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)$/', $at, $m)) {
    $testAt = [(float)$m[1], (float)$m[2]];
}

// Head office: shown when there is no office in the visitor's country (and for Türkiye)
$hq = [
    'phone'    => '+90 216 444 5544',
    'whatsapp' => '+90 535 965 0466',
    'email'    => 'international@acibadem.com',
    'address'  => 'Atatürk Mah. Feza Sok. No:3 Ataşehir / İstanbul',
    'country_code' => 'tr',
];

require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/finder.css?v=1">

<div class="relative bg-[#0A1C36] overflow-hidden">
    <!-- Background Image -->
    <img src="<?= getBaseUrl() ?>/assets/images/home-hero.webp" alt="Acıbadem Global Offices" class="absolute inset-0 w-full h-full object-cover opacity-70">
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-[#0A1C36] via-[#0A1C36]/50 to-transparent"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4"><?= __('home_title') ?></h1>
        <p class="text-xl text-blue-100 max-w-2xl mx-auto mb-10"><?= __('home_subtitle', count($offices), count(array_unique(array_column($offices, 'country')))) ?></p>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <button type="button" data-nf-locate class="nf-locate-btn cta-btn bg-white text-[#0c2d74] hover:bg-gray-50 font-semibold py-4 px-8 rounded-2xl shadow-lg transition-transform hover:scale-105 flex items-center gap-2 text-lg">
                <span class="nf-radar" aria-hidden="true"><i class="ph-fill ph-navigation-arrow"></i></span>
                <span data-nf-locate-label><?= __('btn_locate') ?></span>
            </button>
            <a href="<?= getBaseUrl() ?>/map" class="border-2 border-white/30 text-white hover:bg-white/10 font-semibold py-4 px-8 rounded-2xl transition-colors text-lg flex items-center gap-2">
                <i class="ph-fill ph-globe-hemisphere-west"></i>
                <?= __('btn_map') ?>
            </a>
        </div>
    </div>
</div>

<!-- Nearest office finder card (filled in by assets/js/nearest-finder.js) -->
<div class="max-w-4xl mx-auto px-4 -mt-10 relative z-20 pb-12">
    <section id="nf-card" class="nf-card" aria-live="polite">
        <div class="nf-skeleton">
            <span class="nf-radar nf-radar--dark" aria-hidden="true"><i class="ph-fill ph-navigation-arrow"></i></span>
            <span><?= __('nf_detecting') ?></span>
        </div>
    </section>

    <div class="nf-search" id="nf-search">
        <label for="nf-search-input" class="nf-search__label"><?= __('nf_search_label') ?></label>
        <div class="nf-search__box">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="nf-search-input" autocomplete="off" spellcheck="false"
                   placeholder="<?= e(__('nf_search_ph')) ?>"
                   role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="nf-search-list">
        </div>
        <ul id="nf-search-list" class="nf-search__list" role="listbox" hidden></ul>
    </div>
</div>

<div class="bg-gray-50 pt-4 pb-16">
    <!-- Modern Unified Stats Panel -->
    <div id="stats-panel" class="max-w-7xl mx-auto px-4 relative z-10 mb-16">
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-0 md:divide-x rtl:md:divide-x-reverse md:divide-gray-100">
                <div class="text-center px-4 group">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-blue-50 text-[#0c2d74] mb-4 group-hover:scale-110 group-hover:bg-[#0c2d74] group-hover:text-white transition-all duration-300">
                        <i class="ph-fill ph-buildings text-2xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 mb-1"><?= count($offices) ?>+</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= __('stat_offices') ?></div>
                </div>
                <div class="text-center px-4 group">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-blue-50 text-[#0c2d74] mb-4 group-hover:scale-110 group-hover:bg-[#0c2d74] group-hover:text-white transition-all duration-300">
                        <i class="ph-fill ph-globe-hemisphere-west text-2xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 mb-1"><?= count(array_unique(array_column($offices, 'country'))) ?></div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= __('stat_countries') ?></div>
                </div>
                <div class="text-center px-4 group">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-blue-50 text-[#0c2d74] mb-4 group-hover:scale-110 group-hover:bg-[#0c2d74] group-hover:text-white transition-all duration-300">
                        <i class="ph-fill ph-clock-user text-2xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 mb-1">24/7</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= __('stat_support') ?></div>
                </div>
                <div class="text-center px-4 group">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-blue-50 text-[#0c2d74] mb-4 group-hover:scale-110 group-hover:bg-[#0c2d74] group-hover:text-white transition-all duration-300">
                        <i class="ph-fill ph-users text-2xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 mb-1">90+</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= __('stat_served') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Explore Section -->
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-black text-[#0c2d74] mb-4"><?= __('explore_heading') ?></h2>
            <div class="w-20 h-1.5 bg-[#1a4ba0] mx-auto rounded-full opacity-80"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1 -->
            <a href="<?= getBaseUrl() ?>/offices" class="relative overflow-hidden block group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 p-8 text-start hover:-translate-y-1">
                <i class="ph-fill ph-list-dashes absolute -end-4 -bottom-4 text-9xl text-gray-50 opacity-50 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500"></i>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="w-14 h-14 bg-blue-50 text-[#0c2d74] rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors duration-300">
                        <i class="ph-fill ph-list-dashes text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900 group-hover:text-[#0c2d74] transition-colors"><?= __('explore_offices_h') ?></h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-1"><?= __('explore_offices_p') ?></p>
                    <div class="inline-flex items-center text-[#0c2d74] font-bold text-sm opacity-0 -translate-x-4 rtl:translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 rtl:group-hover:translate-x-0 transition-all duration-300">
                        <i class="ph <?= arrowIcon() ?> text-lg"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2 -->
            <a href="<?= getBaseUrl() ?>/map" class="relative overflow-hidden block group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 p-8 text-start hover:-translate-y-1">
                <i class="ph-fill ph-map-trifold absolute -end-4 -bottom-4 text-9xl text-gray-50 opacity-50 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500"></i>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="w-14 h-14 bg-blue-50 text-[#0c2d74] rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors duration-300">
                        <i class="ph-fill ph-map-trifold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900 group-hover:text-[#0c2d74] transition-colors"><?= __('explore_map_h') ?></h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-1"><?= __('explore_map_p') ?></p>
                    <div class="inline-flex items-center text-[#0c2d74] font-bold text-sm opacity-0 -translate-x-4 rtl:translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 rtl:group-hover:translate-x-0 transition-all duration-300">
                        <i class="ph <?= arrowIcon() ?> text-lg"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3 — Acıbadem Online -->
            <a href="https://www.acibadem.com.tr/acibademonline/#/login" target="_blank" rel="noopener noreferrer" class="relative overflow-hidden block group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 p-8 text-start hover:-translate-y-1">
                <i class="ph-fill ph-desktop-tower absolute -end-4 -bottom-4 text-9xl text-gray-50 opacity-50 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500"></i>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="w-14 h-14 bg-blue-50 text-[#0c2d74] rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors duration-300">
                        <i class="ph-fill ph-desktop-tower text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900 group-hover:text-[#0c2d74] transition-colors"><?= __('explore_online_h') ?></h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-1"><?= __('explore_online_p') ?></p>
                    <div class="inline-flex items-center text-[#0c2d74] font-bold text-sm opacity-0 -translate-x-4 rtl:translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 rtl:group-hover:translate-x-0 transition-all duration-300">
                        <i class="ph <?= arrowIcon() ?> text-lg"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Wide Dark CTA Card -->
        <div class="mt-8 relative overflow-hidden bg-gradient-to-br from-[#0c2d74] to-[#0A1C36] rounded-3xl p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-8 group shadow-2xl shadow-blue-900/20">
            <i class="ph-fill ph-handshake absolute -end-8 -bottom-8 text-[12rem] text-white opacity-5 group-hover:scale-110 group-hover:opacity-10 transition-all duration-700 pointer-events-none"></i>

            <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-6 text-center md:text-start">
                <div class="w-16 h-16 bg-white/10 backdrop-blur text-white rounded-2xl flex items-center justify-center flex-shrink-0 border border-white/10">
                    <i class="ph-fill ph-shield-check text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-white mb-3"><?= __('inst_card_h') ?></h3>
                    <p class="text-blue-100 text-sm md:text-base max-w-2xl leading-relaxed"><?= __('inst_card_p') ?></p>
                </div>
            </div>

            <a href="<?= getBaseUrl() ?>/contracted-institutions" class="relative z-10 flex-shrink-0 w-full md:w-auto text-center bg-white text-[#0c2d74] hover:bg-gray-50 px-8 py-4 rounded-xl font-bold transition-transform shadow-lg inline-flex items-center justify-center gap-2 group-hover:-translate-y-1">
                <?= __('inst_card_btn') ?> <i class="ph <?= arrowIcon() ?>"></i>
            </a>
        </div>

    </div>
</div>

<?php
$finderConfig = [
    'lang'          => LANGUAGES[$GLOBALS['current_lang']]['html'] ?? 'en',
    'serverCountry' => $serverCountry,
    'testCountry'   => $testCountry,
    'testAt'        => $testAt,
    'mapUrl'        => getBaseUrl() . '/map',
    'intlUrl'       => 'https://acibademinternational.com/',
    'listUrl'       => getBaseUrl() . '/offices',
    'hq'            => $hq,
    'offices'       => array_map(fn($o) => [
        'lat'          => (float)$o['latitude'],
        'lon'          => (float)$o['longitude'],
        'name'         => $o['display_name'],
        'country'      => $o['country'],
        'cc'           => strtolower((string)$o['country_code']),
        'address'      => $o['address'],
        'phone'        => $o['phone'],
        'email'        => $o['email'],
        'url'          => officeUrl($o['slug']),
    ], $offices),
    't' => [
        'locate'         => __('btn_locate'),
        'locating'       => __('nf_locating'),
        'seemsIn'        => __('nf_seems_in'),
        'byLocation'     => __('nf_by_location'),
        'nearestLabel'   => __('nf_nearest_label'),
        'selectedLabel'  => __('nf_selected_label'),
        'regionLabel'    => __('nf_region_label'),
        'otherInCountry' => __('nf_other_in_country'),
        'noOfficeH'      => __('nf_no_office_h'),
        'noOfficeRegionH'=> __('nf_no_office_region_h'),
        'noOfficeP'      => __('nf_no_office_p'),
        'hqName'         => __('nf_hq_name'),
        'hqLabel'        => __('nf_hq_label'),
        'nearestPhysical'=> __('nf_nearest_physical'),
        'seeWorld'       => __('nf_see_world'),
        'seeList'        => __('nf_see_list'),
        'refine'         => __('nf_refine'),
        'unknownH'       => __('nf_unknown_h'),
        'unknownP'       => __('nf_unknown_p'),
        'searchNone'     => __('nf_search_none'),
        'deniedH'        => __('nf_denied_h'),
        'deniedP'        => __('nf_denied_p'),
        'stay'           => __('nf_stay'),
        'goNow'          => __('nf_go_now'),
        'kmAway'         => __('result_km_away'),
        'details'        => __('result_view_details'),
        'call'           => __('js_call'),
        'whatsapp'       => __('js_whatsapp'),
        'email'          => __('js_email'),
        'route'          => __('js_route'),
    ],
];
?>
<script>window.NF_CONFIG = <?= json_encode($finderConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= getBaseUrl() ?>/assets/js/tz-countries.js?v=1" defer></script>
<script src="<?= getBaseUrl() ?>/assets/js/nearest-finder.js?v=1" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
