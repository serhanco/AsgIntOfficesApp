<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
$office = getOfficeBySlug($slug);

if (!$office) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$relatedOffices = getOfficesByCountry($office['country'], $office['id']);
$pageTitle = $office['display_name'] . ' — ' . $office['country'];
$currentPage = 'office';
$needsMap = true;
$metaDescription = 'Acıbadem Information Office in ' . $office['display_name'] . ', ' . $office['country'] . '. Contact: ' . $office['phone'] . ' | ' . $office['email'];

$team = getOfficeTeam($office['id']);
$activities = getOfficeActivities($office['id']);

// ===== DEMO MODE =====
// '?demo' at the end of an office URL (logged-in admins only) fills an empty team / event list
// with sample data, to show the office page with staff before real records are entered.
$demoMode = isset($_GET['demo']) && isAdminVisitor();
$demoParts = [];
if (empty($team) && $demoMode) {
    $demoParts[] = 'team';
    $team = [
        [
            'name' => 'Cem Üstündağ',
            'role_key' => 'team_role_coord',
            'languages' => 'Turkish, English',
            'status' => 'online',
            'image_url' => getBaseUrl() . '/assets/images/team-cem-ustundag-sm.jpg'
        ],
        [
            'name' => 'Erim Ekiz',
            'role_key' => 'team_role_patient',
            'languages' => 'Turkish, English, German',
            'status' => 'online',
            'image_url' => getBaseUrl() . '/assets/images/team-erim-ekiz-sm.jpg'
        ],
        [
            'name' => 'Ionea Ruxandra',
            'role_key' => 'team_role_liaison',
            'languages' => 'Romanian, English, French',
            'status' => 'away',
            'image_url' => getBaseUrl() . '/assets/images/team-ionea-ruxandra-sm.jpg'
        ]
    ];
}

// Sample events are shown next to the office's real events (never instead of them),
// as upcoming event cards in the same style. They link nowhere.
$demoActs = [];
if ($demoMode) {
    $demoParts[] = 'activities';
    $soon = fn(int $days) => date('Y-m-d', strtotime('+' . $days . ' days'));
    $demoActs = [
        ['tag_key' => 'act_tag_doctor',       'title' => 'Prof. Yaşar Çolak',            'description' => 'Gastroenterology, Endoscopy and Nutrition',    'start' => $soon(12), 'end' => $soon(12)],
        ['tag_key' => 'act_tag_presentation', 'title' => 'Robotic Single Port Surgery',  'description' => 'Latest techniques in minimal-invasive care',   'start' => $soon(26), 'end' => $soon(26)],
        ['tag_key' => 'act_tag_exhibition',   'title' => 'Growing B2B Network',          'description' => 'International healthcare partnerships fair', 'start' => $soon(40), 'end' => $soon(42)],
    ];
    $demoActs = array_map(fn($a) => $a + ['url' => '', 'ext' => false, 'status' => 'upcoming', 'demo' => true], $demoActs);
}
// ==============================

$hasTeam = !empty($team);
$hasActivities = !empty($activities) || !empty($demoActs);

// Determine grid layout based on available data
$gridCols = ($hasTeam && $hasActivities) ? 'md:grid-cols-2' : 'md:grid-cols-1';

require __DIR__ . '/includes/v2/pages/office.php';
