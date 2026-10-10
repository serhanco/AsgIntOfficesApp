<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

// Logout logic
if (($_POST['action'] ?? '') === 'logout') { // POST only (CSRF-checked in auth.php)
    admin_logout();
    header('Location: login.php');
    exit;
}


$db = getDb();
$one = fn(string $sql) => (int)$db->query($sql)->fetchColumn();

$officeCount = $one("SELECT COUNT(*) FROM offices WHERE is_active = 1");
$countryCount = $one("SELECT COUNT(DISTINCT country) FROM offices WHERE is_active = 1");
$teamCount = $one("SELECT COUNT(*) FROM office_teams");
$hasWhatsapp = (bool)$db->query("SHOW COLUMNS FROM offices LIKE 'whatsapp'")->fetchColumn();

$ready = eventsReady();
$events = $ready ? sortEvents(getEvents(false)) : [];
$upcoming = array_values(array_filter($events, fn($e) => $e['status'] === 'upcoming'));
$drafts = array_values(array_filter($events, fn($e) => !$e['is_published']));

// Things that need attention
$todo = [];
if (!$ready) $todo[] = ['Etkinlik veritabanı yaması bekliyor', 'Yamayı uygula', '../apply_update.php', 'amber'];
if (!$hasWhatsapp) $todo[] = ['WhatsApp alanı için veritabanı yaması bekliyor', 'Yamayı uygula', '../apply_update.php', 'amber'];
if (!$db->query("SHOW TABLES LIKE 'site_settings'")->fetchColumn()) $todo[] = ['Ayarlar ekranı için veritabanı yaması bekliyor', 'Yamayı uygula', '../apply_update.php', 'amber'];
$reqReady = (bool)$db->query("SHOW TABLES LIKE 'event_requests'")->fetchColumn();
if (!$reqReady) $todo[] = ['Etkinlik talepleri için veritabanı yaması bekliyor', 'Yamayı uygula', '../apply_update.php', 'amber'];
else {
    $n = $one("SELECT COUNT(*) FROM event_requests WHERE status = 'new'");
    if ($n > 0) array_unshift($todo, [$n . ' etkinlik talebi yanıt bekliyor', 'Talepler', 'event_requests.php', 'blue']);
}
if ($hasWhatsapp) {
    $n = $one("SELECT COUNT(*) FROM offices WHERE is_active = 1 AND (whatsapp IS NULL OR whatsapp = '')");
    if ($n > 0) $todo[] = [$n . ' ofiste WhatsApp numarası boş (butonu görünmez)', 'Ofisler', 'offices.php', 'blue'];
}
$n = $one("SELECT COUNT(*) FROM offices WHERE is_active = 1 AND (email = '' OR phone = '')");
if ($n > 0) $todo[] = [$n . ' ofiste telefon veya e-posta eksik', 'Ofisler', 'offices.php', 'blue'];
if ($drafts) $todo[] = [count($drafts) . ' etkinlik yayında değil (taslak)', 'Etkinlikler', 'events.php', 'blue'];
$undated = count(array_filter($upcoming, fn($e) => !$e['start']));
if ($undated > 0) $todo[] = [$undated . ' etkinliğin tarihi yok', 'Etkinlikler', 'events.php', 'blue'];

$fmt = fn($d) => $d ? date('d.m.Y', strtotime($d)) : 'Tarih yok';
$nav = ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'event_requests.php' => 'Talepler', 'settings.php' => 'Ayarlar'];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="block px-4 py-2 rounded-md <?= $href === 'index.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Siteyi gör ↗</a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <div class="text-sm text-gray-400 mb-2">Giriş yapan: <?= $h($_SESSION['admin_username']) ?></div>
            <form method="POST" action="index.php"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button type="submit" class="block w-full text-center px-4 py-2 border border-gray-600 rounded-md text-sm text-gray-300 hover:bg-gray-800 hover:text-white">Çıkış Yap</button></form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 md:px-8 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
            <form method="POST" action="index.php" class="md:hidden"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button type="submit" class="text-sm text-gray-500 underline">Çıkış</button></form>
        </header>
        <!-- Mobile menu -->
        <nav class="md:hidden bg-gray-900 text-sm flex overflow-x-auto">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="px-4 py-3 whitespace-nowrap <?= $href === 'index.php' ? 'text-white bg-gray-800' : 'text-gray-300' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 md:p-8 space-y-6 max-w-6xl w-full">
            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="offices.php" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:border-blue-300">
                    <div class="text-sm font-medium text-blue-600">Aktif ofis</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1"><?= $officeCount ?></div>
                </a>
                <a href="offices.php" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:border-blue-300">
                    <div class="text-sm font-medium text-sky-600">Ülke</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1"><?= $countryCount ?></div>
                </a>
                <a href="events.php" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:border-purple-300">
                    <div class="text-sm font-medium text-purple-600">Yaklaşan etkinlik</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1"><?= $ready ? count($upcoming) : '–' ?></div>
                    <?php if ($ready): ?><div class="text-xs text-gray-500 mt-1">Toplam <?= count($events) ?></div><?php endif; ?>
                </a>
                <a href="teams.php" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:border-green-300">
                    <div class="text-sm font-medium text-green-600">Ekip üyesi</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1"><?= $teamCount ?></div>
                </a>
            </div>

            <!-- Quick actions -->
            <div class="flex flex-wrap gap-3">
                <a href="event_edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">+ Yeni etkinlik</a>
                <a href="office_edit.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">+ Yeni ofis</a>
                <a href="../" target="_blank" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">Siteyi gör ↗</a>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Attention -->
                <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-3">Dikkat edilecekler</h3>
                    <?php if (!$todo): ?>
                        <p class="text-sm text-green-700 bg-green-50 border border-green-100 rounded-md px-3 py-2">Her şey yolunda, bekleyen bir iş yok.</p>
                    <?php else: ?>
                    <ul class="space-y-2">
                        <?php foreach ($todo as [$text, $label, $href, $tone]): ?>
                        <li class="flex items-center justify-between gap-3 text-sm rounded-md px-3 py-2 <?= $tone === 'amber' ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-gray-50 border border-gray-200 text-gray-700' ?>">
                            <span><?= $h($text) ?></span>
                            <a href="<?= $h($href) ?>" class="font-medium underline whitespace-nowrap"><?= $h($label) ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>

                <!-- Upcoming events -->
                <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-900">Yaklaşan etkinlikler</h3>
                        <a href="events.php" class="text-sm text-blue-600 hover:underline">Tümü</a>
                    </div>
                    <?php if (!$ready): ?>
                        <p class="text-sm text-gray-500">Etkinlik yapısı için veritabanı yaması gerekiyor.</p>
                    <?php elseif (!$upcoming): ?>
                        <p class="text-sm text-gray-500">Yaklaşan etkinlik yok. <a href="event_edit.php" class="text-blue-600 underline">Yeni etkinlik ekle</a></p>
                    <?php else: ?>
                    <ul class="divide-y divide-gray-100">
                        <?php foreach (array_slice($upcoming, 0, 5) as $ev): ?>
                        <li class="py-2 flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="font-medium text-gray-900 truncate"><?= $h($ev['title']) ?><?= $ev['is_published'] ? '' : ' <span class="text-xs text-amber-700">(taslak)</span>' ?></div>
                                <div class="text-xs text-gray-500"><?= $h($fmt($ev['start'])) ?> · <?= count($ev['locations']) ?> durak</div>
                            </div>
                            <span class="whitespace-nowrap">
                                <a href="event_edit.php?id=<?= (int)$ev['id'] ?>" class="text-indigo-600 hover:underline mr-3">Düzenle</a>
                                <a href="<?= $h($ev['url']) ?>" target="_blank" class="text-gray-500 hover:underline">Gör</a>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>
            </div>

            <div class="text-sm text-gray-500 flex flex-wrap gap-x-6 gap-y-1">
                <a href="../apply_update.php" class="hover:underline">Veritabanı güncellemeleri</a>
            </div>
        </div>
    </main>
</body>
</html>
