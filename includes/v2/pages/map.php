<?php
/** New design: world map of all offices. Data comes from map.php. */
require __DIR__ . '/../header.php';
$countryCount = count(array_unique(array_column($offices, 'country')));
?>

<div class="relative">
    <div id="global-map" class="w-full z-0" style="height: calc(100vh - var(--header-h)); height: calc(100dvh - var(--header-h));"></div>

    <div class="absolute top-4 inset-x-4 sm:inset-x-auto sm:start-6 sm:top-6 z-[1000] sm:w-[24rem] rise">
        <div class="rounded-3xl bg-white/90 border border-white shadow-card-hover backdrop-blur-xl p-5" data-map-panel style="-webkit-backdrop-filter: blur(20px)">
            <p class="eyebrow"><?= __('site_name') ?></p>
            <h1 class="mt-2 text-2xl font-extrabold text-navy"><?= __('map_floating_h') ?></h1>
            <p class="mt-1 text-sm text-muted"><?= __('map_floating_p') ?></p>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="count-pill"><?= count($offices) ?> <?= __('stat_offices') ?></span>
                <span class="count-pill"><?= $countryCount ?> <?= __('stat_countries') ?></span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="button" class="btn btn--primary btn--sm near-btn" data-near-me>
                    <i class="ph-fill ph-crosshair" aria-hidden="true"></i><span data-near-me-label><?= __('nav_nearest') ?></span>
                </button>
                <a href="<?= getBaseUrl() ?>/offices" class="btn btn--navy btn--sm">
                    <i class="ph-fill ph-list-dashes" aria-hidden="true"></i><?= __('map_list_view') ?>
                </a>
            </div>
            <div class="near-result" data-near-result aria-live="polite" hidden></div>
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
    const rtl = document.documentElement.dir === 'rtl'; // the info panel sits on the start side
    // Keep what we zoom to out from under the info panel (beside it on wide screens, below it on phones)
    const padding = () => {
        if (window.innerWidth >= 640) return { paddingTopLeft: [rtl ? 40 : 420, 40], paddingBottomRight: [rtl ? 420 : 40, 40] };
        const panel = document.querySelector('[data-map-panel]');
        return { paddingTopLeft: [30, (panel ? panel.offsetHeight : 200) + 40], paddingBottomRight: [30, 30] };
    };
    if (map && offices.length && window.innerWidth >= 640) {
        map.fitBounds(offices.map((o) => [o.lat, o.lon]), Object.assign(padding(), { maxZoom: 5 }));
    }

    // "Nearest Office": mark the visitor and zoom to them and the closest office
    let me = null;
    document.addEventListener('v2:nearest', (e) => {
        const d = e.detail, near = d.list[0];
        if (!map || !near) return;
        if (me) me.remove();
        me = L.marker([d.lat, d.lon], { icon: L.divIcon({ className: 'bg-transparent', html: '<span class="map-me"></span>', iconSize: [22, 22], iconAnchor: [11, 11] }), zIndexOffset: 1000, keyboard: false }).addTo(map);
        map.flyToBounds([[d.lat, d.lon], [near.lat, near.lon]], Object.assign(padding(), { maxZoom: 11, duration: 1.2 }));
    });
});
window.NEAR_OFFICES = <?= json_encode(array_map(fn($o) => [
    'lat' => $o['latitude'] !== null ? (float)$o['latitude'] : null, 'lon' => $o['longitude'] !== null ? (float)$o['longitude'] : null,
    'name' => $o['display_name'], 'country' => $o['country'], 'url' => officeUrl($o['slug']),
], $offices), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
</main>
</body>
</html>
