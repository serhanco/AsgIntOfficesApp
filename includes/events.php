<?php
/**
 * Events: the second main entity next to offices.
 *
 * An event (events) has one or more stops (event_locations). A stop is held at an
 * office, or at another venue (venue_name/city/address) with the office as its contact.
 * Until database/patch_20261009_events.sql is applied, the old office_activities
 * table is read instead, so nothing breaks in between.
 */

/** Event types, in the order the admin form lists them. Labels come from the lang files. */
const EVENT_TYPES = [
    'act_tag_doctor'       => ['icon' => 'ph-stethoscope'],
    'act_tag_presentation' => ['icon' => 'ph-presentation-chart'],
    'act_tag_exhibition'   => ['icon' => 'ph-handshake'],
    'act_tag_webinar'      => ['icon' => 'ph-video-camera'],
];

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
 * All events with their stops, newest data model only. Each event:
 *   id, slug, url, tag_key, icon, title, description, image_url, link_url, is_published,
 *   start, end (Y-m-d or null), status ('upcoming' | 'past'), countries [codes],
 *   locations: [office (row|null), name, city, country_code, country, address, lat, lon,
 *               is_online, starts_on, ends_on, start_time, end_time, phone, email, whatsapp, maps_url]
 */
function getEvents(bool $publishedOnly = true): array {
    static $cache = [];
    if (isset($cache[$publishedOnly])) return $cache[$publishedOnly];
    if (!eventsReady()) return $cache[$publishedOnly] = [];

    try {
        $db = getDb();
        $events = $db->query("SELECT * FROM events" . ($publishedOnly ? " WHERE is_published = 1" : '') . " ORDER BY sort_order ASC, id ASC")->fetchAll();
        $stops = $db->query("SELECT * FROM event_locations ORDER BY sort_order ASC, starts_on IS NULL, starts_on ASC, id ASC")->fetchAll();
    } catch (\Throwable $e) {
        return $cache[$publishedOnly] = [];
    }

    $offices = [];
    foreach (getAllOffices(false) as $o) $offices[(int)$o['id']] = $o;

    $byEvent = [];
    foreach ($stops as $s) $byEvent[(int)$s['event_id']][] = $s;

    $today = date('Y-m-d');
    $out = [];
    foreach ($events as $ev) {
        $locations = [];
        $starts = $ends = $countries = [];
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
        }

        $start = $starts ? min($starts) : null;
        $end = $ends ? max($ends) : $start;
        $out[] = [
            'id'           => (int)$ev['id'],
            'slug'         => $ev['slug'],
            'url'          => eventUrl($ev['slug']),
            'tag_key'      => $ev['tag_key'],
            'icon'         => eventIcon($ev['tag_key']),
            'title'        => $ev['title'],
            'description'  => (string)$ev['description'],
            'image_url'    => $ev['image_url'],
            'link_url'     => $ev['link_url'],
            'is_published' => (bool)$ev['is_published'],
            'sort_order'   => (int)$ev['sort_order'],
            'start'        => $start,
            'end'          => $end,
            // Undated events stay listed as current; dated ones move to "past" the day after they end
            'status'       => ($end === null || $end >= $today) ? 'upcoming' : 'past',
            'countries'    => array_keys($countries),
            'locations'    => $locations,
        ];
    }
    return $cache[$publishedOnly] = $out;
}

/** Upcoming first (soonest first, undated last), then past (most recent first). */
function sortEvents(array $events): array {
    usort($events, function ($a, $b) {
        if ($a['status'] !== $b['status']) return $a['status'] === 'upcoming' ? -1 : 1;
        if ($a['status'] === 'past') return strcmp((string)$b['end'], (string)$a['end']);
        if ($a['start'] === null || $b['start'] === null) return ($a['start'] === null) <=> ($b['start'] === null) ?: $a['sort_order'] <=> $b['sort_order'];
        return strcmp($a['start'], $b['start']) ?: $a['sort_order'] <=> $b['sort_order'];
    });
    return $events;
}

function getEventBySlug(string $slug): ?array {
    foreach (getEvents() as $ev) if ($ev['slug'] === $slug) return $ev;
    return null;
}

/** Events with a stop at this office, upcoming first. */
function getEventsForOffice(int $officeId): array {
    return sortEvents(array_values(array_filter(getEvents(), function ($ev) use ($officeId) {
        foreach ($ev['locations'] as $l) if ($l['office'] && (int)$l['office']['id'] === $officeId) return true;
        return false;
    })));
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
