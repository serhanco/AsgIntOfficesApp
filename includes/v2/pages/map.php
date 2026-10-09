<?php
/** New design: world map of all offices. Data comes from map.php. */
require __DIR__ . '/../header.php';
$countryCount = count(array_unique(array_column($offices, 'country')));
?>

<div class="relative">
    <div id="global-map" class="w-full z-0" style="height: calc(100vh - var(--header-h)); height: calc(100dvh - var(--header-h));"></div>

    <div class="absolute top-4 inset-x-4 sm:inset-x-auto sm:start-6 sm:top-6 z-[1000] sm:w-[24rem] rise">
        <div class="rounded-3xl bg-white/90 border border-white shadow-card-hover backdrop-blur-xl p-5" style="-webkit-backdrop-filter: blur(20px)">
            <p class="eyebrow"><?= __('site_name') ?></p>
            <h1 class="mt-2 text-2xl font-extrabold text-navy"><?= __('map_floating_h') ?></h1>
            <p class="mt-1 text-sm text-muted"><?= __('map_floating_p') ?></p>
            <div class="mt-4 flex items-center gap-2">
                <span class="count-pill"><?= count($offices) ?> <?= __('stat_offices') ?></span>
                <span class="count-pill"><?= $countryCount ?> <?= __('stat_countries') ?></span>
                <a href="<?= getBaseUrl() ?>/offices" class="btn btn--navy btn--sm ms-auto">
                    <i class="ph-fill ph-list-dashes" aria-hidden="true"></i><?= __('map_list_view') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initMap !== 'function' || typeof L === 'undefined') return;
    const offices = <?= json_encode(array_map(fn($o) => [
        'lat' => (float)$o['latitude'],
        'lon' => (float)$o['longitude'],
        'display_name' => e($o['display_name']),
        'country' => e($o['country']),
        'url' => officeUrl($o['slug']),
    ], $offices), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const map = initMap('global-map', offices, { markerHtml: '<div class="map-pin"><i class="ph-fill ph-buildings"></i></div>' });
    if (map && offices.length && window.innerWidth >= 640) {
        const rtl = document.documentElement.dir === 'rtl'; // the info panel sits on the start side
        map.fitBounds(offices.map((o) => [o.lat, o.lon]), { paddingTopLeft: [rtl ? 40 : 420, 40], paddingBottomRight: [rtl ? 420 : 40, 40], maxZoom: 5 });
    }
});
</script>
</main>
</body>
</html>
