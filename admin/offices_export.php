<?php
/**
 * Ofisler → Excel (.xlsx). All offices (active and passive) with every field,
 * plus team size and linked event count. Admin login required.
 */
require_once __DIR__ . '/includes/auth.php';
require_admin_login();
require_once __DIR__ . '/includes/xlsx.php';

$db = getDb();
$offices = $db->query("SELECT * FROM offices ORDER BY sort_order ASC, country ASC, name ASC")->fetchAll();

// Counts per office; tables that do not exist yet (patch not applied) just give empty counts
$count = function (string $sql) use ($db): array {
    try {
        return $db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $e) {
        return [];
    }
};
$teamCounts = $count("SELECT office_id, COUNT(*) FROM office_teams GROUP BY office_id");
$eventCounts = $count("SELECT office_id, COUNT(DISTINCT event_id) FROM (
        SELECT office_id, event_id FROM event_locations WHERE office_id IS NOT NULL
        UNION SELECT office_id, event_id FROM event_relations WHERE office_id IS NOT NULL
    ) x GROUP BY office_id");
if (!$eventCounts) {
    $eventCounts = $count("SELECT office_id, COUNT(DISTINCT event_id) FROM event_locations WHERE office_id IS NOT NULL GROUP BY office_id");
}

$columns = [
    ['title' => 'ID', 'width' => 6, 'type' => 'number'],
    ['title' => 'Durum', 'width' => 9, 'type' => 'status'],
    ['title' => 'Ülke', 'width' => 18, 'type' => 'bold'],
    ['title' => 'Ülke kodu', 'width' => 9],
    ['title' => 'Görünen ad', 'width' => 28, 'type' => 'bold'],
    ['title' => 'Ofis adı', 'width' => 28],
    ['title' => 'Adres', 'width' => 48, 'type' => 'wrap'],
    ['title' => 'Telefon', 'width' => 22, 'type' => 'wrap'],
    ['title' => 'WhatsApp', 'width' => 20],
    ['title' => 'E-posta', 'width' => 30],
    ['title' => 'Enlem', 'width' => 12, 'type' => 'number'],
    ['title' => 'Boylam', 'width' => 12, 'type' => 'number'],
    ['title' => 'Harita', 'width' => 14, 'type' => 'link'],
    ['title' => 'Sayfa adresi', 'width' => 42, 'type' => 'link'],
    ['title' => 'Ekip üyesi', 'width' => 10, 'type' => 'number'],
    ['title' => 'Bağlı etkinlik', 'width' => 11, 'type' => 'number'],
    ['title' => 'Görsel', 'width' => 36, 'type' => 'link'],
    ['title' => 'Sıra', 'width' => 7, 'type' => 'number'],
    ['title' => 'Slug', 'width' => 26],
    ['title' => 'Eski ID', 'width' => 9],
    ['title' => 'Oluşturulma', 'width' => 18],
    ['title' => 'Güncellenme', 'width' => 18],
];

// Columns added later by DB patches show up at the end without a code change
$known = ['id', 'is_active', 'country', 'country_code', 'display_name', 'name', 'address', 'phone', 'whatsapp', 'email',
    'latitude', 'longitude', 'slug', 'image_url', 'sort_order', 'legacy_id', 'created_at', 'updated_at'];
$extra = $offices ? array_values(array_diff(array_keys($offices[0]), $known)) : [];
foreach ($extra as $key) {
    $columns[] = ['title' => $key, 'width' => 20, 'type' => 'wrap'];
}

$rows = [];
foreach ($offices as $o) {
    $id = (int)$o['id'];
    $lat = (float)$o['latitude'];
    $lng = (float)$o['longitude'];
    $hasGeo = abs($lat) > 0.0001 || abs($lng) > 0.0001;
    $page = officeUrl((string)$o['slug']);
    $img = trim((string)$o['image_url']);
    $imgUrl = $img === '' ? '' : (preg_match('#^https?://#i', $img) ? $img : getBaseUrl() . '/' . ltrim($img, '/'));
    $row = [
        $id,
        (int)$o['is_active'] === 1,
        $o['country'],
        $o['country_code'],
        $o['display_name'],
        $o['name'],
        $o['address'],
        $o['phone'],
        $o['whatsapp'] ?? '',
        $o['email'],
        $hasGeo ? $lat : '',
        $hasGeo ? $lng : '',
        $hasGeo ? ['url' => 'https://www.google.com/maps?q=' . $lat . ',' . $lng, 'text' => 'Haritada aç'] : '',
        ['url' => $page, 'text' => $page],
        (int)($teamCounts[$id] ?? 0),
        (int)($eventCounts[$id] ?? 0),
        $imgUrl === '' ? '' : ['url' => $imgUrl, 'text' => $img],
        (int)$o['sort_order'],
        $o['slug'],
        $o['legacy_id'] ?? '',
        $o['created_at'] ?? '',
        $o['updated_at'] ?? '',
    ];
    foreach ($extra as $key) $row[] = (string)($o[$key] ?? '');
    $rows[] = $row;
}

$fileName = 'acibadem-ofisler-' . date('Y-m-d') . '.xlsx';
try {
    $path = xlsx_build([
        ['name' => 'Ofisler', 'columns' => $columns, 'rows' => $rows, 'freeze_cols' => 3],
    ], 'Acıbadem ofisleri');
} catch (Throwable $e) {
    header('Location: offices.php?msg=export_failed');
    exit;
}

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
readfile($path);
@unlink($path);
exit;
