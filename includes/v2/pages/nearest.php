<?php
/** New design: the nearest-office finder as a page. Data comes from nearest.php. */
$extraHead = '<link rel="stylesheet" href="' . asset('assets/css/finder.css') . '">'
           . '<link rel="stylesheet" href="' . asset('assets/css/finder-v2.css') . '">';
require __DIR__ . '/../header.php';
?>

<section class="page-head">
    <div class="wrap py-12 sm:py-16">
        <div class="text-center max-w-2xl mx-auto rise">
            <p class="eyebrow eyebrow--light justify-center"><?= __('site_name') ?></p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-extrabold"><?= __('nav_nearest') ?></h1>
            <p class="mt-3 text-[#cfdcef] text-base sm:text-lg"><?= __('home_offices_p') ?></p>
        </div>

        <div class="max-w-3xl mx-auto mt-8 sm:mt-10 rise" style="--d:.1s">
            <!-- Filled in by assets/js/nearest-finder.js (same rules as the home page). ?find=1 starts the location search on load. -->
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

            <div class="mt-6 flex flex-wrap justify-center gap-2.5">
                <a href="<?= getBaseUrl() ?>/map" class="btn btn--ghost btn--sm"><i class="ph-fill ph-map-trifold" aria-hidden="true"></i><?= __('offices_view_map') ?></a>
                <a href="<?= getBaseUrl() ?>/offices" class="btn btn--ghost btn--sm"><i class="ph-fill ph-list-dashes" aria-hidden="true"></i><?= __('map_list_view') ?></a>
            </div>
        </div>
    </div>
</section>

<script>window.NF_CONFIG = <?= json_encode($finderConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset('assets/js/tz-countries.js') ?>" defer></script>
<script src="<?= asset('assets/js/nearest-finder.js') ?>" defer></script>

<?php require __DIR__ . '/../footer.php'; ?>
