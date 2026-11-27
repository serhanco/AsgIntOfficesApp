<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$db = getDb();
$ready = eventsReady();
$isContrib = is_contributor();
$meId = (int)$_SESSION['admin_user_id'];
// Who entered each event (empty before patch_20261127_contributor_role.sql)
$owners = [];
if ($ready && eventsCreatedByReady()) {
    foreach ($db->query("SELECT e.id, e.created_by, u.username FROM events e LEFT JOIN admin_users u ON u.id = e.created_by")->fetchAll() as $r) {
        $owners[(int)$r['id']] = ['id' => (int)$r['created_by'], 'name' => (string)$r['username']];
    }
}
$postId = (int)($_POST['id'] ?? 0);
// A contributor may change only their own drafts
$canEdit = function (int $eventId, bool $published) use ($isContrib, $owners, $meId): bool {
    return !$isContrib || (!$published && ($owners[$eventId]['id'] ?? 0) === $meId);
};
$isPublished = function (int $eventId) use ($db): bool {
    $st = $db->prepare("SELECT is_published FROM events WHERE id = ?");
    $st->execute([$eventId]);
    return (bool)$st->fetchColumn();
};

if ($ready && ($_POST['action'] ?? '') === 'publish' && !$isContrib && $postId > 0) {
    $db->prepare("UPDATE events SET is_published = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$postId]);
    header('Location: events.php?msg=published');
    exit;
}

if ($ready && ($_POST['action'] ?? '') === 'delete' && isset($_POST['id']) && $canEdit($postId, $isPublished($postId))) {
    // Its address keeps working: QR codes and old links open the events page
    $stmt = $db->prepare("SELECT slug FROM events WHERE id = ?");
    $stmt->execute([(int)$_POST['id']]);
    $oldSlug = (string)$stmt->fetchColumn();
    if ($oldSlug !== '') seoRememberMove('/event/' . $oldSlug, '/events');
    $db->prepare("DELETE FROM events WHERE id = ?")->execute([(int)$_POST['id']]);
    header('Location: events.php?msg=deleted');
    exit;
}

$events = $ready ? sortEvents(getEvents(false, 'all')) : [];
// Drafts first, so what waits for approval is on top
usort($events, fn($a, $b) => (int)$a['is_published'] <=> (int)$b['is_published']);
$draftCount = count(array_filter($events, fn($e) => !$e['is_published']));
$typeLabels = eventTypeLabelsTr();
$msg = $_GET['msg'] ?? '';
$fmt = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlikler - Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <?= admin_sidebar('events.php') ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 md:px-8 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Etkinlikler</h2>
            <?php if ($ready): ?>
            <a href="event_edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">+ Yeni Etkinlik</a>
            <?php endif; ?>
        </header>
        <?= admin_mobile_nav('events.php') ?>

        <div class="p-4 md:p-8 flex-1 overflow-y-auto">
            <?php if (!$ready): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-4 rounded-lg max-w-2xl">
                    <p class="font-semibold">Yeni etkinlik yapısı için veritabanı yaması gerekiyor.</p>
                    <p class="mt-1 text-sm">Bir etkinliğin birden fazla ofis/mekânda ve farklı tarihlerde yapılabilmesi için <code>patch_20261009_events.sql</code> uygulanmalı. Mevcut etkinlikler yeni yapıya kopyalanır, eski tablo silinmez.</p>
                    <a href="../apply_update.php" class="inline-block mt-3 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium">Yamayı uygula</a>
                    <a href="activities.php?legacy=1" class="inline-block mt-3 ml-3 text-sm underline">Eski etkinlik ekranı</a>
                </div>
            <?php else: ?>
                <?php if ($msg === 'deleted' || $msg === 'published'): ?>
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?= $msg === 'deleted' ? 'Etkinlik silindi.' : 'Etkinlik yayına alındı.' ?></div>
                <?php endif; ?>
                <?php if ($isContrib): ?>
                    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">Eklediğiniz etkinlikler <b>taslak</b> olarak kaydedilir ve sitede görünmez. Bir yönetici kontrol edip yayına alır. Yayına alınana kadar kendi taslaklarınızı düzenleyebilir veya silebilirsiniz; diğer taslakları yalnızca inceleyebilirsiniz.</div>
                <?php elseif ($draftCount): ?>
                    <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm"><?= $draftCount ?> etkinlik taslakta. Kontrol edip "Yayınla" ile yayına alabilir ya da "Düzenle" ekranında "Yayında" kutusunu işaretleyebilirsiniz.</div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etkinlik</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasyonlar</th>
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
                                    <div class="text-xs text-blue-600 mt-1"><?= htmlspecialchars($typeLabels[$ev['tag_key']] ?? $ev['tag_key']) ?>
                                        · <?= $ev['audience'] === 'b2b' ? 'Kurumlar' : 'Hastalar' ?>
                                        <?php if ($ev['featured_rank'] > 0): ?>· <span class="font-semibold text-amber-700">Öne çıkan <?= (int)$ev['featured_rank'] ?></span><?php endif; ?>
                                        <?php if ($ev['visibility'] === 'link'): ?>· <span class="text-gray-600">Yalnız linkle</span><?php elseif ($ev['visibility'] === 'internal'): ?>· <span class="text-gray-600">İç kayıt</span><?php endif; ?>
                                        <?php if ($ev['external']): ?>· <span class="text-gray-600">Dış link ↗</span><?php endif; ?>
                                        <?php if ($ev['is_global']): ?>· <span class="text-gray-600">Konumdan bağımsız</span><?php endif; ?></div>
                                    <?php if (!empty($owners[$ev['id']]['name'])): ?><div class="text-xs text-gray-500 mt-1">Ekleyen: <?= htmlspecialchars($owners[$ev['id']]['name']) ?><?= $owners[$ev['id']]['id'] === $meId ? ' (siz)' : '' ?></div><?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    <?php foreach ($ev['locations'] as $l): ?>
                                        <div class="whitespace-nowrap"><?= $l['is_online'] ? 'Online' : ($l['floating'] ? '<span class="text-gray-400">Yalnız tarih</span>' : htmlspecialchars($l['name'])) ?><?= $l['starts_on'] ? ' <span class="text-gray-400">· ' . $fmt($l['starts_on']) . '</span>' : '' ?></div>
                                    <?php endforeach; ?>
                                    <?php if (!$ev['locations']): ?><span class="text-red-600">Lokasyon yok</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <?php if (!$ev['is_published']): ?><span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Taslak · onay bekliyor</span>
                                    <?php elseif ($ev['status'] === 'past'): ?><span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Geçmiş</span>
                                    <?php else: ?><span class="px-2 py-0.5 rounded-full bg-green-50 text-green-700">Yayında</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php $mine = $canEdit((int)$ev['id'], (bool)$ev['is_published']); ?>
                                    <a href="<?= htmlspecialchars($ev['url']) ?>" target="_blank" class="text-gray-500 hover:text-gray-800 mr-4"><?= $ev['is_published'] ? 'Gör' : 'Önizle' ?></a>
                                    <?php if (!$ev['is_published'] && !$isContrib): ?>
                                    <form method="POST" action="events.php" class="inline mr-4" onsubmit="return confirm('Bu etkinlik sitede yayınlansın mı?');"><?= csrf_field() ?><input type="hidden" name="action" value="publish"><input type="hidden" name="id" value="<?= $ev['id'] ?>"><button type="submit" class="text-green-700 hover:text-green-900">Yayınla</button></form>
                                    <?php endif; ?>
                                    <a href="event_edit.php?id=<?= $ev['id'] ?>" class="text-indigo-600 hover:text-indigo-900<?= $mine ? ' mr-4' : '' ?>"><?= $mine ? 'Düzenle' : 'İncele' ?></a>
                                    <?php if ($mine): ?>
                                    <form method="POST" action="events.php" class="inline" onsubmit="return confirm('Bu etkinlik ve tüm lokasyonları silinecek. Emin misiniz?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                    </form>
                                    <?php endif; ?>
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
