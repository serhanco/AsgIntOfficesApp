<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$offices = getAllOffices();
$pageTitle = __('home_title');
$currentPage = 'home';
$needsMap = false;
$metaDescription = __('site_name') . ' - ' . __('meta_default');

require_once __DIR__ . '/includes/header.php';
?>

<div class="relative bg-[#0A1C36] overflow-hidden">
    <!-- Background Image -->
    <img src="<?= getBaseUrl() ?>/assets/images/home-hero.webp" alt="Acıbadem Global Offices" class="absolute inset-0 w-full h-full object-cover opacity-70">
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-[#0A1C36] via-[#0A1C36]/50 to-transparent"></div>
    
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4"><?= __('home_title') ?></h1>
        <p class="text-xl text-blue-100 max-w-2xl mx-auto mb-10"><?= __('home_subtitle', count($offices), count(array_unique(array_column($offices, 'country')))) ?></p>
        
        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <button onclick="handleNearestOfficeClick()" class="bg-white text-[#0c2d74] hover:bg-gray-50 font-semibold py-4 px-8 rounded-2xl shadow-lg transition-transform hover:scale-105 flex items-center gap-2 text-lg">
                <i class="ph-fill ph-navigation-arrow"></i>
                <?= __('btn_locate') ?>
            </button>
            <a href="<?= getBaseUrl() ?>/map" class="border-2 border-white/30 text-white hover:bg-white/10 font-semibold py-4 px-8 rounded-2xl transition-colors text-lg flex items-center gap-2">
                <i class="ph-fill ph-globe-hemisphere-west"></i>
                <?= __('btn_map') ?>
            </a>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="text-center">
        <div class="spinner w-12 h-12 border-4 border-[#0c2d74] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-xl font-semibold text-[#0c2d74]"><?= __('loading_nearest') ?></p>
    </div>
</div>

<!-- Nearest Office Result -->
<div id="nearest-office-result" class="hidden max-w-4xl mx-auto px-4 -mt-10 relative z-10 fade-in pb-16">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2" id="result-country">
                    <!-- Flag + Country -->
                </div>
                <h2 class="text-3xl font-bold text-[#0c2d74] mb-2" id="result-name"></h2>
                <p class="text-gray-600 flex items-start gap-2 mb-4" id="result-address">
                    <i class="ph-fill ph-map-pin text-[#1a4ba0] mt-1"></i>
                    <span></span>
                </p>
                <div class="inline-block bg-[#E6F0FA] text-[#0c2d74] px-4 py-2 rounded-full font-semibold text-sm" id="result-distance">
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full md:w-auto" id="result-actions">
                <!-- CTA Buttons injected via JS -->
            </div>
        </div>
        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <a href="#" id="result-link" class="text-[#1a4ba0] hover:text-[#0c2d74] font-semibold inline-flex items-center gap-1 group">
                <?= __('result_view_details') ?>
                <i class="ph ph-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </div>
</div>

<div class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-buildings text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900"><?= count($offices) ?>+</div>
                <div class="text-gray-500"><?= __('stat_offices') ?></div>
            </div>
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-globe-hemisphere-west text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900"><?= count(array_unique(array_column($offices, 'country'))) ?></div>
                <div class="text-gray-500"><?= __('stat_countries') ?></div>
            </div>
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-clock-user text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900">24/7</div>
                <div class="text-gray-500"><?= __('stat_support') ?></div>
            </div>
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-users text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900">90+</div>
                <div class="text-gray-500"><?= __('stat_served') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="py-16">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-10 text-[#0c2d74]"><?= __('explore_heading') ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <a href="<?= getBaseUrl() ?>/offices" class="block group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-8 text-center hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#E6F0FA] rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0c2d74] transition-colors">
                    <i class="ph-fill ph-list-dashes text-2xl text-[#0c2d74] group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-xl font-bold mb-2"><?= __('explore_offices_h') ?></h3>
                <p class="text-gray-600"><?= __('explore_offices_p') ?></p>
            </a>
            
            <a href="<?= getBaseUrl() ?>/map" class="block group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-8 text-center hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#E6F0FA] rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0c2d74] transition-colors">
                    <i class="ph-fill ph-map-trifold text-2xl text-[#0c2d74] group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-xl font-bold mb-2"><?= __('explore_map_h') ?></h3>
                <p class="text-gray-600"><?= __('explore_map_p') ?></p>
            </a>

            <a href="<?= getBaseUrl() ?>/api/offices" class="block group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-8 text-center hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#E6F0FA] rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0c2d74] transition-colors">
                    <i class="ph-fill ph-code text-2xl text-[#0c2d74] group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-xl font-bold mb-2"><?= __('explore_api_h') ?></h3>
                <p class="text-gray-600"><?= __('explore_api_p') ?></p>
            </a>
        </div>

        <div class="mt-8 bg-white rounded-3xl shadow-sm border border-gray-100 p-8 md:p-10 flex flex-col md:flex-row items-center justify-between gap-8 group hover:border-[#0c2d74] transition-colors">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-[#E6F0FA] text-[#0c2d74] rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                    <i class="ph-fill ph-handshake text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-[#0c2d74] mb-2"><?= __('inst_card_h') ?></h3>
                    <p class="text-gray-600"><?= __('inst_card_p') ?></p>
                </div>
            </div>
            <a href="<?= getBaseUrl() ?>/contracted-institutions" class="flex-shrink-0 w-full md:w-auto text-center bg-[#0c2d74] hover:bg-[#1a4ba0] text-white px-8 py-4 rounded-xl font-bold transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <?= __('inst_card_btn') ?> <i class="ph ph-arrow-right"></i>
            </a>
        </div>

    </div>
</div>

<?php
// Pass translated JS strings server-side to avoid PHP inside JS strings
$jsStrings = [
    'kmAway'      => __('result_km_away'),
    'call'        => __('js_call'),
    'whatsapp'    => __('js_whatsapp'),
    'email'       => __('js_email'),
    'route'       => __('js_route'),
    'farRedirect' => 'The nearest office is quite far. Redirecting to all offices.',
];
?>
<script>
    const officesData = <?= json_encode(array_map(function($o) {
        return [
            'lat'          => (float)$o['latitude'],
            'lon'          => (float)$o['longitude'],
            'slug'         => $o['slug'],
            'display_name' => $o['display_name'],
            'country'      => $o['country'],
            'country_code' => $o['country_code'],
            'address'      => $o['address'],
            'phone'        => $o['phone'],
            'email'        => $o['email']
        ];
    }, $offices)) ?>;

    const i18n = <?= json_encode($jsStrings) ?>;

    function handleNearestOfficeClick() {
        document.getElementById('loading-overlay').classList.remove('hidden');
        
        if (typeof findNearestOffice === 'function') {
            findNearestOffice(officesData, function(nearest) {
                document.getElementById('loading-overlay').classList.add('hidden');
                
                if (!nearest) return;
                
                if (nearest.distance > 2500) {
                    showToast(i18n.farRedirect);
                    setTimeout(() => {
                        window.location.href = '<?= getBaseUrl() ?>/offices';
                    }, 2000);
                    return;
                }
                
                document.getElementById('result-country').innerHTML = `
                    <img src="https://flagcdn.com/24x18/${nearest.country_code.toLowerCase()}.png" alt="${nearest.country}" class="rounded shadow-sm h-[18px]">
                    <span class="text-gray-500 font-medium">${nearest.country}</span>
                `;
                document.getElementById('result-name').textContent = nearest.display_name;
                document.getElementById('result-address').querySelector('span').textContent = nearest.address;
                document.getElementById('result-distance').textContent = i18n.kmAway.replace('%.0f', Math.round(nearest.distance));
                
                let callUrl  = nearest.phone ? `tel:${nearest.phone.replace(/[^0-9+]/g, '')}` : '#';
                let waUrl    = nearest.phone ? `https://wa.me/${nearest.phone.replace(/[^0-9]/g, '')}` : '#';
                let mailUrl  = nearest.email ? `mailto:${nearest.email}` : '#';
                let routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${nearest.lat},${nearest.lon}`;
                
                document.getElementById('result-actions').innerHTML = `
                    <a href="${callUrl}" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-[#0c2d74] hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-phone text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">${i18n.call}</span>
                    </a>
                    <a href="${waUrl}" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-green-600 hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-whatsapp-logo text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">${i18n.whatsapp}</span>
                    </a>
                    <a href="${mailUrl}" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-[#0c2d74] hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-envelope-simple text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">${i18n.email}</span>
                    </a>
                    <a href="${routeUrl}" target="_blank" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-[#0c2d74] hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-navigation-arrow text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">${i18n.route}</span>
                    </a>
                `;
                
                document.getElementById('result-link').href = '<?= getBaseUrl() ?>/office/' + nearest.slug;
                
                const resultBlock = document.getElementById('nearest-office-result');
                resultBlock.classList.remove('hidden');
            });
        } else {
            document.getElementById('loading-overlay').classList.add('hidden');
            console.error('findNearestOffice function not found in app.js');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
