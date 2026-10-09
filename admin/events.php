<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$db = getDb();
$ready = eventsReady();

if ($ready && ($_POST['action'] ?? '') === 'delete' && isset($_POST['id'])) {
    $db->prepare("DELETE FROM events WHERE id = ?")->execute([(int)$_POST['id']]);
    header('Location: events.php?msg=deleted');
    exit;
}

$events = $ready ? sortEvents(getEvents(false)) : [];
$typeLabels = ['act_tag_doctor' => 'Doktor görüşmesi', 'act_tag_presentation' => 'Sunum', 'act_tag_exhibition' => 'Fuar / Sergi', 'act_tag_webinar' => 'Online / Webinar'];
$msg = $_GET['msg'] ?? '';
$fmt = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlikler - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ekipler</a>
            <a href="events.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Etkinlikler</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 md:px-8 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Etkinlikler</h2>
            <?php if ($ready): ?>
            <a href="event_edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">+ Yeni Etkinlik</a>
            <?php endif; ?>
        </header>

        <div class="p-4 md:p-8 flex-1 overflow-y-auto">
            <?php if (!$ready): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-4 rounded-lg max-w-2xl">
                    <p class="font-semibold">Yeni etkinlik yapısı için veritabanı yaması gerekiyor.</p>
                    <p class="mt-1 text-sm">Bir etkinliğin birden fazla ofis/mekânda ve farklı tarihlerde yapılabilmesi için <code>patch_20261009_events.sql</code> uygulanmalı. Mevcut etkinlikler yeni yapıya kopyalanır, eski tablo silinmez.</p>
                    <a href="../apply_update.php" class="inline-block mt-3 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium">Yamayı uygula</a>
                    <a href="activities.php?legacy=1" class="inline-block mt-3 ml-3 text-sm underline">Eski etkinlik ekranı</a>
                </div>
            <?php else: ?>
                <?php if ($msg === 'deleted'): ?>
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Etkinlik silindi.</div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etkinlik</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duraklar</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($events as $ev): ?>
                            <tr class="<?= $ev['status'] === 'past' ? 'opacity-60' : '' ?>">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= $ev['start'] ? $fmt($ev['start']) . ($ev['end'] && $ev['end'] !== $ev['start'] ? ' – ' . $fmt($ev['end']) : '') : '<span class="text-gray-400">Tarihsiz</span>' ?>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-bold text-gray-900" dir="auto"><?= htmlspecialchars($ev['title']) ?></div>
                                    <div class="text-xs text-blue-600 mt-1"><?= htmlspecialchars($typeLabels[$ev['tag_key']] ?? $ev['tag_key']) ?></div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    <?php foreach ($ev['locations'] as $l): ?>
                                        <div class="whitespace-nowrap"><?= $l['is_online'] ? 'Online' : htmlspecialchars($l['name']) ?><?= $l['starts_on'] ? ' <span class="text-gray-400">· ' . $fmt($l['starts_on']) . '</span>' : '' ?></div>
                                    <?php endforeach; ?>
                                    <?php if (!$ev['locations']): ?><span class="text-red-600">Durak yok</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <?php if (!$ev['is_published']): ?><span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Taslak</span>
                                    <?php elseif ($ev['status'] === 'past'): ?><span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Geçmiş</span>
                                    <?php else: ?><span class="px-2 py-0.5 rounded-full bg-green-50 text-green-700">Yayında</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= htmlspecialchars($ev['url']) ?>" target="_blank" class="text-gray-500 hover:text-gray-800 mr-4">Gör</a>
                                    <a href="event_edit.php?id=<?= $ev['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Düzenle</a>
                                    <form method="POST" action="events.php" class="inline" onsubmit="return confirm('Bu etkinlik ve tüm durakları silinecek. Emin misiniz?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$events): ?>
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Henüz etkinlik yok.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Tarihi geçen etkinlikler sitede kendiliğinden "Geçmiş etkinlikler" bölümüne iner; silmenize gerek yok.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
