<?php
/**
 * Settings for the nearest-office finder (assets/js/nearest-finder.js): used by the home page,
 * and by the map and office list pages of the new design, so the same rules apply everywhere.
 */
function finderConfig(array $offices): array {
    // Test parameters: ?cc=az (pretend country), ?at=40.4,49.8 (pretend location), ?at=deny (pretend refused)
    // Country from the CDN, when the site runs behind Cloudflare (header absent otherwise)
    $serverCountry = strtolower($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '');
    if (!preg_match('/^[a-z]{2}$/', $serverCountry) || $serverCountry === 'xx' || $serverCountry === 't1') {
        $serverCountry = '';
    }

    $testCountry = strtolower($_GET['cc'] ?? '');
    if (!preg_match('/^[a-z]{2}$/', $testCountry)) $testCountry = '';

    $testAt = null;
    $at = $_GET['at'] ?? '';
    if ($at === 'deny') {
        $testAt = 'deny';
    } elseif (preg_match('/^(-?\d{1,2}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)$/', $at, $m)) {
        $testAt = [(float)$m[1], (float)$m[2]];
    }

    // Head office: shown when there is no office in the visitor's country (and for Türkiye)
    $hq = [
        'phone'    => '+90 216 444 5544',
        'whatsapp' => '+90 535 965 0466',
        'email'    => 'international@acibadem.com',
        'address'  => 'Atatürk Mah. Feza Sok. No:3 Ataşehir / İstanbul',
        'country_code' => 'tr',
    ];

    return [
        'lang'          => LANGUAGES[$GLOBALS['current_lang']]['html'] ?? 'en',
        'serverCountry' => $serverCountry,
        'testCountry'   => $testCountry,
        'testAt'        => $testAt,
        'mapUrl'        => getBaseUrl() . '/map',
        'listUrl'       => getBaseUrl() . '/offices',
        'hq'            => $hq,
        'offices'       => array_map(fn($o) => [
            'lat'          => (float)$o['latitude'],
            'lon'          => (float)$o['longitude'],
            'name'         => $o['display_name'],
            'country'      => $o['country'],
            'cc'           => strtolower((string)$o['country_code']),
            'address'      => $o['address'],
            'phone'        => $o['phone'],
            'whatsapp'     => officeWhatsapp($o),
            'email'        => $o['email'],
            'url'          => officeUrl($o['slug']),
        ], $offices),
        't' => [
            'locate'         => __('btn_locate'),
            'locating'       => __('nf_locating'),
            'seemsIn'        => __('nf_seems_in'),
            'byLocation'     => __('nf_by_location'),
            'nearestLabel'   => __('nf_nearest_label'),
            'selectedLabel'  => __('nf_selected_label'),
            'regionLabel'    => __('nf_region_label'),
            'otherInCountry' => __('nf_other_in_country'),
            'noOfficeH'      => __('nf_no_office_h'),
            'noOfficeRegionH'=> __('nf_no_office_region_h'),
            'noOfficeP'      => __('nf_no_office_p'),
            'hqName'         => __('nf_hq_name'),
            'hqLabel'        => __('nf_hq_label'),
            'nearestPhysical'=> __('nf_nearest_physical'),
            'seeWorld'       => __('nf_see_world'),
            'seeList'        => __('nf_see_list'),
            'refine'         => __('nf_refine'),
            'unknownH'       => __('nf_unknown_h'),
            'unknownP'       => __('nf_unknown_p'),
            'searchNone'     => __('nf_search_none'),
            'deniedH'        => __('nf_denied_h'),
            'deniedP'        => __('nf_denied_p'),
            'stay'           => __('nf_stay'),
            'goNow'          => __('nf_go_now'),
            'kmAway'         => __('result_km_away'),
            'details'        => __('result_view_details'),
            'call'           => __('js_call'),
            'whatsapp'       => __('js_whatsapp'),
            'email'          => __('js_email'),
            'route'          => __('js_route'),
        ],
    ];
}
