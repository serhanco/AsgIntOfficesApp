<?php
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
 * Get office URL
 */
function officeUrl(string $slug): string {
    return getBaseUrl() . '/office/' . $slug;
}
