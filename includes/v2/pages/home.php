<?php
/** New design: home page with the nearest-office finder. Data comes from index.php. */
$countryCount = count(array_unique(array_column($offices, 'country')));
$institutions = require __DIR__ . '/../../institutions.php';
$extraHead = '<link rel="preload" as="image" href="' . getBaseUrl() . '/assets/images/home-hero.webp" fetchpriority="high">'
           . '<link rel="stylesheet" href="' . asset('assets/css/finder.css') . '">'
           . '<link rel="stylesheet" href="' . asset('assets/css/finder-v2.css') . '">';
require __DIR__ . '/../header.php';
?>

<section class="hero">
    <img src="<?= getBaseUrl() ?>/assets/images/home-hero.webp" alt="" class="hero__img" fetchpriority="high" decoding="async">
    <div class="hero__shade"></div>
    <div class="hero__grid"></div>
    <div class="hero__glow -top-40 end-[-10rem]"></div>

    <div class="wrap relative pt-10 pb-16 sm:pt-14 lg:pt-20 lg:pb-28">
        <div class="grid gap-8 lg:gap-x-14 lg:grid-cols-[minmax(0,1fr)_minmax(0,480px)] xl:grid-cols-[minmax(0,1fr)_minmax(0,540px)] lg:items-center">
            <!-- Intro -->
            <div class="text-center lg:text-start lg:col-start-1 lg:row-start-1">
                <p class="eyebrow eyebrow--light rise justify-center lg:justify-start"><?= __('site_name') ?></p>
                <h1 class="rise mt-4 text-[2.15rem] leading-[1.08] sm:text-5xl xl:text-[3.6rem]" style="--d:.08s">
                    <span class="text-gradient"><?= __('home_title') ?></span>
                </h1>
                <p class="hero__lead rise mt-5 text-base sm:text-lg max-w-xl mx-auto lg:mx-0 leading-relaxed" style="--d:.16s">
                    <?= __('home_subtitle', count($offices), $countryCount) ?>
                </p>
                <div class="rise mt-8 flex flex-col sm:flex-row sm:flex-wrap gap-3 justify-center lg:justify-start" style="--d:.24s">
                    <button type="button" data-nf-locate class="btn btn--primary btn--lg nf-locate-btn sm:whitespace-nowrap">
                        <span class="nf-radar" aria-hidden="true"><i class="ph-fill ph-navigation-arrow"></i></span>
                        <span data-nf-locate-label><?= __('btn_locate') ?></span>
                    </button>
                    <a href="<?= getBaseUrl() ?>/map" class="btn btn--ghost btn--lg sm:whitespace-nowrap">
                        <i class="ph-fill ph-globe-hemisphere-west" aria-hidden="true"></i><?= __('btn_map') ?>
                    </a>
                </div>
            </div>

            <!-- Finder card (filled in by assets/js/nearest-finder.js). ?find=1 starts the location search on load. -->
            <div class="rise lg:col-start-2 lg:row-start-1 lg:row-span-2" style="--d:.3s">
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

            <!-- Stats -->
            <dl class="rise grid grid-cols-2 sm:grid-cols-4 gap-3 lg:col-start-1 lg:row-start-2 lg:self-start" style="--d:.36s">
                <div class="stat-tile"><dt class="sr-only"><?= __('stat_offices') ?></dt><dd><span class="stat-tile__n block" data-count="<?= count($offices) ?>" data-suffix="+"><?= count($offices) ?>+</span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_offices') ?></span></dd></div>
                <div class="stat-tile"><dt class="sr-only"><?= __('stat_countries') ?></dt><dd><span class="stat-tile__n block" data-count="<?= $countryCount ?>"><?= $countryCount ?></span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_countries') ?></span></dd></div>
                <div class="stat-tile"><dt class="sr-only"><?= __('stat_support') ?></dt><dd><span class="stat-tile__n block">24/7</span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_support') ?></span></dd></div>
                <div class="stat-tile"><dt class="sr-only"><?= __('stat_served') ?></dt><dd><span class="stat-tile__n block" data-count="90" data-suffix="+">90+</span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_served') ?></span></dd></div>
            </dl>
        </div>
    </div>
</section>

<!-- Explore -->
<section class="py-16 sm:py-20 lg:py-24">
    <div class="wrap">
        <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14 reveal">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-navy"><?= __('explore_heading') ?></h2>
            <div class="mx-auto mt-5 h-1 w-16 rounded-full bg-gradient-to-r from-aqua to-navy"></div>
        </div>

        <?php
        $explore = [
            ['url' => getBaseUrl() . '/offices', 'icon' => 'ph-list-dashes',     'h' => __('explore_offices_h'), 'p' => __('explore_offices_p'), 'ext' => false],
            ['url' => getBaseUrl() . '/map',     'icon' => 'ph-map-trifold',     'h' => __('explore_map_h'),     'p' => __('explore_map_p'),     'ext' => false],
            ['url' => 'https://www.acibadem.com.tr/acibademonline/#/login', 'icon' => 'ph-desktop-tower', 'h' => __('explore_online_h'), 'p' => __('explore_online_p'), 'ext' => true],
        ];
        ?>
        <div class="grid gap-5 md:grid-cols-3">
            <?php foreach ($explore as $i => $c): ?>
            <a href="<?= e($c['url']) ?>"<?= $c['ext'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
               class="card card--hover group relative overflow-hidden p-7 sm:p-8 flex flex-col reveal" style="--d:<?= $i * 0.08 ?>s">
                <span class="absolute top-6 end-7 text-5xl font-extrabold text-surface-2 select-none transition-colors duration-500 group-hover:text-aqua-soft" aria-hidden="true">0<?= $i + 1 ?></span>
                <span class="icon-chip relative"><i class="ph-fill <?= $c['icon'] ?>" aria-hidden="true"></i></span>
                <h3 class="relative mt-6 text-xl font-bold text-ink group-hover:text-navy transition-colors"><?= $c['h'] ?></h3>
                <p class="relative mt-2 text-sm text-muted leading-relaxed flex-1"><?= $c['p'] ?></p>
                <span class="relative mt-6 inline-flex items-center gap-2 text-sm font-bold text-azure">
                    <span class="h-9 w-9 rounded-full bg-aqua-soft inline-flex items-center justify-center transition-colors duration-300 group-hover:bg-aqua group-hover:text-white">
                        <i class="ph <?= $c['ext'] ? 'ph-arrow-up-right' : 'ph-arrow-right arrow' ?>" aria-hidden="true"></i>
                    </span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Insurance & contracted institutions -->
        <div class="dark-card mt-6 p-7 sm:p-10 lg:p-12 reveal">
            <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-start flex-1">
                    <span class="w-16 h-16 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center flex-shrink-0">
                        <i class="ph-fill ph-shield-check text-3xl text-[#8fd6f3]" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-extrabold"><?= __('inst_card_h') ?></h3>
                        <p class="mt-3 text-[#cfdcef] leading-relaxed max-w-2xl"><?= __('inst_card_p') ?></p>
                    </div>
                </div>
                <a href="<?= getBaseUrl() ?>/contracted-institutions" class="btn btn--white btn--lg flex-shrink-0 w-full lg:w-auto">
                    <?= __('inst_card_btn') ?> <i class="ph ph-arrow-right arrow" aria-hidden="true"></i>
                </a>
            </div>
            <div class="ticker mt-9" dir="ltr" aria-hidden="true">
                <div class="ticker__track">
                    <?php for ($k = 0; $k < 2; $k++): foreach ($institutions as $inst): ?>
                    <span class="ticker__item"><?= e($inst) ?></span>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>window.NF_CONFIG = <?= json_encode($finderConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset('assets/js/tz-countries.js') ?>" defer></script>
<script src="<?= asset('assets/js/nearest-finder.js') ?>" defer></script>

<?php require __DIR__ . '/../footer.php'; ?>
