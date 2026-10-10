<?php
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/design.php';
require_once __DIR__ . '/events.php';

/**
 * Helper Functions
 * Acıbadem International Offices App
 */

/**
 * HTML escape helper (XSS prevention)
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Get base URL dynamically
 */
function getBaseUrl(): string {
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $path = preg_replace('#/admin$#', '', $path); // links built inside the admin must still point at the public site
    return $protocol . '://' . $host . $path;
}

/** Is $ip inside the CIDR range? Works for IPv4 and IPv6. */
function ip_in_cidr(string $ip, string $cidr): bool {
    [$net, $bits] = explode('/', $cidr) + [1 => null];
    $ipBin = @inet_pton($ip);
    $netBin = @inet_pton($net);
    if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) return false;
    $bits = (int)$bits;
    $bytes = intdiv($bits, 8);
    if ($bytes && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) return false;
    $rest = $bits % 8;
    if ($rest === 0) return true;
    $mask = (0xFF << (8 - $rest)) & 0xFF;
    return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
}

/**
 * The visitor's real IP. Behind Cloudflare every request arrives from a Cloudflare address,
 * so CF-Connecting-IP is trusted only when the connection really comes from Cloudflare.
 */
function client_ip(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
    if ($cf === '' || filter_var($cf, FILTER_VALIDATE_IP) === false) return $remote;
    $cloudflare = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];
    foreach ($cloudflare as $range) if (ip_in_cidr($remote, $range)) return $cf;
    return $remote;
}

/** Site setting saved in the admin (table site_settings). Empty string when unset or the patch is not applied yet. */
function siteSetting(string $key): string {
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (getDb()->query("SELECT skey, svalue FROM site_settings") as $r) $all[$r['skey']] = (string)$r['svalue'];
        } catch (\Throwable $e) {
        }
    }
    return $all[$key] ?? '';
}

/**
 * Cookie consent: visitors from the EU/EEA, UK, Switzerland and Russia (country from Cloudflare) must accept
 * before statistics and the custom codes from the admin load. Unknown country counts as "needs consent".
 * Edit CONSENT_COUNTRIES to add or remove countries (e.g. 'TR').
 */
const CONSENT_COUNTRIES = [
    'AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE',
    'IS','LI','NO','GB','CH','RU',
];

function consentRegion(): bool {
    $cc = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
    if (!preg_match('/^[A-Z]{2}$/', $cc) || $cc === 'XX' || $cc === 'T1') return true;
    return in_array($cc, CONSENT_COUNTRIES, true);
}

/** 'yes', 'no' or '' (not chosen yet) */
function consentChoice(): string {
    $v = $_COOKIE['asg_consent'] ?? '';
    return $v === 'yes' || $v === 'no' ? $v : '';
}

/** May statistics and custom codes load for this visitor? */
function analyticsAllowed(): bool {
    return !consentRegion() || consentChoice() === 'yes';
}

/** Show the cookie bar / preferences link to this visitor? */
function showConsentUi(): bool {
    return consentRegion() && !isAdminVisitor();
}

/** Custom code from the admin for a slot (head_code, body_code, footer_code). Not printed for logged-in admins (their visits are not tracked) or before consent. */
function customCode(string $slot): string {
    return isAdminVisitor() || !analyticsAllowed() ? '' : siteSetting($slot);
}

/** GA4 measurement ID: admin setting, then config.php GA4_ID, then the built-in default. 'kapat' in the admin turns it off. */
function ga4Id(): string {
    $v = trim(siteSetting('ga4_id'));
    if (strtolower($v) === 'kapat') return '';
    if (preg_match('/^G-[A-Z0-9]{4,20}$/', $v)) return $v;
    return defined('GA4_ID') ? GA4_ID : 'G-QN9G5K8F63';
}

/**
 * Get all offices
 */
function getAllOffices(bool $activeOnly = true): array {
    $db = getDb();
    $sql = 'SELECT * FROM offices';
    if ($activeOnly) $sql .= ' WHERE is_active = 1';
    $sql .= ' ORDER BY country ASC, display_name ASC';
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * WhatsApp number of an office, digits only ('' = none, the button is hidden).
 * Before the whatsapp column exists (patch not applied yet) the phone number is used, as before.
 */
function officeWhatsapp(array $office): string {
    $value = array_key_exists('whatsapp', $office) ? (string)$office['whatsapp'] : (string)($office['phone'] ?? '');
    return preg_replace('/[^0-9]/', '', $value);
}

/**
 * Get single office by slug
 */
function getOfficeBySlug(string $slug): ?array {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM offices WHERE slug = :slug AND is_active = 1 LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get offices by country
 */
function getOfficesByCountry(string $country, ?int $excludeId = null): array {
    $db = getDb();
    $sql = 'SELECT * FROM offices WHERE country = :country AND is_active = 1';
    $params = [':country' => $country];
    if ($excludeId !== null) {
        $sql .= ' AND id != :excludeId';
        $params[':excludeId'] = $excludeId;
    }
    $sql .= ' ORDER BY display_name ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get countries with office count
 */
function getCountriesWithCount(): array {
    $db = getDb();
    $stmt = $db->query('SELECT country, country_code, COUNT(*) as office_count FROM offices WHERE is_active = 1 GROUP BY country, country_code ORDER BY country ASC');
    return $stmt->fetchAll();
}

/**
 * Generate URL-safe slug
 */
function generateSlug(string $text): string {
    $slug = mb_strtolower($text, 'UTF-8');
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Get flag image URL
 */
function getFlagUrl(string $countryCode): string {
    return 'https://flagcdn.com/w40/' . strtolower($countryCode) . '.png';
}

/**
 * Get flag HTML img tag
 */
function getFlagImg(string $country, ?string $countryCode): string {
    if ($countryCode) {
        return '<img src="' . e(getFlagUrl($countryCode)) . '" alt="' . e($country) . '" class="w-6 h-auto object-cover rounded shadow-sm" loading="lazy">';
    }
    return '<i class="ph-fill ph-flag text-gray-400"></i>';
}

/**
 * Calculate Haversine distance in km
 */
function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

/**
 * Resolve a stored image path to a URL.
 * Absolute URLs and root-relative paths are returned as is; paths saved by the
 * admin upload (e.g. "assets/images/uploads/...") are prefixed with the base URL.
 * Returns $default when the path is empty.
 */
function imageUrl(?string $path, string $default = ''): string {
    $path = trim((string)$path);
    if ($path === '') return $default;
    if (preg_match('#^(https?:)?//#i', $path) || $path[0] === '/') return $path;
    return getBaseUrl() . '/' . ltrim($path, '/');
}

/**
 * Is the current visitor a logged-in admin? Only looks at an existing session
 * (never starts one for anonymous visitors).
 */
function isAdminVisitor(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        if (empty($_COOKIE[session_name()])) return false;
        session_start();
    }
    return isset($_SESSION['admin_user_id']);
}

/**
 * Status shown for a team member. For now everyone shows as "online";
 * define('TEAM_STATUS_LIVE', true) in config.php to show the status saved in the admin panel.
 */
function teamStatus(array $member): string {
    if (!defined('TEAM_STATUS_LIVE') || !TEAM_STATUS_LIVE) return 'online';
    return $member['status'] ?? 'online';
}

/**
 * Get office URL
 */
function officeUrl(string $slug): string {
    return getBaseUrl() . '/office/' . $slug;
}

/**
 * Get team members for a specific office
 */
function getOfficeTeam(int $officeId): array {
    try {
        $db = getDb();
        $stmt = $db->prepare("SELECT * FROM office_teams WHERE office_id = ? ORDER BY sort_order ASC, id ASC");
        if (!$stmt) return [];
        $stmt->execute([$officeId]);
        return $stmt->fetchAll();
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Get recent activities for a specific office
 */
function getOfficeActivities(int $officeId): array {
    // Events moved to events + event_locations (one event, several offices/venues)
    if (eventsReady()) {
        $rows = [];
        foreach (getEventsForOffice($officeId) as $ev) {
            $rows[] = ['id' => $ev['id'], 'tag_key' => $ev['tag_key'], 'title' => $ev['title'], 'description' => $ev['description'],
                       'activity_date' => $ev['start'], 'sort_order' => $ev['sort_order'],
                       'url' => $ev['url'], 'start' => $ev['start'], 'end' => $ev['end'], 'status' => $ev['status']];
        }
        usort($rows, fn($a, $b) => [$a['sort_order'], $a['id']] <=> [$b['sort_order'], $b['id']]);
        return $rows;
    }
    try {
        $db = getDb();
        $stmt = $db->prepare("SELECT * FROM office_activities WHERE office_id = ? ORDER BY sort_order ASC, id ASC");
        if (!$stmt) return [];
        $stmt->execute([$officeId]);
        return $stmt->fetchAll();
    } catch (\Throwable $e) {
        // Table may not exist yet (patch not applied). Fail silently.
        return [];
    }
}
