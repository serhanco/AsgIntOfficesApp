<?php
/**
 * Events list (/events). A new page, so it only exists in the new design.
 * Upcoming events first; past ones folded away at the bottom.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/v2/events-ui.php';

$events   = sortEvents(getEvents());
$upcoming = array_values(array_filter($events, fn($e) => $e['status'] === 'upcoming'));
$past     = array_values(array_filter($events, fn($e) => $e['status'] === 'past'));

// Filters: only countries and types that upcoming events actually have
$countryNames = [];
$types = [];
foreach ($upcoming as $ev) {
    foreach ($ev['locations'] as $l) {
        if ($l['country_code'] !== '') $countryNames[$l['country_code']] = $l['country'];
    }
    $types[$ev['tag_key']] = true;
}
asort($countryNames);
$types = array_values(array_intersect(array_keys(EVENT_TYPES), array_keys($types)));

$pageTitle = __('events_title');
$currentPage = 'events';
$needsMap = false;
$metaDescription = __('events_subtitle');

require __DIR__ . '/includes/v2/header.php';
?>

<section class="page-head">
    <div class="wrap py-12 sm:py-16 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="rise">
            <p class="eyebrow eyebrow--light"><?= __('site_name') ?></p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-extrabold"><?= __('events_title') ?></h1>
            <p class="mt-3 text-[#cfdcef] text-base sm:text-lg max-w-2xl"><?= __('events_subtitle') ?></p>
        </div>
        <?php if ($upcoming): ?>
        <div class="rise flex items-center gap-3 self-start md:self-auto" style="--d:.1s">
            <span class="stat-tile !py-3 !px-5">
                <span class="stat-tile__n"><?= count($upcoming) ?></span>
                <span class="stat-tile__l block"><?= __('events_upcoming') ?></span>
            </span>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($upcoming && (count($countryNames) > 1 || count($types) > 1)): ?>
<div class="sticky z-40 top-[var(--header-h)] bg-surface/85 backdrop-blur-xl border-b border-line" style="-webkit-backdrop-filter: blur(20px)">
    <div class="wrap py-3 sm:py-4 flex flex-col md:flex-row md:items-center gap-3">
        <?php if (count($types) > 1): ?>
        <div class="flex gap-2 overflow-x-auto no-scrollbar -mx-1 px-1 flex-1" role="group" aria-label="<?= e(__('events_filter_type')) ?>">
            <button type="button" class="filter-pill" aria-pressed="true" data-type-filter=""><?= __('events_filter_type') ?></button>
            <?php foreach ($types as $t): ?>
            <button type="button" class="filter-pill" aria-pressed="false" data-type-filter="<?= e($t) ?>"><i class="ph-fill <?= eventIcon($t) ?>" aria-hidden="true"></i><?= __($t) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (count($countryNames) > 1): ?>
        <div class="relative w-full md:w-72 md:ms-auto">
            <select id="event-country" class="field !h-12" aria-label="<?= e(__('events_filter_country')) ?>">
                <option value=""><?= __('events_filter_country') ?></option>
                <?php foreach ($countryNames as $cc => $name): ?>
                <option value="<?= e($cc) ?>"><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="ph ph-caret-down absolute end-5 top-1/2 -translate-y-1/2 text-muted pointer-events-none" aria-hidden="true"></i>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="wrap py-10 sm:py-14">
    <?php if ($upcoming): ?>
    <div class="grid md:grid-cols-2 gap-4 sm:gap-5" data-event-list>
        <?php foreach ($upcoming as $i => $ev): ?>
        <div class="reveal" style="--d:<?= min($i, 5) * 0.05 ?>s" data-event-item data-type="<?= e($ev['tag_key']) ?>" data-cc="<?= e(implode(' ', $ev['countries'])) ?>">
            <?= v2EventCard($ev, 'h-full') ?>
        </div>
        <?php endforeach; ?>
    </div>
    <p id="events-no-match" class="hidden text-center text-muted py-12"><?= __('events_no_match') ?></p>
    <?php else: ?>
    <div class="card p-8 sm:p-12 text-center max-w-2xl mx-auto reveal">
        <span class="icon-chip mx-auto w-16 h-16 rounded-2xl text-3xl"><i class="ph-fill ph-calendar-star" aria-hidden="true"></i></span>
        <h2 class="mt-5 text-xl sm:text-2xl font-extrabold text-navy"><?= __('events_none_h') ?></h2>
        <p class="mt-2 text-muted"><?= __('events_none_p') ?></p>
        <div class="mt-7 flex flex-col sm:flex-row justify-center gap-3">
            <a href="<?= getBaseUrl() ?>/?find=1" class="btn btn--primary"><i class="ph-fill ph-target" aria-hidden="true"></i><?= __('nav_nearest') ?></a>
            <a href="<?= getBaseUrl() ?>/offices" class="btn btn--line"><i class="ph-fill ph-buildings" aria-hidden="true"></i><?= __('nav_all_offices') ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($past): ?>
    <details class="past-events mt-12 sm:mt-16">
        <summary class="flex items-center gap-3 cursor-pointer select-none">
            <h2 class="text-xl font-extrabold text-navy"><?= __('events_past') ?></h2>
            <span class="count-pill"><?= count($past) ?></span>
            <i class="ph ph-caret-down text-muted ms-auto transition-transform" aria-hidden="true"></i>
        </summary>
        <div class="mt-5 grid md:grid-cols-2 gap-4 sm:gap-5">
            <?php foreach ($past as $ev): ?>
            <?= v2EventCard($ev) ?>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>
</div>

<?php if ($upcoming): ?>
<script>
(function () {
    var items = document.querySelectorAll('[data-event-item]');
    var pills = document.querySelectorAll('[data-type-filter]');
    var country = document.getElementById('event-country');
    var none = document.getElementById('events-no-match');
    var type = '';
    function apply() {
        var cc = country ? country.value : '';
        var shown = 0;
        items.forEach(function (el) {
            var ok = (!type || el.dataset.type === type) && (!cc || (' ' + el.dataset.cc + ' ').indexOf(' ' + cc + ' ') !== -1);
            el.hidden = !ok;
            if (ok) { shown++; el.classList.add('in'); }
        });
        if (none) none.classList.toggle('hidden', shown > 0);
    }
    pills.forEach(function (p) {
        p.addEventListener('click', function () {
            type = p.dataset.typeFilter;
            pills.forEach(function (q) { q.setAttribute('aria-pressed', String(q === p)); });
            apply();
        });
    });
    if (country) country.addEventListener('change', apply);
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/v2/footer.php'; ?>
