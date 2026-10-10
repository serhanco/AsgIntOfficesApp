<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/v2/events-ui.php';
require_admin_login();

$db = getDb();
$ready = (bool)$db->query("SHOW TABLES LIKE 'event_requests'")->fetchColumn();
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($id > 0 && $act === 'answer') {
        $db->prepare("UPDATE event_requests SET status = 'answered', answered_at = NOW(), note = ? WHERE id = ?")
           ->execute([mb_substr(trim((string)($_POST['note'] ?? '')), 0, 1000) ?: null, $id]);
    } elseif ($id > 0 && $act === 'reopen') {
        $db->prepare("UPDATE event_requests SET status = 'new', answered_at = NULL WHERE id = ?")->execute([$id]);
    } elseif ($id > 0 && $act === 'delete') {
        $db->prepare("DELETE FROM event_requests WHERE id = ?")->execute([$id]);
    }
    header('Location: event_requests.php?show=' . urlencode($_POST['show'] ?? 'new'));
    exit;
}

$show = in_array($_GET['show'] ?? 'new', ['new', 'answered', 'info', 'all'], true) ? ($_GET['show'] ?? 'new') : 'new';
$rows = [];
$counts = ['new' => 0, 'answered' => 0, 'info' => 0];
if ($ready) {
    foreach ($db->query("SELECT status, COUNT(*) c FROM event_requests GROUP BY status") as $r) $counts[$r['status']] = (int)$r['c'];
    $where = $show === 'all' ? '' : "WHERE status = " . $db->quote($show);
    $rows = $db->query("SELECT * FROM event_requests $where ORDER BY created_at DESC LIMIT 300")->fetchAll();
}
$typeLabels = eventTypeLabelsTr();
$channels = ['form' => 'Form', 'whatsapp' => 'WhatsApp açtı', 'email' => 'E-posta açtı'];
$nav = ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'people.php' => 'Kişiler', 'event_requests.php' => 'Talepler', 'settings.php' => 'Ayarlar', 'admins.php' => 'Yöneticiler', '../apply_update.php' => 'Güncellemeler'];

function contactLink(string $c): string {
    if ($c === '') return '<span class="text-gray-400">–</span>';
    $e = htmlspecialchars($c, ENT_QUOTES, 'UTF-8');
    if (strpos($c, '@') !== false) return '<a class="text-blue-600 hover:underline" href="mailto:' . $e . '">' . $e . '</a>';
    $d = preg_replace('/\D/', '', $c);
    return strlen($d) >= 6 ? '<a class="text-blue-600 hover:underline" target="_blank" rel="noopener" href="https://wa.me/' . $d . '">' . $e . '</a>' : $e;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlik talepleri - Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="block px-4 py-2 rounded-md <?= $href === 'event_requests.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Siteyi gör ↗</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8">
            <h2 class="text-xl font-semibold text-gray-800">Etkinlik talepleri</h2>
        </header>
        <nav class="md:hidden bg-gray-900 text-sm flex overflow-x-auto">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="px-4 py-3 whitespace-nowrap <?= $href === 'event_requests.php' ? 'text-white bg-gray-800' : 'text-gray-300' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 md:p-8 space-y-4 max-w-6xl w-full">
            <?php if (!$ready): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-4 rounded-lg">
                    <p class="font-semibold">Bu ekran için veritabanı yaması gerekiyor.</p>
                    <p class="mt-1 text-sm"><code>patch_20261010_event_requests.sql</code> bir tablo ekler, mevcut verilere dokunmaz. Uygulanana kadar form eskisi gibi yalnızca WhatsApp/e-posta açar.</p>
                    <a href="../apply_update.php" class="inline-block mt-3 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium">Yamayı uygula</a>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-600">Ziyaretçilerin "Etkinlik talep et" formundan gönderdikleri talepler. Formdan gelenler "Bekleyen" listesindedir; yanıtladığınızı "Yanıtlandı" yapın. Ziyaretçi formu doldurup WhatsApp veya e-postayı açtıysa, bu yalnızca bilgi için "WhatsApp / e-posta tıklamaları" sekmesine düşer (talep o kanaldan size zaten ulaşır).</p>
                <div class="flex flex-wrap gap-2 text-sm">
                    <?php foreach (['new' => 'Bekleyen (' . $counts['new'] . ')', 'answered' => 'Yanıtlanan (' . $counts['answered'] . ')', 'info' => 'WhatsApp / e-posta tıklamaları (' . $counts['info'] . ')', 'all' => 'Hepsi'] as $k => $label): ?>
                    <a href="?show=<?= $k ?>" class="px-3 py-1.5 rounded-full border <?= $show === $k ? 'bg-gray-800 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?>"><?= $h($label) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if (!$rows): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500 text-sm">Bu listede talep yok.</div>
                <?php else: ?>
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr><th class="px-4 py-3 text-left">Tarih</th><th class="px-4 py-3 text-left">Talep</th><th class="px-4 py-3 text-left">İletişim</th><th class="px-4 py-3 text-left">Kaynak</th><th class="px-4 py-3 text-right">İşlem</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <?php foreach ($rows as $r): ?>
                            <tr class="align-top <?= $r['status'] === 'answered' ? 'bg-gray-50 text-gray-500' : '' ?>">
                                <td class="px-4 py-3 whitespace-nowrap"><?= $h(date('d.m.Y H:i', strtotime($r['created_at']))) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?= $h($typeLabels[$r['type_key']] ?? $r['type_key']) ?></div>
                                    <div><?= $r['place'] !== '' ? $h($r['place']) : '<span class="text-gray-400">Yer yazılmamış</span>' ?></div>
                                    <?php if ($r['note']): ?><div class="mt-1 text-xs text-gray-500">Not: <?= $h($r['note']) ?></div><?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><?= contactLink((string)$r['contact']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                    <?= $h($channels[$r['channel']] ?? $r['channel']) ?><br>
                                    <?= $h(trim($r['country'] . ' ' . strtoupper($r['lang']))) ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form method="POST" class="inline-flex items-center gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="show" value="<?= $h($show) ?>">
                                        <?php if ($r['status'] === 'new'): ?>
                                            <input type="text" name="note" maxlength="1000" placeholder="Not (isteğe bağlı)" class="hidden sm:block w-36 border border-gray-300 rounded-md px-2 py-1 text-xs">
                                            <button name="action" value="answer" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs font-medium">Yanıtlandı</button>
                                        <?php else: ?>
                                            <button name="action" value="reopen" class="text-indigo-600 hover:underline text-xs">Yeniden aç</button>
                                        <?php endif; ?>
                                        <button name="action" value="delete" class="text-red-600 hover:underline text-xs" onclick="return confirm('Bu talep silinsin mi?');">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
