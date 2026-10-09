<?php
/** New design: all offices, grouped by country. Data comes from offices.php. */
require __DIR__ . '/../header.php';
?>

<section class="page-head">
    <div class="wrap py-12 sm:py-16 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="rise">
            <p class="eyebrow eyebrow--light"><?= __('site_name') ?></p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-extrabold"><?= __('offices_title') ?></h1>
            <p class="mt-3 text-[#cfdcef] text-base sm:text-lg"><?= __('offices_subtitle', $totalOffices, $totalCountries) ?></p>
        </div>
        <a href="<?= getBaseUrl() ?>/map" class="btn btn--ghost rise self-start md:self-auto" style="--d:.1s">
            <i class="ph-fill ph-map-trifold" aria-hidden="true"></i><?= __('offices_view_map') ?>
        </a>
    </div>
</section>

<!-- Search + country jump, sticks under the header -->
<div class="sticky z-40 top-[var(--header-h)] bg-surface/85 backdrop-blur-xl border-b border-line" style="-webkit-backdrop-filter: blur(20px)">
    <div class="wrap py-3 sm:py-4 flex flex-col md:flex-row gap-3">
        <div class="relative flex-1">
            <i class="ph ph-magnifying-glass absolute start-5 top-1/2 -translate-y-1/2 text-muted text-xl pointer-events-none" aria-hidden="true"></i>
            <input type="search" id="office-search" placeholder="<?= e(__('offices_search_ph')) ?>" aria-label="<?= e(__('offices_search_ph')) ?>" class="field">
        </div>
        <div class="relative w-full md:w-72">
            <select id="country-jump" onchange="scrollToCountry(this.value)" class="field" aria-label="<?= e(__('offices_jump_ph')) ?>">
                <option value=""><?= __('offices_jump_ph') ?></option>
                <?php foreach ($countries as $c): ?>
                <option value="<?= e(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $c['country']))) ?>"><?= e($c['country']) ?> (<?= $c['office_count'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <i class="ph ph-caret-down absolute end-5 top-1/2 -translate-y-1/2 text-muted pointer-events-none" aria-hidden="true"></i>
        </div>
    </div>
</div>

<div class="wrap py-10 sm:py-14">
    <div id="no-results" class="hidden text-center py-16">
        <span class="icon-chip mx-auto w-16 h-16 rounded-2xl text-3xl"><i class="ph-fill ph-magnifying-glass-minus" aria-hidden="true"></i></span>
        <h3 class="mt-5 text-xl font-bold text-navy"><?= __('offices_no_results_h') ?></h3>
        <p class="mt-2 text-muted"><?= __('offices_no_results_p') ?></p>
    </div>

    <div class="columns-1 md:columns-2 xl:columns-3 gap-5">
    <?php foreach ($grouped as $countryName => $countryOffices):
        $cSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $countryName));
        $cCode = $countryOffices[0]['country_code'];
    ?>
    <section id="country-<?= e($cSlug) ?>" class="country-group card break-inside-avoid mb-5 p-2 reveal">
        <div class="flex items-center gap-3 px-4 pt-4 pb-3">
            <?= getFlagImg($countryName, $cCode) ?>
            <h2 class="text-lg font-extrabold text-navy flex-1 min-w-0 truncate"><bdi><?= e($countryName) ?></bdi></h2>
            <span class="count-pill"><?= count($countryOffices) ?></span>
        </div>
        <div class="space-y-1">
            <?php foreach ($countryOffices as $o): ?>
            <a href="<?= officeUrl($o['slug']) ?>"
               class="office-card group flex items-center gap-3.5 p-3 rounded-2xl hover:bg-surface transition-colors"
               data-name="<?= strtolower(e($o['display_name'])) ?>"
               data-country="<?= strtolower(e($o['country'])) ?>"
               data-address="<?= strtolower(e($o['address'])) ?>">
                <span class="icon-chip w-11 h-11 text-xl rounded-xl"><i class="ph-fill ph-buildings" aria-hidden="true"></i></span>
                <span class="flex-1 min-w-0">
                    <span class="block font-bold text-ink leading-tight group-hover:text-navy transition-colors line-clamp-1"><bdi><?= e($o['display_name']) ?></bdi></span>
                    <span class="block mt-0.5 text-[0.8rem] text-muted line-clamp-1"><bdi><?= e($o['address']) ?></bdi></span>
                </span>
                <i class="ph ph-arrow-right arrow text-muted-2 group-hover:text-aqua transition-colors" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
