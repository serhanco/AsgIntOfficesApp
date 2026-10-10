<?php
/**
 * Stores an event request from the public form (POST, JSON in / JSON out).
 * channel: 'form' (contact required), or 'whatsapp' / 'email' (logged when the visitor opens that channel).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/v2/events-ui.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply_json(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reply_json(405, ['ok' => false]);

// Only our own pages may post here
$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
if ($origin !== '' && strcasecmp((string)parse_url($origin, PHP_URL_HOST), (string)parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) !== 0) {
    reply_json(403, ['ok' => false]);
}

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;
$str = fn($k, $max) => mb_substr(trim(is_string($in[$k] ?? null) ? preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $in[$k]) : ''), 0, $max);

if ($str('website', 50) !== '') reply_json(200, ['ok' => true]); // honeypot: pretend success

$type = $str('type', 40);
$channel = $str('channel', 12);
$place = $str('place', 200);
$contact = $str('contact', 200);
if (!isset(EVENT_TYPES[$type]) || !in_array($channel, ['form', 'whatsapp', 'email'], true)) reply_json(422, ['ok' => false]);
if ($channel === 'form') {
    $digits = preg_replace('/\D/', '', $contact);
    $looksOk = strpos($contact, '@') !== false ? filter_var($contact, FILTER_VALIDATE_EMAIL) !== false : strlen($digits) >= 6;
    if (!$looksOk) reply_json(422, ['ok' => false, 'field' => 'contact']);
}
if ($place === '' && $contact === '') reply_json(200, ['ok' => true]); // nothing worth storing from a channel click

// At most 8 per hour per visitor
$rl = sys_get_temp_dir() . '/asg_evreq_' . hash('sha256', client_ip() . (defined('APP_SECRET') ? APP_SECRET : ''));
$times = array_filter((array)json_decode((string)@file_get_contents($rl), true), fn($t) => is_int($t) && $t > time() - 3600);
if (count($times) >= 8) reply_json(429, ['ok' => false]);
$times[] = time();
@file_put_contents($rl, json_encode(array_values($times)), LOCK_EX);

try {
    getDb()->prepare("INSERT INTO event_requests (type_key, place, contact, channel, lang, country, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$type, $place, $contact, $channel, mb_substr(langHtml(), 0, 8),
            preg_match('/^[A-Z]{2}$/', $cc = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')) ? $cc : '',
            $channel === 'form' ? 'new' : 'info']); // WhatsApp / e-mail clicks are handled in that channel, so they start as 'info'
} catch (\Throwable $e) {
    reply_json(503, ['ok' => false]); // table not created yet: the page falls back to WhatsApp / e-mail
}
reply_json(200, ['ok' => true]);
