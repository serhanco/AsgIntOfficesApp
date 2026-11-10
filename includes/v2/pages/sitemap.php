<?php
/** Sitemap for people. $groups (pages, offices, events) and $officesByCountry come from sitemap-page.php. */
require __DIR__ . '/../header.php';
?>

<section class="page-head">
    <div class="wrap py-14 sm:py-20 text-center">
        <h1 class="rise text-3xl sm:text-5xl font-extrabold max-w-3xl mx-auto"><?= __('sitemap_title') ?></h1>
        <p class="rise mt-4 text-[#cfdcef] text-base sm:text-lg max-w-2xl mx-auto leading-relaxed" style="--d:.06s"><?= __('sitemap_subtitle') ?></p>
    </div>
</section>

<div class="wrap py-10 sm:py-14">
    <section aria-labelledby="sm-pages">
        <h2 id="sm-pages" class="text-xl font-extrabold text-navy"><?= __('sitemap_pages') ?></h2>
        <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
            <?php foreach ($groups['pages'] as $entry): ?>
            <li><a class="block card card--hover p-4 font-bold text-ink hover:text-navy transition-colors" href="<?= e(seoUrl($entry['path'])) ?>"><?= e($entry['label']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if ($officesByCountry): ?>
    <section aria-labelledby="sm-offices" class="mt-10">
        <h2 id="sm-offices" class="text-xl font-extrabold text-navy"><?= __('sitemap_offices') ?></h2>
        <?php foreach ($officesByCountry as $country => $list): ?>
        <h3 class="mt-6 text-sm font-bold uppercase tracking-wide text-muted"><?= e($country) ?></h3>
        <ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
            <?php foreach ($list as $entry): ?>
            <li><a class="block card card--hover p-4 font-semibold text-ink hover:text-navy transition-colors" href="<?= e(seoUrl($entry['path'])) ?>"><?= e($entry['label']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($groups['events']): ?>
    <section aria-labelledby="sm-events" class="mt-10">
        <h2 id="sm-events" class="text-xl font-extrabold text-navy"><?= __('sitemap_events') ?></h2>
        <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
            <?php foreach ($groups['events'] as $entry): ?>
            <li><a class="block card card--hover p-4 font-semibold text-ink hover:text-navy transition-colors" dir="auto" href="<?= e(seoUrl($entry['path'])) ?>"><?= e($entry['label']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
