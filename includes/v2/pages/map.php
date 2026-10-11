<?php
/** New design: world map of all offices. Data comes from map.php. */
require_once __DIR__ . '/../../finder-config.php';
$finderConfig = finderConfig($offices);
$extraHead = '<link rel="stylesheet" href="' . asset('assets/css/finder.css') . '">'
           . '<link rel="stylesheet" href="' . asset('assets/css/finder-v2.css') . '">';
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
        </div>
    </div>

    <!-- Nearest Office answer (same rules as the home page: country with offices, no office, Türkiye) -->
    <div class="nf-sheet nf-sheet--map" data-nf-panel hidden>
        <button type="button" class="nf-sheet__close" data-nf-close aria-label="<?= e(__('nf_close')) ?>"><i class="ph ph-x" aria-hidden="true"></i></button>
        <section id="nf-card" class="nf-card" aria-live="polite">
        <div class="nf-skeleton"><span><?= __('nf_detecting') ?></span></div>
    </section>
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
    const wide = () => window.innerWidth >= 640;
    // Keep what we zoom to clear of the info panel (beside it on wide screens, above/below on phones)
    const padding = () => {
        if (wide()) return { paddingTopLeft: [rtl ? 40 : 420, 40], paddingBottomRight: [rtl ? 420 : 40, 40] };
        const top = document.querySelector('[data-map-panel]'), sheet = document.querySelector('[data-nf-panel]');
        return { paddingTopLeft: [30, (top ? top.offsetHeight : 160) + 30], paddingBottomRight: [30, (sheet && !sheet.hidden ? sheet.offsetHeight : 0) + 30] };
    };
    if (map && offices.length && wide()) {
        map.fitBounds(offices.map((o) => [o.lat, o.lon]), Object.assign(padding(), { maxZoom: 5 }));
    }

    // Nearest Office: mark the visitor and zoom to them and the office the finder picked
    let me = null;
    document.addEventListener('nf:focus', (e) => {
        if (!map) return;
        const d = e.detail, pts = [];
        if (me) { me.remove(); me = null; }
        if (d.position) {
            me = L.marker([d.position.lat, d.position.lon], { icon: L.divIcon({ className: 'bg-transparent', html: '<span class="map-me"></span>', iconSize: [22, 22], iconAnchor: [11, 11] }), zIndexOffset: 1000, keyboard: false }).addTo(map);
            pts.push([d.position.lat, d.position.lon]);
        }
        if (d.office && d.office.lat != null) pts.push([d.office.lat, d.office.lon]);
        if (pts.length > 1) map.flyToBounds(pts, Object.assign(padding(), { maxZoom: 11, duration: 1.2 }));
        else if (pts.length) map.flyTo(pts[0], d.position ? 7 : 6, { duration: 1.2 });
    });
    document.addEventListener('nf:close', () => { if (me) { me.remove(); me = null; } });
});
</script>
<script>window.NF_CONFIG = <?= json_encode($finderConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset('assets/js/tz-countries.js') ?>" defer></script>
<script src="<?= asset('assets/js/nearest-finder.js') ?>" defer></script>
</main>
</body>
</html>
