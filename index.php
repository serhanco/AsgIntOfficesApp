<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$offices = getAllOffices();
$pageTitle = 'Find Your Nearest Office';
$currentPage = 'home';
$needsMap = false;
$metaDescription = 'Find the nearest Acıbadem International information office. Our global network spans 31 countries with 55+ offices ready to assist you.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="relative bg-gradient-to-br from-[#0c2d74] to-[#0A1C36] overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
        <i class="ph-fill ph-shield-plus text-6xl text-white mb-6"></i>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Find Your Nearest Office</h1>
        <p class="text-xl text-blue-100 max-w-2xl mx-auto mb-10">Our global network of 55+ information offices across 31 countries is here to assist you.</p>
        
        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <button onclick="handleNearestOfficeClick()" class="bg-white text-[#0c2d74] hover:bg-gray-50 font-semibold py-4 px-8 rounded-2xl shadow-lg transition-transform hover:scale-105 flex items-center gap-2 text-lg">
                <i class="ph-fill ph-navigation-arrow"></i>
                Locate Nearest Office
            </button>
            <a href="<?= getBaseUrl() ?>/offices.php" class="border-2 border-white/30 text-white hover:bg-white/10 font-semibold py-4 px-8 rounded-2xl transition-colors text-lg">
                View All Offices
            </a>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="text-center">
        <div class="spinner w-12 h-12 border-4 border-[#0c2d74] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-xl font-semibold text-[#0c2d74]">Finding nearest office...</p>
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
                View Full Details
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
                <div class="text-2xl font-bold text-gray-900">55+</div>
                <div class="text-gray-500">Offices</div>
            </div>
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-globe-hemisphere-west text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900">31</div>
                <div class="text-gray-500">Countries</div>
            </div>
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-clock-user text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900">24/7</div>
                <div class="text-gray-500">Support</div>
            </div>
            <div class="bg-white p-6 rounded-2xl text-center shadow-sm">
                <i class="ph-fill ph-users text-3xl text-[#1a4ba0] mb-3"></i>
                <div class="text-2xl font-bold text-gray-900">90+</div>
                <div class="text-gray-500">Countries Served</div>
            </div>
        </div>
    </div>
</div>

<div class="py-16">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-10 text-[#0c2d74]">Explore Our Network</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <a href="<?= getBaseUrl() ?>/offices.php" class="block group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-8 text-center hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#E6F0FA] rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0c2d74] transition-colors">
                    <i class="ph-fill ph-list-dashes text-2xl text-[#0c2d74] group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">All Offices</h3>
                <p class="text-gray-600">Browse our complete directory of international information offices.</p>
            </a>
            
            <a href="<?= getBaseUrl() ?>/map.php" class="block group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-8 text-center hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#E6F0FA] rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0c2d74] transition-colors">
                    <i class="ph-fill ph-map-trifold text-2xl text-[#0c2d74] group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Global Map</h3>
                <p class="text-gray-600">Explore all office locations interactively on our global map.</p>
            </a>

            <a href="<?= getBaseUrl() ?>/api.php" class="block group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-8 text-center hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#E6F0FA] rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0c2d74] transition-colors">
                    <i class="ph-fill ph-code text-2xl text-[#0c2d74] group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Contact API</h3>
                <p class="text-gray-600">Integrate our office location data programmatically.</p>
            </a>
        </div>
    </div>
</div>

<script>
    const officesData = <?= json_encode(array_map(function($o) { 
        return [
            'lat' => (float)$o['latitude'], 
            'lon' => (float)$o['longitude'], 
            'slug' => $o['slug'], 
            'display_name' => $o['display_name'], 
            'country' => $o['country'], 
            'country_code' => $o['country_code'], 
            'address' => $o['address'], 
            'phone' => $o['phone'], 
            'email' => $o['email']
        ]; 
    }, $offices)) ?>;

    function handleNearestOfficeClick() {
        document.getElementById('loading-overlay').classList.remove('hidden');
        
        if (typeof findNearestOffice === 'function') {
            findNearestOffice(officesData, function(nearest) {
                document.getElementById('loading-overlay').classList.add('hidden');
                
                if (!nearest) return;
                
                if (nearest.distance > 2500) {
                    showToast('The nearest office is quite far. Redirecting to all offices.');
                    setTimeout(() => {
                        window.location.href = '<?= getBaseUrl() ?>/offices.php';
                    }, 2000);
                    return;
                }
                
                document.getElementById('result-country').innerHTML = `
                    <img src="https://flagcdn.com/24x18/${nearest.country_code.toLowerCase()}.png" alt="${nearest.country}" class="rounded shadow-sm h-[18px]">
                    <span class="text-gray-500 font-medium">${nearest.country}</span>
                `;
                document.getElementById('result-name').textContent = nearest.display_name;
                document.getElementById('result-address').querySelector('span').textContent = nearest.address;
                document.getElementById('result-distance').textContent = Math.round(nearest.distance) + ' km away';
                
                let callUrl = nearest.phone ? `tel:${nearest.phone.replace(/[^0-9+]/g, '')}` : '#';
                let waUrl = nearest.phone ? `https://wa.me/${nearest.phone.replace(/[^0-9]/g, '')}` : '#';
                let mailUrl = nearest.email ? `mailto:${nearest.email}` : '#';
                let routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${nearest.lat},${nearest.lon}`;
                
                document.getElementById('result-actions').innerHTML = `
                    <a href="${callUrl}" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-[#0c2d74] hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-phone text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">Call</span>
                    </a>
                    <a href="${waUrl}" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-green-600 hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-whatsapp-logo text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">WhatsApp</span>
                    </a>
                    <a href="${mailUrl}" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-[#0c2d74] hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-envelope-simple text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">Email</span>
                    </a>
                    <a href="${routeUrl}" target="_blank" class="flex flex-col items-center justify-center bg-white p-3 rounded-xl shadow border border-gray-100 text-[#0c2d74] hover:bg-gray-50 transition-colors">
                        <i class="ph-fill ph-navigation-arrow text-2xl mb-1"></i>
                        <span class="text-xs font-semibold">Route</span>
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
