<?php
/**
 * Measurement layer: which tag the site loads (direct GA4, Google Tag Manager or nothing), the page facts it
 * writes to the dataLayer before any tag, and the settings for assets/js/track.js (clicks and other events).
 *
 * The contract (event names, fields, rules for new buttons) is in docs/olcumleme-sozlesmesi.md.
 * Field names and event names never change once live; new ones are only added. Bump MEASURE_CONTRACT when you add some.
 */

const MEASURE_CONTRACT = '1.0';

/** Admin choice in Ayarlar: 'ga4' (direct, the default), 'gtm' or 'off'. */
function measureMethodSetting(): string {
    $v = siteSetting('measure_method');
    return in_array($v, ['ga4', 'gtm', 'off'], true) ? $v : 'ga4';
}

/** GTM ID from Ayarlar. A pasted snippet works too: the ID is taken out of it. */
function gtmId(): string {
    return parseGtmId(siteSetting('gtm_id'));
}

function parseGtmId(string $raw): string {
    return preg_match('/GTM-[A-Z0-9]{4,12}/i', $raw, $m) ? strtoupper($m[0]) : '';
}

/** Meta Pixel ID from Ayarlar (digits only; a pasted snippet works too). Empty = no Pixel. */
function metaPixelId(): string {
    return parseMetaPixelId(siteSetting('meta_pixel_id'));
}

function parseMetaPixelId(string $raw): string {
    if (preg_match("/fbq\(\s*['\"]init['\"]\s*,\s*['\"](\d{8,20})/", $raw, $m)) return $m[1];
    return preg_match('/\b(\d{8,20})\b/', $raw, $m) ? $m[1] : '';
}

/**
 * The method that is really in use: GTM chosen without a valid GTM ID falls back to direct GA4,
 * so measurement never stops silently. 'off' when nothing can be loaded.
 */
function measureMode(): string {
    $m = measureMethodSetting();
    if ($m === 'gtm' && gtmId() === '') $m = 'ga4';
    if ($m === 'ga4' && ga4Id() === '') $m = 'off';
    return $m;
}

/**
 * GTM preview (Tag Assistant adds ?gtm_debug=x to the address): load the GTM setup for this page view,
 * also for signed-in admins and also while Ayarlar still says "direct GA4", so a new GTM setup can be
 * tested on the live site before switching. Direct GA4 stays off on that page view (no double count).
 */
function measurePreview(): bool {
    return isset($_GET['gtm_debug']) && gtmId() !== '';
}

/** What this page view loads: 'gtm', 'ga4' or '' (nothing). */
function measureLoad(): string {
    static $load = null;
    if ($load !== null) return $load;
    if (!analyticsAllowed()) return $load = '';
    if (measurePreview()) return $load = 'gtm';
    if (isAdminVisitor()) return $load = '';
    $mode = measureMode();
    return $load = $mode === 'off' ? '' : $mode;
}

/** Load the Meta Pixel directly? Only in direct GA4 mode; with GTM the Meta tag inside GTM does it. */
function metaPixelDirect(): bool {
    return measureLoad() === 'ga4' && metaPixelId() !== '';
}

/** Why the page loads what it loads (printed as an HTML comment, so a page source check shows it). */
function measureNote(): string {
    $load = measureLoad();
    if ($load === 'gtm') return 'gtm ' . gtmId() . ' loaded' . (measurePreview() ? ' (preview)' : '');
    if ($load === 'ga4') return 'ga4 ' . ga4Id() . ' loaded' . (metaPixelDirect() ? ', meta pixel' : '');
    if (measureMode() === 'off') return 'off';
    if (!analyticsAllowed()) return 'waiting for cookie consent';
    if (isAdminVisitor()) return 'skipped, signed-in admin (open the site in a private window to be measured)';
    return 'off';
}

/** Page template names (dataLayer page_template). One per page type, never translated. Add a row for a new page. */
function measureTemplate(string $currentPage, ?string $override): string {
    if ($override !== null && $override !== '') return $override;
    $map = [
        'home' => 'home', 'nearest' => 'nearest', 'offices' => 'offices', 'office' => 'office', 'map' => 'map',
        'events' => 'events', 'contracted-institutions' => 'institutions', 'sitemap' => 'sitemap', 'api-docs' => 'api_docs',
    ];
    return $map[$currentPage] ?? ($currentPage !== '' ? $currentPage : 'not_found');
}

/** Office fields for the dataLayer (page context or a link). */
function measureOfficeFields(array $office): array {
    return [
        'office_id'      => (int)($office['id'] ?? 0) ?: null,
        'office_slug'    => (string)($office['slug'] ?? '') ?: null,
        'office_country' => strtoupper((string)($office['country_code'] ?? '')) ?: null,
    ];
}

/** Event fields for the dataLayer (page context or a link). */
function measureEventFields(array $ev): array {
    return [
        'event_id'       => (int)($ev['id'] ?? 0) ?: null,
        'event_slug'     => (string)($ev['slug'] ?? '') ?: null,
        'event_type'     => (string)($ev['tag_key'] ?? '') ?: null,
        'event_audience' => (string)($ev['audience'] ?? '') ?: null,
    ];
}

/** The same fields as data-* attributes, read by assets/js/track.js from the nearest element that has them. */
function trackAttrs(array $fields): string {
    $out = '';
    foreach ($fields as $k => $v) {
        if ($v === null || $v === '') continue;
        $out .= ' data-' . str_replace('_', '-', $k) . '="' . e((string)$v) . '"';
    }
    return $out;
}

/** Facts about this page, written to the dataLayer before any tag loads. */
function measurePageContext(string $template, array $extra = []): array {
    return array_merge([
        'page_template'     => $template,
        'page_lang'         => langHtml(),
        'page_dir'          => langDir(),
        'visitor_country'   => cfCountry() ?: null,
        'consent_region'    => consentRegion(),
        'wa_office_enabled' => officeWhatsappEnabled(),
        'meta_pixel_id'     => metaPixelId() ?: null,
        'contract_version'  => MEASURE_CONTRACT,
    ], $extra);
}

/** Everything for <head>: consent default, page facts, then GTM or GA4 (and the Pixel in direct mode). */
function measurementHead(array $context): string {
    $json = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $load = measureLoad();
    $hqDigits = fn($s) => preg_replace('/\D/', '', $s);
    $cfg = [
        'mode' => $load,
        'hq'   => ['phone' => $hqDigits('+90 216 444 5544'), 'whatsapp' => $hqDigits('+90 535 965 0466'), 'email' => 'international@acibadem.com'],
        'pixel' => metaPixelDirect(),
    ];
    $html = consentDefaultScript();
    // Page facts first, so every tag (and GTM's first page view) sees them. asgTrack() queues until track.js loads.
    $html .= '<script>window.dataLayer=window.dataLayer||[];dataLayer.push(' . $json($context) . ');'
        . 'window.ASG_TRACK=' . $json($cfg) . ';'
        . 'window.asgTrack=window.asgTrack||function(n,p){(window.asgTrackQ=window.asgTrackQ||[]).push([n,p||{}]);};</script>' . "\n";

    if ($load === 'gtm') {
        $id = e(gtmId());
        $html .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],"
            . "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);"
            . "})(window,document,'script','dataLayer','$id');</script>\n";
    } elseif ($load === 'ga4') {
        $id = e(ga4Id());
        // The page facts go with every GA4 event (page_view and the click events from track.js)
        $params = array_filter($context, fn($v, $k) => $v !== null && $k !== 'meta_pixel_id', ARRAY_FILTER_USE_BOTH);
        $params = array_map(fn($v) => is_bool($v) ? ($v ? 'yes' : 'no') : $v, $params);
        $html .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script>' . "\n"
            . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());'
            . 'gtag(\'set\',' . $json($params) . ');gtag(\'config\',\'' . $id . '\');</script>' . "\n";
        if (metaPixelDirect()) {
            // Meta Pixel has no Consent Mode: it is held back ('revoke') until the visitor accepts, see consent-banner.php
            $revoke = showConsentUi() && consentChoice() !== 'yes';
            $html .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};"
                . "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];"
                . "s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');"
                . ($revoke ? "fbq('consent','revoke');" : '')
                . "fbq('init','" . e(metaPixelId()) . "');fbq('track','PageView');</script>\n";
        }
    }
    $html .= '<!-- olcum: ' . e(measureNote()) . ' -->' . "\n";
    return $html;
}

/** Right after <body>: GTM's <noscript> part. */
function measurementBody(): string {
    if (measureLoad() !== 'gtm') return '';
    return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e(gtmId()) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
}

/** Measurement codes pasted into the custom code boxes would load a second time next to the method above. */
function customCodeMeasureWarnings(): array {
    $warn = [];
    // Admin screens are Turkish only
    foreach (['head_code' => '<head> içine eklenecek kod', 'body_code' => '<body> başlangıcına eklenecek kod', 'footer_code' => 'Sayfa sonuna eklenecek kod'] as $slot => $label) {
        $code = siteSetting($slot);
        if ($code === '') continue;
        if (preg_match('/googletagmanager\.com|GTM-[A-Z0-9]{4,}|\bG-[A-Z0-9]{6,}\b|gtag\(/i', $code)) $warn[] = 'Özel kod alanında Google (GA4/GTM) kodu var: "' . $label . '"';
        if (preg_match('/fbevents\.js|fbq\(/i', $code)) $warn[] = 'Özel kod alanında Meta Pixel kodu var: "' . $label . '"';
    }
    return $warn;
}
