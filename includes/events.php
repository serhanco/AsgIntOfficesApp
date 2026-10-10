<?php
/**
 * Events: the second main entity next to offices.
 *
 * An event (events) has one or more stops (event_locations). A stop is held at an
 * office, or at another venue (venue_name/city/address) with the office as its contact.
 * Until database/patch_20261009_events.sql is applied, the old office_activities
 * table is read instead, so nothing breaks in between.
 */

/**
 * Event types, in the order the admin form lists them. Labels come from the lang files (act_tag_*), so every
 * type is translated. To add a type: add a line here, a label in every lang file and (for new icons) run npm run build:icons.
 * 'audience' is what a new event of that type starts with: b2c (patients and relatives) or b2b (institutions).
 */
const EVENT_TYPES = [
    'act_tag_doctor'       => ['label_tr' => 'Doktor görüşmesi / tanışma', 'icon' => 'ph-stethoscope',        'audience' => 'b2c'],
    'act_tag_consult'      => ['label_tr' => 'Konsültasyon günü (ofiste veya online)', 'icon' => 'ph-chats-circle',       'audience' => 'b2c'],
    'act_tag_seminar'      => ['label_tr' => 'Sağlık semineri / bilgilendirme', 'icon' => 'ph-chalkboard-teacher', 'audience' => 'b2c'],
    'act_tag_checkup'      => ['label_tr' => 'Check-up / tarama günü', 'icon' => 'ph-heartbeat',          'audience' => 'b2c'],
    'act_tag_webinar'      => ['label_tr' => 'Online / Webinar', 'icon' => 'ph-video-camera',       'audience' => 'b2c'],
    'act_tag_presentation' => ['label_tr' => 'Hekim sunumu', 'icon' => 'ph-presentation-chart', 'audience' => 'b2b'],
    'act_tag_exhibition'   => ['label_tr' => 'Fuar / Sergi', 'icon' => 'ph-handshake',          'audience' => 'b2b'],
    'act_tag_conference'   => ['label_tr' => 'Kongre / bilimsel toplantı', 'icon' => 'ph-microphone-stage',   'audience' => 'b2b'],
    'act_tag_visit'        => ['label_tr' => 'Kurum / devlet ziyareti ve görüşmesi', 'icon' => 'ph-bank',               'audience' => 'b2b'],
    'act_tag_insurer'      => ['label_tr' => 'Sigorta / anlaşmalı kurum görüşmesi', 'icon' => 'ph-shield-check',       'audience' => 'b2b'],
    'act_tag_other'        => ['label_tr' => 'Diğer (sektörel etkinlik vb.)', 'icon' => 'ph-calendar-star',      'audience' => 'b2b'],
];

/** The types a visitor can pick on the "request an event" form (a short, patient-facing list). */
const EVENT_REQUEST_TYPES = ['act_tag_doctor', 'act_tag_presentation', 'act_tag_exhibition', 'act_tag_webinar'];

/** Type => Turkish admin label. */
function eventTypeLabelsTr(): array {
    return array_map(fn($t) => $t['label_tr'], EVENT_TYPES);
}

/** Admin labels (the admin is Turkish only). */
const EVENT_ADMIN_AUDIENCE = ['b2c' => 'Hastalar ve yakınları (B2C)', 'b2b' => 'Kurumlar (B2B)'];
const EVENT_ADMIN_VISIBILITY = [
    'listed'   => 'Listede (herkese açık, listelerde ve takvimde görünür)',
    'link'     => 'Yalnız linkle (listelerde yok, adresi/QR\'ı olan açabilir)',
    'internal' => 'İç kayıt (sitede hiç görünmez, yalnız yönetimde)',
];
const EVENT_ADMIN_ROLES = ['speaker' => 'Konuşmacı', 'moderator' => 'Moderatör', 'chair' => 'Başkan / heyet başkanı', 'executive' => 'Yönetici', 'guest' => 'Misafir / katılımcı'];

/** Who an event is for. */
const EVENT_AUDIENCES = ['b2c', 'b2b'];

/** listed = in lists, calendar and on the home page. link = only reachable with its address (QR, invitation). internal = not on the site at all. */
const EVENT_VISIBILITY = ['listed', 'link', 'internal'];

/** What a person does at an event (label key event_role_*). */
const EVENT_ROLES = ['speaker', 'moderator', 'chair', 'executive', 'guest'];

function eventsReady(): bool {
    static $ready = null;
    if ($ready === null) {
        try {
            $ready = (bool)getDb()->query("SHOW TABLES LIKE 'event_locations'")->fetchColumn();
        } catch (\Throwable $e) {
            $ready = false;
        }
    }
    return $ready;
}

/** Have the stage 1 columns and tables (patch_20261011_events_relations.sql) been applied? Until then events behave as before. */
function eventsV2Ready(): bool {
    static $ready = null;
    if ($ready === null) {
        try {
            $db = getDb();
            $ready = eventsReady()
                && (bool)$db->query("SHOW COLUMNS FROM events LIKE 'audience'")->fetchColumn()
                && (bool)$db->query("SHOW TABLES LIKE 'event_sessions'")->fetchColumn();
        } catch (\Throwable $e) {
            $ready = false;
        }
    }
    return $ready;
}

function eventUrl(string $slug): string {
    return getBaseUrl() . '/event/' . rawurlencode($slug);
}

function eventIcon(string $tagKey): string {
    return EVENT_TYPES[$tagKey]['icon'] ?? 'ph-calendar-star';
}

/** Country name for a code: from our offices when we have one there, else the intl extension, else the code. */
function countryNameFor(?string $code, string $fallback = ''): string {
    $code = strtolower(trim((string)$code));
    if ($code === '') return $fallback;
    static $names = null;
    if ($names === null) {
        $names = [];
        foreach (getAllOffices() as $o) $names[strtolower((string)$o['country_code'])] = $o['country'];
    }
    if (isset($names[$code])) return $names[$code];
    if (class_exists('Locale')) {
        $n = \Locale::getDisplayRegion('-' . strtoupper($code), 'en');
        if ($n && strcasecmp($n, $code) !== 0) return $n;
    }
    return $fallback !== '' ? $fallback : strtoupper($code);
}

/**
 * All events with their locations, relations, people and program. Each event:
 *   id, slug, url, tag_key, icon, audience, visibility, featured_rank, relates_hq, title, description, image_url, link_url,
 *   file_url, is_published, start, end (Y-m-d or null), status ('upcoming' | 'past'), countries [codes],
 *   is_global (no place, no office, no country, not head office), office_ids [offices it is linked to],
 *   relations: [{kind: office|country, office (row|null), country_code, country}],
 *   people: [{id, name, title, specialty, organization, photo_url, is_acibadem, role}],
 *   program: [{day, start_time, end_time, kind, title, speakers}],
 *   locations: [office (row|null), name, city, country_code, country, address, lat, lon, floating,
 *               is_online, starts_on, ends_on, start_time, end_time, phone, email, whatsapp, maps_url]
 *
 * $scope: 'listed' = what lists, the calendar and the home page show (default), 'public' = also link-only events
 * (the event page itself), 'all' = everything including internal ones (admin).
 * Events that were not tied to a location, an office or a country are "global": they are shown to every visitor.
 */
function getEvents(bool $publishedOnly = true, string $scope = 'listed'): array {
    static $cache = [];
    $key = ($publishedOnly ? 'p' : 'a') . $scope;
    if (isset($cache[$key])) return $cache[$key];
    if (!eventsReady()) return $cache[$key] = [];

    $v2 = eventsV2Ready();
    try {
        $db = getDb();
        $where = [];
        if ($publishedOnly) $where[] = 'is_published = 1';
        if ($v2 && $scope === 'listed') $where[] = "visibility = 'listed'";
        if ($v2 && $scope === 'public') $where[] = "visibility IN ('listed', 'link')";
        $events = $db->query("SELECT * FROM events" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY sort_order ASC, id ASC")->fetchAll();
        $stops = $db->query("SELECT * FROM event_locations ORDER BY sort_order ASC, starts_on IS NULL, starts_on ASC, id ASC")->fetchAll();
        $rels = $v2 ? $db->query("SELECT * FROM event_relations ORDER BY id ASC")->fetchAll() : [];
        $ppl = $v2 ? $db->query("SELECT ep.event_id, ep.role, p.* FROM event_people ep JOIN people p ON p.id = ep.person_id ORDER BY ep.sort_order ASC, ep.id ASC")->fetchAll() : [];
        $sess = $v2 ? $db->query("SELECT * FROM event_sessions ORDER BY day IS NULL, day ASC, sort_order ASC, id ASC")->fetchAll() : [];
    } catch (\Throwable $e) {
        return $cache[$key] = [];
    }

    $offices = [];
    foreach (getAllOffices(false) as $o) $offices[(int)$o['id']] = $o;

    $byEvent = $relByEvent = $pplByEvent = $sessByEvent = [];
    foreach ($stops as $s) $byEvent[(int)$s['event_id']][] = $s;
    foreach ($rels as $r) $relByEvent[(int)$r['event_id']][] = $r;
    foreach ($ppl as $p) $pplByEvent[(int)$p['event_id']][] = $p;
    foreach ($sess as $r) $sessByEvent[(int)$r['event_id']][] = $r;

    $today = date('Y-m-d');
    $out = [];
    foreach ($events as $ev) {
        $locations = [];
        $starts = $ends = $countries = $officeIds = [];
        foreach ($byEvent[(int)$ev['id']] ?? [] as $s) {
            $office = $s['office_id'] !== null ? ($offices[(int)$s['office_id']] ?? null) : null;
            $atVenue = trim((string)$s['venue_name']) !== '' || trim((string)$s['city']) !== '' || trim((string)$s['address']) !== '';
            $cc = strtolower(trim((string)($s['country_code'] ?: ($office['country_code'] ?? ''))));
            $lat = $s['latitude'] !== null ? (float)$s['latitude'] : (!$atVenue && $office ? (float)$office['latitude'] : null);
            $lon = $s['longitude'] !== null ? (float)$s['longitude'] : (!$atVenue && $office ? (float)$office['longitude'] : null);
            $address = $atVenue ? trim((string)$s['address']) : ($office['address'] ?? '');
            $city = trim((string)$s['city']) ?: ($office['display_name'] ?? '');
            $name = trim((string)$s['venue_name']) ?: ($office ? $office['display_name'] : $city);
            $phone = $office['phone'] ?? '';
            $mapsQuery = $lat !== null ? $lat . ',' . $lon : trim($name . ' ' . $address . ' ' . $city);

            $loc = [
                'office'       => $office,
                'office_url'   => $office ? officeUrl($office['slug']) : '',
                'at_venue'     => $atVenue,
                // Only a date: no office, no venue, not online (an event that does not depend on a place)
                'floating'     => !$office && !$atVenue && !$s['is_online'],
                'name'         => $name,
                'city'         => $city,
                'country_code' => $cc,
                'country'      => countryNameFor($cc, $office['country'] ?? ''),
                'address'      => $address,
                'lat'          => $lat,
                'lon'          => $lon,
                'is_online'    => (bool)$s['is_online'],
                'starts_on'    => $s['starts_on'],
                'ends_on'      => $s['ends_on'] ?: $s['starts_on'],
                'start_time'   => $s['start_time'] ? substr($s['start_time'], 0, 5) : null,
                'end_time'     => $s['end_time'] ? substr($s['end_time'], 0, 5) : null,
                'phone'        => $phone,
                'email'        => $office['email'] ?? '',
                'whatsapp'     => $office ? officeWhatsapp($office) : '',
                'maps_url'     => ($s['is_online'] || $mapsQuery === '') ? '' : 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($mapsQuery),
            ];
            $locations[] = $loc;
            if ($loc['starts_on']) $starts[] = $loc['starts_on'];
            if ($loc['ends_on']) $ends[] = $loc['ends_on'];
            if ($cc !== '') $countries[$cc] = true;
            if ($office) $officeIds[(int)$office['id']] = true;
        }

        // Relations: offices and whole countries the event is linked to (a country = all of its offices)
        $relations = [];
        foreach ($relByEvent[(int)$ev['id']] ?? [] as $r) {
            if ($r['office_id'] !== null && isset($offices[(int)$r['office_id']])) {
                $o = $offices[(int)$r['office_id']];
                $relations[] = ['kind' => 'office', 'office' => $o, 'country_code' => strtolower((string)$o['country_code']), 'country' => $o['country']];
                $officeIds[(int)$o['id']] = true;
                if ($o['country_code'] !== '') $countries[strtolower((string)$o['country_code'])] = true;
            } elseif ($r['country_code'] !== null && trim((string)$r['country_code']) !== '') {
                $cc = strtolower(trim((string)$r['country_code']));
                $relations[] = ['kind' => 'country', 'office' => null, 'country_code' => $cc, 'country' => countryNameFor($cc)];
                $countries[$cc] = true;
            }
        }

        $people = array_map(fn($p) => [
            'id' => (int)$p['id'], 'name' => $p['name'], 'title' => (string)$p['title'], 'specialty' => (string)$p['specialty'],
            'organization' => (string)$p['organization'], 'photo_url' => (string)$p['photo_url'], 'is_acibadem' => (bool)$p['is_acibadem'],
            'role' => in_array($p['role'], EVENT_ROLES, true) ? $p['role'] : 'speaker',
        ], $pplByEvent[(int)$ev['id']] ?? []);

        $program = array_map(fn($r) => [
            'day' => $r['day'], 'start_time' => $r['start_time'] ? substr($r['start_time'], 0, 5) : null,
            'end_time' => $r['end_time'] ? substr($r['end_time'], 0, 5) : null,
            'kind' => $r['kind'] === 'heading' ? 'heading' : 'session', 'title' => $r['title'], 'speakers' => (string)$r['speakers'],
        ], $sessByEvent[(int)$ev['id']] ?? []);

        $relatesHq = $v2 && !empty($ev['relates_hq']);
        $hasPlace = (bool)array_filter($locations, fn($l) => !$l['floating']);
        $start = $starts ? min($starts) : null;
        $end = $ends ? max($ends) : $start;
        $out[] = [
            'id'           => (int)$ev['id'],
            'slug'         => $ev['slug'],
            'url'          => eventUrl($ev['slug']),
            'tag_key'      => $ev['tag_key'],
            'icon'         => eventIcon($ev['tag_key']),
            'audience'     => $v2 && in_array($ev['audience'], EVENT_AUDIENCES, true) ? $ev['audience'] : 'b2c',
            'visibility'   => $v2 && in_array($ev['visibility'], EVENT_VISIBILITY, true) ? $ev['visibility'] : 'listed',
            'featured_rank' => $v2 ? (int)$ev['featured_rank'] : 0,
            'relates_hq'   => $relatesHq,
            'title'        => $ev['title'],
            'description'  => (string)$ev['description'],
            'image_url'    => $ev['image_url'],
            'link_url'     => $ev['link_url'],
            'file_url'     => $v2 ? (string)$ev['file_url'] : '',
            'is_published' => (bool)$ev['is_published'],
            'sort_order'   => (int)$ev['sort_order'],
            'start'        => $start,
            'end'          => $end,
            // Undated events stay listed as current; dated ones move to "past" the day after they end
            'status'       => ($end === null || $end >= $today) ? 'upcoming' : 'past',
            'countries'    => array_keys($countries),
            'office_ids'   => array_keys($officeIds),
            'is_global'    => !$countries && !$officeIds && !$hasPlace && !$relatesHq,
            'relations'    => $relations,
            'people'       => $people,
            'program'      => $program,
            'locations'    => $locations,
        ];
    }
    return $cache[$key] = $out;
}

/**
 * Upcoming first, past after. Among upcoming events, featured ones (rank 1, 2, 3) come first, then by date
 * (soonest first, undated last). Past events never get a boost: most recent first.
 */
function sortEvents(array $events): array {
    usort($events, function ($a, $b) {
        if ($a['status'] !== $b['status']) return $a['status'] === 'upcoming' ? -1 : 1;
        if ($a['status'] === 'past') return strcmp((string)$b['end'], (string)$a['end']);
        $ra = $a['featured_rank'] > 0 ? $a['featured_rank'] : 99;
        $rb = $b['featured_rank'] > 0 ? $b['featured_rank'] : 99;
        if ($ra !== $rb) return $ra <=> $rb;
        if ($a['start'] === null || $b['start'] === null) return ($a['start'] === null) <=> ($b['start'] === null) ?: $a['sort_order'] <=> $b['sort_order'];
        return strcmp($a['start'], $b['start']) ?: $a['sort_order'] <=> $b['sort_order'];
    });
    return $events;
}

/** One event by its address. Link-only events open here too, internal ones never. */
function getEventBySlug(string $slug): ?array {
    foreach (getEvents(true, 'public') as $ev) if ($ev['slug'] === $slug) return $ev;
    return null;
}

/**
 * Events linked to this office: it hosts a location, is named directly, or its country is named
 * (a country means all of its offices). Upcoming first.
 */
function getEventsForOffice(int $officeId): array {
    $cc = '';
    foreach (getAllOffices(false) as $o) if ((int)$o['id'] === $officeId) $cc = strtolower((string)$o['country_code']);
    return sortEvents(array_values(array_filter(getEvents(), function ($ev) use ($officeId, $cc) {
        if (in_array($officeId, $ev['office_ids'], true)) return true;
        foreach ($ev['relations'] as $r) if ($r['kind'] === 'country' && $cc !== '' && $r['country_code'] === $cc) return true;
        return false;
    })));
}

/**
 * Who a visitor should contact about an event: the office of its first location, else a named office,
 * else the first office of a named country (the visitor's own country first), else the head office.
 * Returns ['office' => row|null (null = head office), 'phone', 'email', 'whatsapp'] with digits-only whatsapp.
 */
function eventContact(array $ev, string $visitorCc = ''): array {
    $office = null;
    if (!$ev['relates_hq']) {
        foreach ($ev['locations'] as $l) if ($l['office']) { $office = $l['office']; break; }
        if (!$office) foreach ($ev['relations'] as $r) if ($r['kind'] === 'office') { $office = $r['office']; break; }
        if (!$office) {
            $ccs = array_column(array_filter($ev['relations'], fn($r) => $r['kind'] === 'country'), 'country_code');
            if ($visitorCc !== '' && in_array($visitorCc, $ccs, true)) $ccs = [$visitorCc];
            foreach (getAllOffices() as $o) if (in_array(strtolower((string)$o['country_code']), $ccs, true)) { $office = $o; break; }
        }
    }
    if ($office) {
        return ['office' => $office, 'phone' => (string)$office['phone'], 'email' => (string)$office['email'], 'whatsapp' => officeWhatsapp($office)];
    }
    return ['office' => null, 'phone' => '+90 216 444 5544', 'email' => 'international@acibadem.com', 'whatsapp' => '905359650466'];
}

/** URL-safe slug that is not used by another event yet. */
function uniqueEventSlug(string $text, int $exceptId = 0): string {
    $map = ['ş' => 's', 'Ş' => 's', 'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o',
            'ü' => 'u', 'Ü' => 'u', 'ə' => 'e', 'Ə' => 'e', 'ß' => 'ss', 'ä' => 'a', 'â' => 'a', 'ă' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'ô' => 'o', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z'];
    $text = strtr(mb_strtolower($text, 'UTF-8'), $map);
    if (function_exists('iconv')) $text = (string)@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $base = generateSlug($text) ?: 'event';
    $base = substr($base, 0, 150);
    $slug = $base;
    $stmt = getDb()->prepare("SELECT COUNT(*) FROM events WHERE slug = ? AND id <> ?");
    for ($i = 2; ; $i++) {
        $stmt->execute([$slug, $exceptId]);
        if (!(int)$stmt->fetchColumn()) return $slug;
        $slug = $base . '-' . $i;
    }
}

/** iCalendar file for "Add to calendar": one entry per stop (all-day when no time is given). */
function eventIcs(array $ev): string {
    $esc = fn($s) => str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], (string)$s);
    $host = parse_url(getBaseUrl(), PHP_URL_HOST) ?: 'acibadem.world';
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Acibadem International Offices//Events//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'];
    foreach ($ev['locations'] as $i => $l) {
        if (!$l['starts_on']) continue;
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:event-' . $ev['id'] . '-' . $i . '@' . $host;
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        if ($l['start_time']) {
            $lines[] = 'DTSTART:' . str_replace(['-', ':'], '', $l['starts_on']) . 'T' . str_replace(':', '', $l['start_time']) . '00';
            $endTime = $l['end_time'] ?: date('H:i', strtotime($l['start_time']) + 3600);
            $lines[] = 'DTEND:' . str_replace('-', '', $l['ends_on']) . 'T' . str_replace(':', '', $endTime) . '00';
        } else {
            $lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $l['starts_on']);
            $lines[] = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime($l['ends_on'] . ' +1 day'));
        }
        $lines[] = 'SUMMARY:' . $esc($ev['title']);
        $place = $l['is_online'] ? 'Online' : trim($l['name'] . ', ' . $l['address'] . ($l['city'] && stripos($l['address'], $l['city']) === false ? ', ' . $l['city'] : ''), ', ');
        if ($place !== '') $lines[] = 'LOCATION:' . $esc($place);
        $desc = trim($ev['description'] . "\n\n" . ($l['phone'] ? $l['phone'] . "\n" : '') . $ev['url']);
        $lines[] = 'DESCRIPTION:' . $esc($desc);
        $lines[] = 'URL:' . $ev['url'];
        $lines[] = 'END:VEVENT';
    }
    $lines[] = 'END:VCALENDAR';
    // Fold long lines (RFC 5545: 75 octets)
    $out = '';
    foreach ($lines as $line) {
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) $cut--; // don't split a UTF-8 character
            $out .= substr($line, 0, $cut) . "\r\n ";
            $line = substr($line, $cut);
        }
        $out .= $line . "\r\n";
    }
    return $out;
}
