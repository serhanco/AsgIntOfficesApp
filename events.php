<?php
/**
 * Events hub (/events): list, calendar and map views over the same filters, plus "request an event".
 * A new page, so it only exists in the new design.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/v2/events-ui.php';

$events   = sortEvents(getEvents());
$upcoming = array_values(array_filter($events, fn($e) => $e['status'] === 'upcoming'));
$past     = array_values(array_filter($events, fn($e) => $e['status'] === 'past'));

// Filters: only countries and types that events actually have
$countryNames = [];
$types = [];
$audiences = [];
foreach ($events as $ev) {
    foreach ($ev['countries'] as $cc) $countryNames[$cc] = countryNameFor($cc);
    $types[$ev['tag_key']] = true;
    $audiences[$ev['audience']] = true;
}
asort($countryNames);
$types = array_values(array_intersect(array_keys(EVENT_TYPES), array_keys($types)));

// Data for the calendar and map views (event text is set with textContent in the browser)
$hasCoords = false;
$jsEvents = array_map(function ($ev) use (&$hasCoords) {
    $stops = array_map(function ($l) use (&$hasCoords) {
        if (!$l['is_online'] && $l['lat'] !== null) $hasCoords = true;
        return ['s' => $l['starts_on'], 'e' => $l['ends_on'], 'city' => $l['is_online'] ? '' : ($l['city'] ?: $l['name']), 'cc' => $l['country_code'],
                'online' => $l['is_online'], 'lat' => $l['is_online'] ? null : $l['lat'], 'lon' => $l['is_online'] ? null : $l['lon']];
    }, $ev['locations']);
    return ['url' => $ev['url'], 'title' => $ev['title'], 'tag' => $ev['tag_key'], 'tagLabel' => __($ev['tag_key']), 'icon' => $ev['icon'],
            'past' => $ev['status'] === 'past', 'cc' => $ev['countries'], 'aud' => $ev['audience'], 'g' => $ev['is_global'], 'stops' => $stops,
            'q' => mb_strtolower($ev['title'] . ' ' . implode(' ', array_column($stops, 'city')), 'UTF-8')];
}, $events);

$view = in_array($_GET['view'] ?? '', ['calendar', 'map'], true) ? $_GET['view'] : 'list';
$firstDate = null;
foreach ($upcoming as $ev) { if ($ev['start']) { $firstDate = max($ev['start'], date('Y-m-d')); break; } }

$pageTitle = __('events_title');
$currentPage = 'events';
$needsMap = $hasCoords;
$metaDescription = __('events_subtitle');

require __DIR__ . '/includes/v2/header.php';

$hqWa = 'https://wa.me/905359650466';
$hqMail = 'international@acibadem.com';
?>

<section class="page-head">
    <div class="wrap py-12 sm:py-16 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="rise">
            <p class="eyebrow eyebrow--light"><?= __('site_name') ?></p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-extrabold"><?= __('events_title') ?></h1>
            <p class="mt-3 text-[#cfdcef] text-base sm:text-lg max-w-2xl"><?= __('events_subtitle') ?></p>
        </div>
        <div class="rise flex items-center gap-3 self-start md:self-auto" style="--d:.1s">
            <?php if ($upcoming): ?>
            <span class="stat-tile !py-3 !px-5">
                <span class="stat-tile__n"><?= count($upcoming) ?></span>
                <span class="stat-tile__l block"><?= __('events_upcoming') ?></span>
            </span>
            <?php endif; ?>
            <a href="#request" class="btn btn--primary"><i class="ph-fill ph-paper-plane-tilt" aria-hidden="true"></i><?= __('events_req_btn') ?></a>
        </div>
    </div>
</section>

<?php if ($events): ?>
<!-- Toolbar: search, view switch, filters -->
<div class="sticky z-40 top-[var(--header-h)] bg-surface/85 backdrop-blur-xl border-b border-line" style="-webkit-backdrop-filter: blur(20px)">
    <div class="wrap py-3 sm:py-4 space-y-3">
        <div class="flex flex-col md:flex-row gap-3 md:items-center">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute start-5 top-1/2 -translate-y-1/2 text-muted text-xl pointer-events-none" aria-hidden="true"></i>
                <input type="search" id="event-search" class="field !h-12" placeholder="<?= e(__('events_search_ph')) ?>" aria-label="<?= e(__('events_search_ph')) ?>" autocomplete="off">
            </div>
            <div class="seg self-start md:self-auto" role="group" aria-label="View">
                <button type="button" data-view="list" aria-pressed="<?= $view === 'list' ? 'true' : 'false' ?>"><i class="ph-fill ph-list-bullets" aria-hidden="true"></i><?= __('events_view_list') ?></button>
                <button type="button" data-view="calendar" aria-pressed="<?= $view === 'calendar' ? 'true' : 'false' ?>"><i class="ph-fill ph-calendar-blank" aria-hidden="true"></i><?= __('events_view_calendar') ?></button>
                <?php if ($hasCoords): ?>
                <button type="button" data-view="map" aria-pressed="<?= $view === 'map' ? 'true' : 'false' ?>"><i class="ph-fill ph-map-trifold" aria-hidden="true"></i><?= __('events_view_map') ?></button>
                <?php endif; ?>
            </div>
        </div>
        <?php if (count($audiences) > 1): ?>
        <div class="flex gap-2 overflow-x-auto no-scrollbar -mx-1 px-1" role="group" aria-label="<?= e(__('events_filter_aud')) ?>">
            <button type="button" class="filter-pill" aria-pressed="true" data-aud-filter=""><?= __('events_filter_aud') ?></button>
            <button type="button" class="filter-pill" aria-pressed="false" data-aud-filter="b2c"><i class="ph-fill ph-users-three" aria-hidden="true"></i><?= __('aud_b2c') ?></button>
            <button type="button" class="filter-pill" aria-pressed="false" data-aud-filter="b2b"><i class="ph-fill ph-buildings" aria-hidden="true"></i><?= __('aud_b2b') ?></button>
        </div>
        <?php endif; ?>
        <?php if (count($types) > 1 || count($countryNames) > 1): ?>
        <div class="flex flex-col md:flex-row md:items-center gap-3">
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
        <?php endif; ?>
    </div>
</div>

<div class="wrap py-10 sm:py-14">
    <!-- List view -->
    <div id="view-list"<?= $view !== 'list' ? ' hidden' : '' ?>>
        <?php if ($upcoming): ?>
        <div class="grid md:grid-cols-2 gap-4 sm:gap-5">
            <?php foreach ($upcoming as $ev): ?>
            <div data-event-item data-i="<?= array_search($ev['id'], array_column($events, 'id'), true) ?>">
                <?= v2EventCard($ev, 'h-full') ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card p-8 sm:p-12 text-center max-w-2xl mx-auto">
            <span class="icon-chip mx-auto w-16 h-16 rounded-2xl text-3xl"><i class="ph-fill ph-calendar-star" aria-hidden="true"></i></span>
            <h2 class="mt-5 text-xl sm:text-2xl font-extrabold text-navy"><?= __('events_none_h') ?></h2>
            <p class="mt-2 text-muted"><?= __('events_none_p') ?></p>
        </div>
        <?php endif; ?>
        <p id="events-no-match" class="hidden text-center text-muted py-12"><?= __('events_no_match') ?></p>

        <?php if ($past): ?>
        <details class="past-events mt-12 sm:mt-16">
            <summary class="flex items-center gap-3 cursor-pointer select-none">
                <h2 class="text-xl font-extrabold text-navy"><?= __('events_past') ?></h2>
                <span class="count-pill"><?= count($past) ?></span>
                <i class="ph ph-caret-down text-muted ms-auto transition-transform" aria-hidden="true"></i>
            </summary>
            <div class="mt-5 grid md:grid-cols-2 gap-4 sm:gap-5">
                <?php foreach ($past as $ev): ?>
                <div data-event-item data-i="<?= array_search($ev['id'], array_column($events, 'id'), true) ?>"><?= v2EventCard($ev, 'h-full') ?></div>
                <?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>
    </div>

    <!-- Calendar view -->
    <div id="view-calendar"<?= $view !== 'calendar' ? ' hidden' : '' ?>>
        <div class="grid lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] gap-6 items-start">
            <section class="card cal p-4 sm:p-6" aria-label="<?= e(__('events_view_calendar')) ?>">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <button type="button" class="w-11 h-11 rounded-full border border-line inline-flex items-center justify-center hover:bg-surface" data-cal-prev aria-label="<?= e(__('events_cal_prev')) ?>"><i class="ph ph-caret-left arrow-flip" aria-hidden="true"></i></button>
                    <h2 class="text-lg sm:text-xl font-extrabold text-navy" data-cal-title aria-live="polite"></h2>
                    <button type="button" class="w-11 h-11 rounded-full border border-line inline-flex items-center justify-center hover:bg-surface" data-cal-next aria-label="<?= e(__('events_cal_next')) ?>"><i class="ph ph-caret-right arrow-flip" aria-hidden="true"></i></button>
                </div>
                <div class="cal__grid" data-cal-grid></div>
            </section>
            <section aria-live="polite">
                <h3 class="text-lg font-extrabold text-navy mb-3" data-cal-day-title></h3>
                <div class="space-y-3" data-cal-day></div>
            </section>
        </div>
    </div>

    <!-- Map view -->
    <?php if ($hasCoords): ?>
    <div id="view-map"<?= $view !== 'map' ? ' hidden' : '' ?>>
        <div class="card overflow-hidden"><div id="events-map" aria-label="<?= e(__('events_view_map')) ?>"></div></div>
        <p id="events-map-none" class="hidden text-center text-muted py-8"><?= __('events_no_match') ?></p>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="wrap py-12 sm:py-16">
    <div class="card p-8 sm:p-12 text-center max-w-2xl mx-auto">
        <span class="icon-chip mx-auto w-16 h-16 rounded-2xl text-3xl"><i class="ph-fill ph-calendar-star" aria-hidden="true"></i></span>
        <h2 class="mt-5 text-xl sm:text-2xl font-extrabold text-navy"><?= __('events_none_h') ?></h2>
        <p class="mt-2 text-muted"><?= __('events_none_p') ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Request an event: stored for the admin (Talepler) and/or sent through WhatsApp / e-mail with the message filled in -->
<section id="request" class="pb-16 sm:pb-24 scroll-mt-28">
    <div class="wrap">
        <div class="dark-card p-6 sm:p-10 grid lg:grid-cols-2 gap-8 items-center">
            <div>
                <span class="icon-chip !bg-white/10 !text-[#8fd6f3]"><i class="ph-fill ph-paper-plane-tilt" aria-hidden="true"></i></span>
                <h2 class="mt-5 text-2xl sm:text-3xl font-extrabold"><?= __('events_req_h') ?></h2>
                <p class="mt-3 text-[#cfdcef] max-w-md"><?= __('events_req_p') ?></p>
            </div>
            <form id="req-form" class="space-y-3" novalidate data-msg="<?= e(__('events_req_msg')) ?>" data-ok="<?= e(__('events_req_thanks')) ?>" data-err="<?= e(__('events_req_err')) ?>" data-url="<?= e(getBaseUrl()) ?>/api/event-request">
                <div class="relative">
                    <label for="req-type" class="sr-only"><?= __('events_req_type') ?></label>
                    <select id="req-type" class="field !bg-white/10 !text-white !border-white/25">
                        <?php foreach (EVENT_REQUEST_TYPES as $k): ?>
                        <option value="<?= e($k) ?>" class="text-ink"><?= __($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="ph ph-caret-down absolute end-5 top-1/2 -translate-y-1/2 text-white/70 pointer-events-none" aria-hidden="true"></i>
                </div>
                <div>
                    <label for="req-where" class="sr-only"><?= __('events_req_where') ?></label>
                    <input type="text" id="req-where" class="field !bg-white/10 !text-white !border-white/25 placeholder:!text-white/60" placeholder="<?= e(__('events_req_where')) ?>" autocomplete="off">
                </div>
                <div>
                    <label for="req-contact" class="sr-only"><?= __('events_req_contact') ?></label>
                    <input type="text" id="req-contact" class="field !bg-white/10 !text-white !border-white/25 placeholder:!text-white/60" placeholder="<?= e(__('events_req_contact')) ?>" autocomplete="email" maxlength="200">
                </div>
                <input type="text" name="website" id="req-website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                <button type="submit" class="btn btn--primary w-full !mt-4" data-req-send><i class="ph-fill ph-paper-plane-tilt" aria-hidden="true"></i><span><?= __('events_req_send') ?></span></button>
                <p class="text-xs text-[#9fb3d1]"><?= __('events_req_note') ?></p>
                <p id="req-status" class="text-sm font-semibold hidden" role="status" aria-live="polite"></p>
                <div class="grid sm:grid-cols-2 gap-3 pt-1">
                    <a href="<?= e($hqWa) ?>" data-req-wa target="_blank" rel="noopener" class="btn btn--ghost"><i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i><?= __('js_whatsapp') ?></a>
                    <a href="mailto:<?= e($hqMail) ?>" data-req-mail class="btn btn--ghost"><i class="ph-fill ph-envelope-simple" aria-hidden="true"></i><?= __('js_email') ?></a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    var EVENTS = <?= json_encode($jsEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var START = <?= json_encode($firstDate) ?>;
    var lang = document.documentElement.lang || 'en';
    var rtlWeek = /^(ar|fa)/.test(lang);
    var state = { view: <?= json_encode($view) ?>, type: '', aud: '', cc: '', q: '', y: 0, m: 0, sel: null };
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    // ---- helpers
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function iso(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
    function utc(s) { var p = s.split('-'); return new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])); }
    function fmt(s, o) { try { return new Intl.DateTimeFormat(lang, Object.assign({ timeZone: 'UTC' }, o)).format(utc(s)); } catch (e) { return s; } }
    function matches(ev) {
        if (state.type && ev.tag !== state.type) return false;
        if (state.aud && ev.aud !== state.aud) return false;
        if (state.cc && !ev.g && ev.cc.indexOf(state.cc) === -1) return false; // events without a place or country suit every country
        if (state.q && ev.q.indexOf(state.q) === -1) return false;
        return true;
    }
    function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }

    // ---- list
    function applyList() {
        var shown = 0;
        $$('[data-event-item]').forEach(function (n) {
            var ok = matches(EVENTS[+n.dataset.i]);
            n.hidden = !ok;
            if (ok && !EVENTS[+n.dataset.i].past) shown++;
        });
        var none = $('#events-no-match');
        if (none) none.classList.toggle('hidden', shown > 0 || !EVENTS.some(function (e) { return !e.past; }));
    }

    // ---- calendar
    function eventsOn(day) {
        return EVENTS.filter(function (ev) {
            return matches(ev) && ev.stops.some(function (s) { return s.s && s.s <= day && day <= (s.e || s.s); });
        });
    }
    function renderCal() {
        var grid = $('[data-cal-grid]');
        if (!grid) return;
        grid.textContent = '';
        $('[data-cal-title]').textContent = fmt(iso(state.y, state.m, 1), { month: 'long', year: 'numeric' });
        var first = new Date(Date.UTC(state.y, state.m, 1)).getUTCDay(); // 0 = Sunday
        var lead = rtlWeek ? (first + 1) % 7 : (first + 6) % 7;        // Saturday- or Monday-first
        var days = new Date(Date.UTC(state.y, state.m + 1, 0)).getUTCDate();
        for (var i = 0; i < 7; i++) {
            // 2024-01-06 is a Saturday, 2024-01-01 a Monday
            var dow = rtlWeek ? '2024-01-' + pad(6 + i) : '2024-01-' + pad(1 + i);
            grid.appendChild(el('div', 'cal__dow', fmt(dow, { weekday: 'short' })));
        }
        for (var b = 0; b < lead; b++) grid.appendChild(el('div'));
        var today = new Date(), todayIso = iso(today.getFullYear(), today.getMonth(), today.getDate());
        for (var d = 1; d <= days; d++) {
            var day = iso(state.y, state.m, d), evs = eventsOn(day);
            var cell = el(evs.length ? 'button' : 'div', 'cal__day' + (evs.length ? ' has-ev' : '') + (day === todayIso ? ' is-today' : '') + (day === state.sel ? ' is-sel' : '') + (day < todayIso ? ' is-past' : ''));
            cell.appendChild(el('span', null, String(d)));
            if (evs.length) {
                cell.type = 'button';
                cell.dataset.day = day;
                cell.setAttribute('aria-label', fmt(day, { day: 'numeric', month: 'long' }) + ' · ' + evs.length);
                var dots = el('span', 'cal__dots');
                evs.slice(0, 3).forEach(function (ev) { var dot = document.createElement('i'); dot.dataset.t = ev.tag; dots.appendChild(dot); });
                cell.appendChild(dots);
            }
            grid.appendChild(cell);
        }
        renderDay();
    }
    function renderDay() {
        var box = $('[data-cal-day]'), title = $('[data-cal-day-title]');
        if (!box) return;
        box.textContent = '';
        var evs = state.sel ? eventsOn(state.sel) : [];
        if (!state.sel) {
            // No day picked: list this month's events
            var seen = {};
            var days = new Date(Date.UTC(state.y, state.m + 1, 0)).getUTCDate();
            for (var d = 1; d <= days; d++) eventsOn(iso(state.y, state.m, d)).forEach(function (ev) { if (!seen[ev.url]) { seen[ev.url] = 1; evs.push(ev); } });
            title.textContent = fmt(iso(state.y, state.m, 1), { month: 'long', year: 'numeric' });
        } else {
            title.textContent = fmt(state.sel, { weekday: 'long', day: 'numeric', month: 'long' });
        }
        if (!evs.length) { box.appendChild(el('p', 'text-muted text-sm py-4', <?= json_encode(__('events_cal_none')) ?>)); return; }
        evs.forEach(function (ev) {
            var a = el('a', 'card card--hover flex gap-3 p-4 items-start');
            a.href = ev.url;
            var ico = el('span', 'icon-chip'); var i = document.createElement('i'); i.className = 'ph-fill ' + ev.icon; ico.appendChild(i);
            var body = el('div', 'min-w-0');
            body.appendChild(el('p', 'text-[0.7rem] font-bold uppercase tracking-wider text-azure', ev.tagLabel));
            var t = el('p', 'event-text font-bold text-ink leading-snug', ev.title); t.dir = 'auto'; body.appendChild(t);
            var cities = ev.stops.filter(function (s) { return s.city; }).map(function (s) { return s.city; });
            if (cities.length) body.appendChild(el('p', 'text-sm text-muted mt-1', cities.join(' · ')));
            a.appendChild(ico); a.appendChild(body); box.appendChild(a);
        });
    }
    function calStart() {
        var base = START ? utc(START) : new Date();
        state.y = START ? base.getUTCFullYear() : base.getFullYear();
        state.m = START ? base.getUTCMonth() : base.getMonth();
    }
    function shiftMonth(n) {
        state.m += n;
        if (state.m < 0) { state.m = 11; state.y--; } else if (state.m > 11) { state.m = 0; state.y++; }
        state.sel = null;
        renderCal();
    }

    // ---- map
    var map = null, layer = null;
    function renderMap() {
        var holder = $('#events-map');
        if (!holder || typeof L === 'undefined') return;
        if (!map) {
            map = L.map(holder, { zoomControl: false, scrollWheelZoom: false }).setView([41, 29], 4);
            map.attributionControl.setPrefix(false);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);
            L.control.zoom({ position: 'bottomright' }).addTo(map);
            layer = L.layerGroup().addTo(map);
        }
        layer.clearLayers();
        var pts = [];
        EVENTS.forEach(function (ev) {
            if (ev.past || !matches(ev)) return;
            ev.stops.forEach(function (s) {
                if (s.lat == null || s.lon == null) return;
                var icon = L.divIcon({ className: 'bg-transparent', html: '<div class="map-pin"><i class="ph-fill ' + ev.icon + '"></i></div>', iconSize: [40, 40], iconAnchor: [20, 40] });
                var pop = el('div', 'ev-pop');
                pop.appendChild(el('p', 'text-[0.7rem] font-bold uppercase tracking-wider text-azure', ev.tagLabel));
                var t = el('p', 'font-bold text-ink', ev.title); t.dir = 'auto'; pop.appendChild(t);
                if (s.s) pop.appendChild(el('p', 'text-sm text-muted', fmt(s.s, { day: 'numeric', month: 'long' }) + (s.e && s.e !== s.s ? ' – ' + fmt(s.e, { day: 'numeric', month: 'long' }) : '') + (s.city ? ' · ' + s.city : '')));
                var a = el('a', 'text-sm', <?= json_encode(__('map_view_details')) ?>); a.href = ev.url; pop.appendChild(a);
                L.marker([s.lat, s.lon], { icon: icon }).bindPopup(pop).addTo(layer);
                pts.push([s.lat, s.lon]);
            });
        });
        var none = $('#events-map-none'); if (none) none.classList.toggle('hidden', pts.length > 0);
        map.invalidateSize();
        if (pts.length === 1) map.setView(pts[0], 9);
        else if (pts.length) map.fitBounds(pts, { padding: [50, 50], maxZoom: 7 });
    }

    // ---- views and filters
    function show(view) {
        state.view = view;
        ['list', 'calendar', 'map'].forEach(function (v) { var n = $('#view-' + v); if (n) n.hidden = v !== view; });
        $$('[data-view]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.view === view)); });
        try { var u = new URL(location.href); if (view === 'list') u.searchParams.delete('view'); else u.searchParams.set('view', view); history.replaceState(null, '', u); } catch (e) {}
        refresh();
    }
    function refresh() {
        applyList();
        if (state.view === 'calendar') renderCal();
        if (state.view === 'map') renderMap();
    }
    $$('[data-view]').forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.view); }); });
    $$('[data-type-filter]').forEach(function (p) {
        p.addEventListener('click', function () {
            state.type = p.dataset.typeFilter;
            $$('[data-type-filter]').forEach(function (q) { q.setAttribute('aria-pressed', String(q === p)); });
            refresh();
        });
    });
    $$('[data-aud-filter]').forEach(function (p) {
        p.addEventListener('click', function () {
            state.aud = p.dataset.audFilter;
            $$('[data-aud-filter]').forEach(function (q) { q.setAttribute('aria-pressed', String(q === p)); });
            refresh();
        });
    });
    var country = $('#event-country'); if (country) country.addEventListener('change', function () { state.cc = country.value; refresh(); });
    var search = $('#event-search'); if (search) search.addEventListener('input', function () { state.q = search.value.trim().toLowerCase(); refresh(); });
    var prev = $('[data-cal-prev]'), next = $('[data-cal-next]');
    if (prev) prev.addEventListener('click', function () { shiftMonth(-1); });
    if (next) next.addEventListener('click', function () { shiftMonth(1); });
    var grid = $('[data-cal-grid]');
    if (grid) grid.addEventListener('click', function (e) {
        var c = e.target.closest('[data-day]'); if (!c) return;
        state.sel = state.sel === c.dataset.day ? null : c.dataset.day;
        renderCal();
    });
    calStart();
    document.addEventListener('DOMContentLoaded', function () { show(state.view); });

    // ---- request an event: stored for the admin, and the message is filled into the WhatsApp / e-mail links
    var form = $('#req-form');
    if (form) {
        var type = $('#req-type'), where = $('#req-where'), contact = $('#req-contact'), wa = $('[data-req-wa]'), mail = $('[data-req-mail]');
        var status = $('#req-status'), send = $('[data-req-send]');
        function label() { return type.options[type.selectedIndex].text; }
        function msg() {
            var place = where.value.trim() || '…';
            return form.dataset.msg
                .replace('%1$s', function () { return label(); }).replace('%2$s', function () { return place; })
                .replace('%s', function () { return label(); }).replace('%s', function () { return place; });
        }
        function update() {
            var m = encodeURIComponent(msg());
            wa.href = 'https://wa.me/905359650466?text=' + m;
            mail.href = 'mailto:international@acibadem.com?subject=' + encodeURIComponent(label()) + '&body=' + m;
        }
        function payload(channel) {
            return JSON.stringify({ type: type.value, place: where.value, contact: contact.value, channel: channel, website: $('#req-website').value });
        }
        function say(text, ok) {
            status.textContent = text;
            status.classList.remove('hidden');
            status.style.color = ok ? '#7ef0a8' : '#ffb4a6';
        }
        type.addEventListener('change', update); where.addEventListener('input', update); update();

        // Opening WhatsApp or e-mail is also noted for the admin (nothing is lost if this fails)
        ['wa', 'mail'].forEach(function (k) {
            var a = k === 'wa' ? wa : mail;
            a.addEventListener('click', function () {
                try {
                    var blob = new Blob([payload(k === 'wa' ? 'whatsapp' : 'email')], { type: 'application/json' });
                    if (!(navigator.sendBeacon && navigator.sendBeacon(form.dataset.url, blob))) {
                        fetch(form.dataset.url, { method: 'POST', body: blob, keepalive: true });
                    }
                } catch (e) {}
            });
        });

        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var digits = contact.value.replace(/\D/g, '');
            var okContact = contact.value.indexOf('@') > 0 ? /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(contact.value.trim()) : digits.length >= 6;
            if (!okContact) { contact.focus(); contact.setAttribute('aria-invalid', 'true'); say(contact.placeholder, false); return; }
            contact.removeAttribute('aria-invalid');
            send.disabled = true;
            fetch(form.dataset.url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: payload('form') })
                .then(function (r) { return r.json().then(function (j) { return r.ok && j.ok; }); })
                .then(function (ok) {
                    if (ok) { say(form.dataset.ok, true); send.style.display = 'none'; where.value = ''; contact.value = ''; update(); }
                    else { say(form.dataset.err, false); send.disabled = false; }
                })
                .catch(function () { say(form.dataset.err, false); send.disabled = false; });
        });
    }
})();
</script>

<?php require __DIR__ . '/includes/v2/footer.php'; ?>
