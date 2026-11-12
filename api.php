<?php
/**
 * Acıbadem International Offices — JSON API
 * Returns all active office data in JSON format
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
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
    
    $data = array_map('apiOfficePayload', $offices);

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
