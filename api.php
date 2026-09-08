<?php
/**
 * Acıbadem International Offices — JSON API
 * Returns all active office data in JSON format
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=300'); // 5 min cache

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $offices = getAllOffices(true);
    
    // Clean up data for API response
    $data = array_map(function($o) {
        return [
            'id' => (int)$o['id'],
            'name' => $o['name'],
            'display_name' => $o['display_name'],
            'slug' => $o['slug'],
            'country' => $o['country'],
            'country_code' => $o['country_code'],
            'address' => $o['address'],
            'phone' => $o['phone'],
            'email' => $o['email'],
            'latitude' => (float)$o['latitude'],
            'longitude' => (float)$o['longitude']
        ];
    }, $offices);
    
    echo json_encode([
        'success' => true,
        'count' => count($data),
        'generated_at' => date('c'),
        'data' => array_values($data)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
