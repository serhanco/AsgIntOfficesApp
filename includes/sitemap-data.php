<?php
/**
 * What is in the sitemap: one list used by /sitemap.xml (for search engines) and /sitemap (for people),
 * so both always show the same pages. Built from the database on every call.
 *
 * Contents: the public pages, every active office and every listed, published event. Left out: admin, API and
 * setup addresses, link-only and internal events, events that currently forward to an outside registration page,
 * and test/preview addresses (?demo, ?cc, ?at, ?page).
 */

/** Y-m-d of a database timestamp, or null. */
function sitemapDate($ts): ?string {
    $t = $ts ? strtotime((string)$ts) : false;
    return $t ? date('Y-m-d', $t) : null;
}

/**
 * Entries of the sitemap. Each: path ('/office/baku'), lastmod (Y-m-d or null), group ('pages' | 'offices' | 'events'),
 * label (page name, office name or event title), country (offices only).
 */
function sitemapEntries(): array {
    $offices = getAllOffices();
    $events = array_values(array_filter(getEvents(), fn($ev) => !$ev['href_ext'] && $ev['visibility'] === 'listed'));

    // Last change of each event (events.updated_at); its stops count too
    $eventDates = [];
    if ($events) {
        try {
            foreach (getDb()->query('SELECT id, updated_at FROM events')->fetchAll() as $r) $eventDates[(int)$r['id']] = sitemapDate($r['updated_at']);
            foreach (getDb()->query('SELECT event_id, MAX(updated_at) AS u FROM event_locations GROUP BY event_id')->fetchAll() as $r) {
                $d = sitemapDate($r['u']);
                $id = (int)$r['event_id'];
                if ($d && (!isset($eventDates[$id]) || $d > $eventDates[$id])) $eventDates[$id] = $d;
            }
        } catch (\Throwable $e) {
            // Dates are optional; the addresses are listed without them
        }
    }

    // An office page also shows its team: a team change counts as a change of the office page
    $teamDates = [];
    try {
        foreach (getDb()->query('SELECT office_id, MAX(updated_at) AS u FROM office_teams GROUP BY office_id')->fetchAll() as $r) {
            $teamDates[(int)$r['office_id']] = sitemapDate($r['u']);
        }
    } catch (\Throwable $e) {
    }
    $officeDate = fn(array $o) => max(sitemapDate($o['updated_at'] ?? null), $teamDates[(int)$o['id']] ?? null);

    $latest = fn(array $dates) => ($dates = array_filter($dates)) ? max($dates) : null;
    $latestOffice = $latest(array_map($officeDate, $offices));
    $latestEvent = $latest(array_map(fn($ev) => $eventDates[$ev['id']] ?? null, $events));
    $latestAny = $latest([$latestOffice, $latestEvent]);

    $entries = [
        ['path' => '/',                        'lastmod' => $latestAny,    'group' => 'pages', 'label' => __('nav_nearest')],
        ['path' => '/offices',                 'lastmod' => $latestOffice, 'group' => 'pages', 'label' => __('nav_all_offices')],
        ['path' => '/map',                     'lastmod' => $latestOffice, 'group' => 'pages', 'label' => __('nav_map')],
        ['path' => '/events',                  'lastmod' => $latestEvent,  'group' => 'pages', 'label' => __('nav_events')],
        ['path' => '/contracted-institutions', 'lastmod' => null,          'group' => 'pages', 'label' => __('inst_title')],
    ];
    // The nearest-office finder has its own address; it is listed for search engines, not repeated for people
    array_splice($entries, 1, 0, [['path' => '/nearest', 'lastmod' => $latestOffice, 'group' => 'finder', 'label' => __('nav_nearest')]]);
    foreach ($offices as $o) {
        $entries[] = ['path' => '/office/' . $o['slug'], 'lastmod' => $officeDate($o), 'group' => 'offices', 'label' => $o['display_name'], 'country' => $o['country']];
    }
    foreach ($events as $ev) {
        $entries[] = ['path' => '/event/' . $ev['slug'], 'lastmod' => $eventDates[$ev['id']] ?? null, 'group' => 'events', 'label' => $ev['title']];
    }
    return $entries;
}
