<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = __('inst_title');
$currentPage = 'contracted-institutions';
$metaDescription = __('inst_subtitle');

$institutions = require __DIR__ . '/includes/institutions.php';

if (designV2()) {
    require __DIR__ . '/includes/v2/pages/institutions.php';
    return;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="relative bg-gradient-to-br from-[#0c2d74] to-[#0A1C36] overflow-hidden pt-20 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6"><?= __('inst_title') ?></h1>
        <p class="text-xl text-blue-100 max-w-3xl mx-auto"><?= __('inst_subtitle') ?></p>
    </div>
</div>

<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Search Bar -->
        <div class="max-w-md mx-auto mb-10">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute start-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                <input type="text" id="inst-search" placeholder="<?= __('inst_search_ph') ?>" class="w-full ps-12 pe-4 py-3 rounded-xl border border-gray-300 focus:border-[#0c2d74] focus:ring-1 focus:ring-[#0c2d74] outline-none transition-shadow bg-white shadow-sm">
            </div>
        </div>

        <!-- No Results -->
        <div id="inst-no-results" class="hidden text-center py-16 px-4">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <i class="ph-fill ph-magnifying-glass-minus text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-1"><?= __('offices_no_results_h') ?></h3>
            <p class="text-gray-500 text-sm"><?= __('offices_no_results_p') ?></p>
        </div>

        <div id="inst-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($institutions as $inst): ?>
                <div class="inst-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:border-[#0c2d74] hover:shadow-md transition-all group" data-name="<?= strtolower(e($inst)) ?>">
                    <div class="w-12 h-12 rounded-xl bg-[#E6F0FA] text-[#0c2d74] flex items-center justify-center font-bold text-lg flex-shrink-0 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                        <?= substr($inst, 0, 1) ?>
                    </div>
                    <span class="font-bold text-gray-800 group-hover:text-[#0c2d74] transition-colors leading-tight"><?= e($inst) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('inst-search').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.inst-card');
    let visible = 0;
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const match = !query || name.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('inst-no-results').classList.toggle('hidden', visible > 0);
    document.getElementById('inst-grid').classList.toggle('hidden', visible === 0);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
