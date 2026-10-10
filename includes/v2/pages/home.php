<?php
/** New design: home page with the nearest-office finder. Data comes from index.php. */
$countryCount = count(array_unique(array_column($offices, 'country')));
$institutions = require __DIR__ . '/../../institutions.php';
$extraHead = '<link rel="preload" as="image" href="' . getBaseUrl() . '/assets/images/home-hero.webp" fetchpriority="high">'
           . '<link rel="stylesheet" href="' . asset('assets/css/finder.css') . '">'
           . '<link rel="stylesheet" href="' . asset('assets/css/finder-v2.css') . '">';
require __DIR__ . '/../header.php';
?>

<?php
// Upcoming events for the Events panel: the visitor's country first (test param or Cloudflare; the browser refines it later)
require_once __DIR__ . '/../events-ui.php';
$homeCc = $testCountry ?: $serverCountry;
$homeEvents = array_values(array_filter(sortEvents(getEvents()), fn($e) => $e['status'] === 'upcoming'));
$upcomingCount = count($homeEvents);
$nearCount = 0;
if ($homeCc !== '') {
    $near = array_values(array_filter($homeEvents, fn($e) => in_array($homeCc, $e['countries'], true)));
    $nearCount = count($near);
    $homeEvents = array_merge($near, array_values(array_filter($homeEvents, fn($e) => !in_array($homeCc, $e['countries'], true))));
}
$homeEvents = array_slice($homeEvents, 0, 6);
?>
<section class="hero">
    <img src="<?= getBaseUrl() ?>/assets/images/home-hero.webp" alt="" class="hero__img" fetchpriority="high" decoding="async">
    <div class="hero__shade"></div>
    <div class="hero__grid"></div>
    <div class="hero__glow -top-40 end-[-10rem]"></div>

    <div class="wrap relative pt-10 pb-14 sm:pt-14 lg:pt-16 lg:pb-24">
        <!-- Intro -->
        <div class="text-center max-w-3xl mx-auto">
            <p class="eyebrow eyebrow--light rise justify-center"><?= __('site_name') ?></p>
            <h1 class="rise mt-4 text-[2.15rem] leading-[1.08] sm:text-5xl xl:text-[3.4rem]" style="--d:.08s">
                <span class="text-gradient"><?= __('home_title') ?></span>
            </h1>
            <p class="hero__lead rise mt-4 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed" style="--d:.16s">
                <?= __('home_subtitle', count($offices), $countryCount) ?>
            </p>
            <!-- Phones: jump to either section -->
            <div class="rise mt-6 grid grid-cols-2 gap-2 lg:hidden" style="--d:.2s">
                <a href="#panel-offices" class="btn btn--ghost btn--sm"><i class="ph-fill ph-buildings" aria-hidden="true"></i><?= __('home_offices_h') ?></a>
                <a href="#panel-events" class="btn btn--ghost btn--sm"><i class="ph-fill ph-calendar-star" aria-hidden="true"></i><?= __('nav_events') ?></a>
            </div>
        </div>

        <!-- The two main sections, side by side -->
        <div class="mt-9 lg:mt-12 grid gap-8 lg:gap-6 xl:gap-8 lg:grid-cols-2 lg:items-start">

            <!-- Offices: filled in by assets/js/nearest-finder.js. ?find=1 starts the location search on load. -->
            <div id="panel-offices" class="rise scroll-mt-28" style="--d:.24s">
                <div class="panel-head">
                    <span class="panel-head__icon"><i class="ph-fill ph-buildings" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <h2><?= __('home_offices_h') ?></h2>
                        <p><?= __('home_offices_p') ?></p>
                    </div>
                    <a href="<?= getBaseUrl() ?>/map" class="panel-head__link"><?= __('nav_map') ?><i class="ph ph-arrow-right arrow" aria-hidden="true"></i></a>
                </div>
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

            <!-- Events -->
            <div id="panel-events" class="rise scroll-mt-28" style="--d:.3s">
                <div class="panel-head">
                    <span class="panel-head__icon"><i class="ph-fill ph-calendar-star" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <h2><?= __('nav_events') ?></h2>
                        <p><?= __('home_events_p') ?></p>
                    </div>
                    <a href="<?= getBaseUrl() ?>/events" class="panel-head__link"><?= __('events_see_all') ?><i class="ph ph-arrow-right arrow" aria-hidden="true"></i></a>
                </div>
                <section class="nf-card ev-panel" data-ev-panel data-near="<?= e(__('events_near_h')) ?>" data-all="<?= e(__('events_upcoming')) ?>">
                    <?php if ($homeEvents): ?>
                    <p class="nf-label" data-ev-title><?= $nearCount ? __('events_near_h') : __('events_upcoming') ?></p>
                    <ul class="mt-4 space-y-2.5" data-ev-list>
                        <?php foreach ($homeEvents as $i => $ev) echo v2EventRow($ev, $i >= 3); ?>
                    </ul>
                    <div class="mt-5 pt-5 border-t border-line grid grid-cols-2 gap-2.5">
                        <a href="<?= getBaseUrl() ?>/events" class="btn btn--navy btn--sm"><i class="ph-fill ph-list-bullets" aria-hidden="true"></i><?= __('events_view_list') ?></a>
                        <a href="<?= getBaseUrl() ?>/events?view=calendar" class="btn btn--line btn--sm"><i class="ph-fill ph-calendar-blank" aria-hidden="true"></i><?= __('events_view_calendar') ?></a>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-6">
                        <span class="icon-chip mx-auto w-16 h-16 rounded-2xl text-3xl"><i class="ph-fill ph-calendar-star" aria-hidden="true"></i></span>
                        <h3 class="mt-4 text-xl font-extrabold text-navy"><?= __('events_none_h') ?></h3>
                        <p class="mt-2 text-sm text-muted"><?= __('events_none_p') ?></p>
                    </div>
                    <?php endif; ?>
                </section>

                <a href="<?= getBaseUrl() ?>/events#request" class="ev-request">
                    <span class="ev-request__icon"><i class="ph-fill ph-paper-plane-tilt" aria-hidden="true"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold text-white"><?= __('events_req_h') ?></span>
                        <span class="block text-sm text-[#cfdcef]"><?= __('events_req_btn') ?></span>
                    </span>
                    <i class="ph ph-arrow-right arrow text-white/70" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <!-- Stats -->
        <dl class="rise mt-10 lg:mt-14 grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-3xl mx-auto" style="--d:.36s">
            <div class="stat-tile"><dt class="sr-only"><?= __('stat_offices') ?></dt><dd><span class="stat-tile__n block" data-count="<?= count($offices) ?>"><?= count($offices) ?></span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_offices') ?></span></dd></div>
            <div class="stat-tile"><dt class="sr-only"><?= __('stat_countries') ?></dt><dd><span class="stat-tile__n block" data-count="<?= $countryCount ?>"><?= $countryCount ?></span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_countries') ?></span></dd></div>
            <div class="stat-tile"><dt class="sr-only"><?= __('stat_support') ?></dt><dd><span class="stat-tile__n block">24/7</span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_support') ?></span></dd></div>
            <div class="stat-tile"><dt class="sr-only"><?= __('stat_served') ?></dt><dd><span class="stat-tile__n block" data-count="<?= statServed()[0] ?>"<?= statServed()[1] !== '' ? ' data-suffix="+"' : '' ?>><?= statServed()[0] . statServed()[1] ?></span><span class="stat-tile__l block" aria-hidden="true"><?= __('stat_served') ?></span></dd></div>
        </dl>
    </div>
</section>

<script>
// Events panel: put events in the visitor's country first once the finder has worked out where they are
document.addEventListener('nf:country', function (e) {
    var panel = document.querySelector('[data-ev-panel]'), list = panel && panel.querySelector('[data-ev-list]');
    var cc = e.detail && e.detail.country;
    if (!list || !cc) return;
    var rows = Array.prototype.slice.call(list.children);
    var hit = function (r) { return (' ' + r.dataset.cc + ' ').indexOf(' ' + cc + ' ') !== -1; };
    var sorted = rows.filter(hit).concat(rows.filter(function (r) { return !hit(r); }));
    sorted.forEach(function (r, i) { list.appendChild(r); r.hidden = i >= 3; });
    var t = panel.querySelector('[data-ev-title]');
    if (t) t.textContent = rows.some(hit) ? panel.dataset.near : panel.dataset.all;
});
</script>

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
