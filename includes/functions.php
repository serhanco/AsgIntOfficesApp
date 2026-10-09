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
    return $protocol . '://' . $host . $path;
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
