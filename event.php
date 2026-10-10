<?php
/**
 * One event (/event/{slug}): what, when and where, with contact buttons per stop.
 * /event/{slug}.ics (or ?ics=1) downloads it for the phone calendar.
 * A new page, so it only exists in the new design.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/v2/events-ui.php';

$slug = (string)($_GET['slug'] ?? '');
$event = $slug !== '' ? getEventBySlug($slug) : null;

if (!$event) {
    require __DIR__ . '/404.php';
    exit;
}

if (isset($_GET['ics'])) {
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9-]/', '', $event['slug']) . '.ics"');
    echo eventIcs($event);
    exit;
}

$past     = $event['status'] === 'past';
$dir      = textDir($event['title'] . ' ' . $event['description']);
$stops    = $event['locations'];
$hasDates = (bool)array_filter($stops, fn($l) => $l['starts_on']);
$mapStops = array_values(array_filter($stops, fn($l) => !$l['is_online'] && $l['lat'] !== null));
$icsUrl   = $event['url'] . '.ics';
$fileUrl  = $event['file_url'] !== '' ? imageUrl($event['file_url']) : '';
$people   = $event['people'];
$program  = $event['program'];
$contact  = eventContact($event, strtolower((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
$needContact = !$past && ($event['relates_hq'] || !array_filter($stops, fn($l) => $l['office']));
$link     = $event['link_url'] && preg_match('#^https?://#i', $event['link_url']) ? $event['link_url'] : '';

// Other upcoming events, the ones sharing a country first
$others = array_values(array_filter(sortEvents(getEvents()), fn($e) => $e['id'] !== $event['id'] && $e['status'] === 'upcoming'));
usort($others, fn($a, $b) => (int)!array_intersect($a['countries'], $event['countries']) <=> (int)!array_intersect($b['countries'], $event['countries']));
$others = array_slice($others, 0, 2);

$pageTitle = $event['title'];
$currentPage = 'events';
$needsMap = (bool)$mapStops;
$metaDescription = trim(mb_substr(preg_replace('/\s+/', ' ', $event['description']), 0, 160)) ?: __('events_subtitle');
if ($event['image_url']) $ogImage = imageUrl($event['image_url']);
$heroImg = $event['image_url'] ? imageUrl($event['image_url']) : '';

require __DIR__ . '/includes/v2/header.php';
?>

<section class="page-head<?= $heroImg ? ' page-head--img' : '' ?>">
    <?php if ($heroImg): ?><img src="<?= e($heroImg) ?>" alt="" class="hero__img opacity-40" fetchpriority="high" decoding="async"><?php endif; ?>
    <div class="wrap wrap--narrow py-10 sm:py-16">
        <a href="<?= getBaseUrl() ?>/events" class="rise inline-flex items-center gap-2 text-sm font-semibold text-[#9fc6f5] hover:text-white transition-colors">
            <i class="ph ph-arrow-left arrow-back" aria-hidden="true"></i><?= __('events_title') ?>
        </a>
        <div class="mt-6 flex items-start gap-4 sm:gap-6">
            <div class="rise hidden sm:block flex-shrink-0" style="--d:.04s"><?= v2DateBadge($event['start'], $event['end'], $past, 'lg') ?></div>
            <div class="min-w-0">
                <div class="rise flex items-center gap-2 flex-wrap" style="--d:.06s">
                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1"><?= v2EventTag($event['tag_key']) ?></span>
                    <?php if ($past): ?><span class="rounded-full bg-white/10 border border-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider"><?= __('event_past_badge') ?></span><?php endif; ?>
                    <?= v2EventBadges($event) ?>
                </div>
                <h1 class="rise event-text mt-4 text-3xl sm:text-4xl md:text-5xl font-extrabold leading-tight" style="--d:.1s" dir="<?= $dir ?>"><?= e($event['title']) ?></h1>
                <p class="rise mt-3 text-[#cfdcef] text-base sm:text-lg flex items-center gap-2 flex-wrap" style="--d:.14s">
                    <i class="ph-fill ph-calendar-blank text-aqua" aria-hidden="true"></i><?= v2DateText($event['start'], $event['end']) ?>
                </p>
                <?php if ($stops): ?>
                <div class="rise mt-4 flex flex-wrap gap-1.5" style="--d:.18s"><?= v2StopChips($event, 6, 'dark') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="rise mt-8 flex flex-wrap gap-3" style="--d:.22s">
            <?php if ($hasDates && !$past): ?>
            <a href="<?= e($icsUrl) ?>" class="btn btn--primary"><i class="ph-fill ph-calendar-plus" aria-hidden="true"></i><?= __('event_add_cal') ?></a>
            <?php endif; ?>
            <?php if ($fileUrl): ?>
            <a href="<?= e($fileUrl) ?>" target="_blank" rel="noopener" class="btn btn--ghost"><i class="ph ph-file-pdf" aria-hidden="true"></i><?= __('event_download') ?></a>
            <?php endif; ?>
            <?php if ($link): ?>
            <a href="<?= e($link) ?>" target="_blank" rel="noopener" class="btn btn--ghost"><i class="ph ph-arrow-square-out" aria-hidden="true"></i><?= __('event_more_info') ?></a>
            <?php endif; ?>
            <button type="button" class="btn btn--ghost" data-share data-title="<?= e($event['title']) ?>" data-url="<?= e($event['url']) ?>" data-copied="<?= e(__('event_copied')) ?>">
                <i class="ph ph-share-network" aria-hidden="true"></i><span><?= __('event_share') ?></span>
            </button>
        </div>
    </div>
</section>

<div class="wrap wrap--narrow py-10 sm:py-14 space-y-6 sm:space-y-8">
    <?php if ($past): ?>
    <div class="rounded-3xl bg-coral-soft border border-coral/20 text-coral-ink px-5 py-4 flex items-center gap-3 reveal">
        <i class="ph-fill ph-info text-xl flex-shrink-0" aria-hidden="true"></i>
        <p class="text-sm font-semibold flex-1"><?= __('event_ended') ?></p>
        <a href="<?= getBaseUrl() ?>/events" class="text-sm font-bold underline underline-offset-2 whitespace-nowrap"><?= __('events_upcoming') ?></a>
    </div>
    <?php endif; ?>

    <div class="grid gap-6 sm:gap-8 <?= $mapStops ? 'lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]' : '' ?>">
        <div class="space-y-6 sm:space-y-8 min-w-0">
            <?php if (trim($event['description']) !== ''): ?>
            <section class="card p-6 sm:p-8 reveal">
                <div class="event-text text-[1.02rem] text-ink leading-relaxed whitespace-pre-line" dir="<?= $dir ?>"><?= e(trim($event['description'])) ?></div>
            </section>
            <?php endif; ?>

            <?php if ($people): ?>
            <section class="reveal">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-navy"><?= __('event_people') ?></h2>
                    <span class="count-pill"><?= count($people) ?></span>
                </div>
                <ul class="grid sm:grid-cols-2 gap-3">
                    <?php foreach ($people as $p):
                        $photo = $p['photo_url'] !== '' ? imageUrl($p['photo_url']) : '';
                        $initials = mb_strtoupper(mb_substr(preg_replace('/^(prof\.?|dr\.?|doç\.?|assoc\.?)\s+/iu', '', trim($p['name'])), 0, 1));
                    ?>
                    <li class="card p-4 flex items-center gap-4">
                        <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="" width="56" height="56" loading="lazy" class="w-14 h-14 rounded-full object-cover flex-shrink-0">
                        <?php else: ?><span class="icon-chip w-14 h-14 rounded-full text-xl font-extrabold flex-shrink-0" aria-hidden="true"><?= e($initials) ?></span><?php endif; ?>
                        <div class="min-w-0">
                            <p class="text-[0.7rem] font-bold uppercase tracking-wider text-azure"><?= __('event_role_' . $p['role']) ?></p>
                            <p class="event-text font-bold text-ink leading-snug" dir="auto"><?= e(trim($p['title'] . ' ' . $p['name'])) ?></p>
                            <?php $sub = trim($p['specialty'] . ($p['specialty'] !== '' && $p['organization'] !== '' ? ' · ' : '') . $p['organization']); if ($sub !== ''): ?>
                            <p class="event-text text-sm text-muted leading-snug" dir="auto"><?= e($sub) ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <?php if ($program):
                $days = [];
                foreach ($program as $r) $days[(string)$r['day']][] = $r;
            ?>
            <section class="reveal">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-navy"><?= __('event_program') ?></h2>
                </div>
                <div class="space-y-4">
                    <?php foreach ($days as $day => $rows): ?>
                    <div class="card p-5 sm:p-6">
                        <?php if ($day !== ''): ?>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-azure mb-3 flex items-center gap-2"><i class="ph-fill ph-calendar-blank" aria-hidden="true"></i><?= v2DateText($day, $day) ?></h3>
                        <?php endif; ?>
                        <ul class="divide-y divide-line">
                            <?php foreach ($rows as $r): ?>
                            <?php if ($r['kind'] === 'heading'): ?>
                            <li class="pt-4 pb-2 first:pt-0"><p class="event-text font-extrabold text-navy" dir="auto"><?= e($r['title']) ?></p></li>
                            <?php else: ?>
                            <li class="py-2.5 flex gap-4">
                                <span class="w-24 flex-shrink-0 text-sm font-semibold text-muted tabular-nums" dir="ltr"><?= e($r['start_time'] ?? '') ?><?= $r['end_time'] ? '–' . e($r['end_time']) : '' ?></span>
                                <span class="min-w-0">
                                    <span class="event-text block text-ink leading-snug" dir="auto"><?= e($r['title']) ?></span>
                                    <?php if ($r['speakers'] !== ''): ?><span class="event-text block text-sm text-muted" dir="auto"><?= e($r['speakers']) ?></span><?php endif; ?>
                                </span>
                            </li>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="reveal">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-navy"><?= __('event_stops') ?></h2>
                    <span class="count-pill"><?= count($stops) ?></span>
                </div>
                <ol class="space-y-4">
                    <?php foreach ($stops as $i => $l):
                        $tel = $l['phone'] !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $l['phone']) : '';
                        $wa  = $l['whatsapp'] !== '' ? 'https://wa.me/' . $l['whatsapp'] . '?text=' . rawurlencode(__('event_wa_msg', $event['title'])) : '';
                        $stopPast = $l['ends_on'] && $l['ends_on'] < date('Y-m-d');
                    ?>
                    <li id="stop-<?= $i + 1 ?>" class="card p-5 sm:p-6 scroll-mt-28<?= $stopPast && !$past ? ' opacity-60' : '' ?>">
                        <div class="flex gap-4">
                            <?= v2DateBadge($l['starts_on'], $l['ends_on'], $past || $stopPast) ?>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-azure"><?= v2DateText($l['starts_on'], $l['ends_on'], $l['start_time'], $l['end_time']) ?></p>
                                <?php if ($l['is_online']): ?>
                                <h3 class="mt-1 text-lg font-extrabold text-ink flex items-center gap-2"><i class="ph-fill ph-video-camera text-aqua" aria-hidden="true"></i><?= __('event_online') ?></h3>
                                <?php elseif ($l['floating']): ?>
                                <?php else: ?>
                                <h3 class="mt-1 text-lg font-extrabold text-ink flex items-center gap-2 min-w-0">
                                    <?php if ($l['country_code']): ?><img src="<?= e(getFlagUrl($l['country_code'])) ?>" alt="<?= e($l['country']) ?>" width="22" height="16" class="w-[22px] h-4 object-cover rounded-[3px] flex-shrink-0"><?php endif; ?>
                                    <bdi class="truncate"><?= e($l['name']) ?></bdi>
                                </h3>
                                <?php if ($l['address'] !== '' || ($l['at_venue'] && $l['city'] !== '')): ?>
                                <p class="mt-1 text-sm text-muted leading-relaxed"><bdi><?= e(trim($l['address'] . ($l['at_venue'] && $l['city'] !== '' && stripos($l['address'], $l['city']) === false ? ($l['address'] !== '' ? ', ' : '') . $l['city'] : ''))) ?></bdi></p>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($l['office'] && ($l['at_venue'] || $l['is_online'])): ?>
                                <p class="mt-2 text-xs text-muted-2"><?= __('event_contact_office', '<a href="' . e($l['office_url']) . '" class="font-semibold text-azure hover:underline"><bdi>' . e($l['office']['display_name']) . '</bdi></a>') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$past && !$stopPast && ($tel || $wa || $l['maps_url'])): ?>
                        <div class="mt-4 pt-4 border-t border-line grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                            <?php if ($wa): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn btn--sm bg-wa text-white hover:-translate-y-0.5"><i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i><?= __('js_whatsapp') ?></a><?php endif; ?>
                            <?php if ($tel): ?><a href="<?= e($tel) ?>" class="btn btn--sm btn--line"><i class="ph-fill ph-phone" aria-hidden="true"></i><?= __('js_call') ?></a><?php endif; ?>
                            <?php if ($l['maps_url']): ?><a href="<?= e($l['maps_url']) ?>" target="_blank" rel="noopener" class="btn btn--sm btn--line"><i class="ph-fill ph-navigation-arrow" aria-hidden="true"></i><?= __('office_cta_route') ?></a><?php endif; ?>
                            <?php if ($l['office'] && !$l['at_venue'] && !$l['is_online']): ?><a href="<?= e($l['office_url']) ?>" class="btn btn--sm btn--line"><i class="ph-fill ph-buildings" aria-hidden="true"></i><?= __('event_office_page') ?></a><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <?php if ($event['relates_hq'] || $event['relations']): ?>
            <section class="reveal">
                <h2 class="text-xl sm:text-2xl font-extrabold text-navy mb-4"><?= __('event_related') ?></h2>
                <div class="flex flex-wrap gap-2">
                    <?php if ($event['relates_hq']): ?><span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1.5 text-sm font-semibold text-ink"><i class="ph-fill ph-seal-check text-azure" aria-hidden="true"></i><?= __('event_from_hq') ?></span><?php endif; ?>
                    <?php foreach ($event['relations'] as $r): ?>
                    <?php if ($r['kind'] === 'office'): ?>
                    <a href="<?= e(officeUrl($r['office']['slug'])) ?>" class="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1.5 text-sm font-semibold text-ink hover:border-aqua"><i class="ph-fill ph-buildings text-azure" aria-hidden="true"></i><bdi><?= e($r['office']['display_name']) ?></bdi></a>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1.5 text-sm font-semibold text-ink"><img src="<?= e(getFlagUrl($r['country_code'])) ?>" alt="" width="18" height="13" loading="lazy" class="w-[18px] h-[13px] object-cover rounded-[3px]"><bdi><?= e($r['country']) ?></bdi></span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($needContact):
                $ctel = $contact['phone'] !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $contact['phone']) : '';
                $cwa  = $contact['whatsapp'] !== '' ? 'https://wa.me/' . $contact['whatsapp'] . '?text=' . rawurlencode(__('event_wa_msg', $event['title'])) : '';
            ?>
            <section class="card p-5 sm:p-6 reveal">
                <h2 class="text-lg font-extrabold text-navy"><?= __('event_contact_h') ?></h2>
                <p class="mt-1 text-sm text-muted"><?= $contact['office'] ? '<bdi>' . e($contact['office']['display_name']) . '</bdi>' : __('event_contact_hq') ?></p>
                <div class="mt-4 grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                    <?php if ($cwa): ?><a href="<?= e($cwa) ?>" target="_blank" rel="noopener" class="btn btn--sm bg-wa text-white hover:-translate-y-0.5"><i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i><?= __('js_whatsapp') ?></a><?php endif; ?>
                    <?php if ($ctel): ?><a href="<?= e($ctel) ?>" class="btn btn--sm btn--line"><i class="ph-fill ph-phone" aria-hidden="true"></i><?= __('js_call') ?></a><?php endif; ?>
                    <?php if ($contact['email'] !== ''): ?><a href="mailto:<?= e($contact['email']) ?>" class="btn btn--sm btn--line"><i class="ph-fill ph-envelope-simple" aria-hidden="true"></i><?= __('js_email') ?></a><?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>

        <?php if ($mapStops): ?>
        <aside class="lg:sticky lg:top-[calc(var(--header-h)+1.5rem)] self-start reveal" style="--d:.08s">
            <div class="card relative overflow-hidden h-[320px] lg:h-[460px]">
                <div id="event-map" class="absolute inset-0 z-0"></div>
            </div>
        </aside>
        <?php endif; ?>
    </div>

    <?php if ($others): ?>
    <section class="pt-4 reveal">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="text-xl sm:text-2xl font-extrabold text-navy"><?= __('event_other') ?></h2>
            <a href="<?= getBaseUrl() ?>/events" class="text-sm font-bold text-azure hover:text-navy inline-flex items-center gap-1.5"><?= __('events_see_all') ?><i class="ph ph-arrow-right arrow" aria-hidden="true"></i></a>
        </div>
        <div class="grid md:grid-cols-2 gap-4 sm:gap-5">
            <?php foreach ($others as $o) echo v2EventCard($o); ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php if ($mapStops): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initMap !== 'function' || typeof L === 'undefined') return;
    var stops = <?= json_encode(array_map(fn($l) => [
        'lat' => $l['lat'],
        'lon' => $l['lon'],
        'display_name' => e($l['name']),
        'country' => e($l['country']),
        'url' => '#stop-' . (array_search($l, $stops, true) + 1),
    ], $mapStops), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var map = initMap('event-map', stops, {
        center: [stops[0].lat, stops[0].lon],
        zoom: 14,
        singleOffice: stops.length === 1,
        markerHtml: '<div class="map-pin"><i class="ph-fill <?= eventIcon($event['tag_key']) ?>"></i></div>'
    });
    if (map && stops.length > 1) map.fitBounds(stops.map(function (s) { return [s.lat, s.lon]; }), { padding: [50, 50], maxZoom: 12 });
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/v2/footer.php'; ?>
