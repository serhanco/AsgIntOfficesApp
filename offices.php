<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$offices = getAllOffices();
$countries = getCountriesWithCount();
$totalOffices = count($offices);
$totalCountries = count($countries);

$pageTitle = 'All Offices';
$currentPage = 'offices';
$needsMap = false;
$metaDescription = 'Browse all ' . $totalOffices . ' Acıbadem International information offices across ' . $totalCountries . ' countries worldwide.';

// Group by country
$grouped = [];
foreach ($offices as $o) {
    $grouped[$o['country']][] = $o;
}
ksort($grouped);

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-gray-50 min-h-screen pb-20">
    <div class="bg-white border-b border-gray-200 py-10">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-[#0c2d74] mb-2">All Offices</h1>
                <p class="text-gray-500 text-lg"><?= $totalOffices ?> offices across <?= $totalCountries ?> countries</p>
            </div>
            <div>
                <a href="<?= getBaseUrl() ?>/map" class="bg-[#E6F0FA] text-[#0c2d74] hover:bg-[#0c2d74] hover:text-white font-semibold py-3 px-6 rounded-xl transition-colors flex items-center gap-2">
                    <i class="ph-fill ph-map-trifold"></i>
                    View on Map
                </a>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="sticky top-0 z-40 bg-white/90 backdrop-blur-md shadow-sm py-4 border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                <input type="text" id="office-search" placeholder="Search by office, country, or city..." class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-300 focus:border-[#0c2d74] focus:ring-1 focus:ring-[#0c2d74] outline-none transition-shadow">
            </div>
            
            <div class="relative w-full md:w-64">
                <select id="country-jump" onchange="scrollToCountry(this.value)" class="w-full pl-4 pr-10 py-3 rounded-xl border border-gray-300 focus:border-[#0c2d74] focus:ring-1 focus:ring-[#0c2d74] outline-none appearance-none bg-white font-medium text-gray-700">
                    <option value="">Jump to Country...</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= e(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $c['country']))) ?>"><?= e($c['country']) ?> (<?= $c['office_count'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"></i>
            </div>
        </div>
    </div>

    <!-- No Results -->
    <div id="no-results" class="hidden text-center py-20 px-4">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
            <i class="ph-fill ph-magnifying-glass-minus text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">No results found</h3>
        <p class="text-gray-500">We couldn't find any offices matching your search.</p>
    </div>

    <!-- Office List -->
    <div class="max-w-6xl mx-auto px-4 mt-10">
        <?php foreach ($grouped as $countryName => $countryOffices): 
            $cSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $countryName));
            $cCode = $countryOffices[0]['country_code'];
        ?>
            <div id="country-<?= e($cSlug) ?>" class="country-group mb-12">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-200 pb-2">
                    <?= getFlagImg($countryName, $cCode) ?>
                    <h2 class="text-2xl font-bold text-[#0A1C36]"><?= e($countryName) ?></h2>
                    <span class="bg-[#E6F0FA] text-[#0c2d74] text-sm font-bold px-2 py-0.5 rounded-full"><?= count($countryOffices) ?></span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($countryOffices as $o): ?>
                        <a href="<?= officeUrl($o['slug']) ?>" 
                           class="office-card block bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all group"
                           data-name="<?= strtolower(e($o['display_name'])) ?>"
                           data-country="<?= strtolower(e($o['country'])) ?>"
                           data-address="<?= strtolower(e($o['address'])) ?>">
                            <div class="flex items-start justify-between gap-4">
                                <div class="bg-[#E6F0FA] p-3 rounded-xl text-[#0c2d74] group-hover:bg-[#0c2d74] group-hover:text-white transition-colors shrink-0">
                                    <i class="ph-fill ph-buildings text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 text-lg leading-tight mb-1 truncate"><?= e($o['display_name']) ?></h3>
                                    <p class="text-sm text-gray-500 line-clamp-2"><?= e($o['address']) ?></p>
                                </div>
                                <div class="text-gray-300 group-hover:text-[#1a4ba0] transition-colors shrink-0 mt-2">
                                    <i class="ph ph-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
