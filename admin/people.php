<?php
/**
 * People who take part in events: one shared list (add once, pick again in later events).
 * Filter by Acıbadem / outside speakers. Events are edited in event_edit.php; people can also be added there.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$db = getDb();
$ready = eventsV2Ready();
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border';
$error = '';
$msg = $_GET['msg'] ?? '';

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete') {
        $db->prepare("DELETE FROM people WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
        header('Location: people.php?msg=deleted');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $photo = trim($_POST['photo_url'] ?? '');
    try {
        $uploaded = handle_image_upload('photo_file', 'people', 600);
        if ($uploaded !== null) $photo = $uploaded;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
    if ($error) {
        // keep the message, show the form again
    } elseif ($name === '') {
        $error = 'Ad soyad zorunludur.';
    } elseif ($photo !== '' && !preg_match('#^(https?://|assets/)#i', $photo)) {
        $error = 'Fotoğraf linki http(s):// ya da assets/ ile başlamalı.';
    } else {
        $vals = [$name, trim($_POST['title'] ?? '') ?: null, trim($_POST['specialty'] ?? '') ?: null, trim($_POST['organization'] ?? '') ?: null,
                 $photo ?: null, isset($_POST['is_acibadem']) ? 1 : 0];
        if ($id > 0) {
            $db->prepare("UPDATE people SET name = ?, title = ?, specialty = ?, organization = ?, photo_url = ?, is_acibadem = ? WHERE id = ?")->execute(array_merge($vals, [$id]));
        } else {
            $db->prepare("INSERT INTO people (name, title, specialty, organization, photo_url, is_acibadem) VALUES (?, ?, ?, ?, ?, ?)")->execute($vals);
        }
        header('Location: people.php?msg=saved');
        exit;
    }
}

$filter = in_array($_GET['f'] ?? '', ['acibadem', 'external'], true) ? $_GET['f'] : 'all';
$q = trim($_GET['q'] ?? '');
$people = [];
$edit = null;
if ($ready) {
    $sql = "SELECT p.*, (SELECT COUNT(*) FROM event_people ep WHERE ep.person_id = p.id) AS events_count FROM people p WHERE 1=1";
    $args = [];
    if ($filter === 'acibadem') $sql .= " AND p.is_acibadem = 1";
    if ($filter === 'external') $sql .= " AND p.is_acibadem = 0";
    if ($q !== '') {
        $sql .= " AND (p.name LIKE ? OR p.specialty LIKE ? OR p.organization LIKE ?)";
        array_push($args, "%$q%", "%$q%", "%$q%");
    }
    $stmt = $db->prepare($sql . " ORDER BY p.name ASC");
    $stmt->execute($args);
    $people = $stmt->fetchAll();
    if (isset($_GET['edit'])) {
        $stmt = $db->prepare("SELECT * FROM people WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $edit = $stmt->fetch() ?: null;
    }
}
$form = $edit ?? ['id' => 0, 'name' => '', 'title' => '', 'specialty' => '', 'organization' => '', 'photo_url' => '', 'is_acibadem' => 1];
if ($error) $form = array_merge($form, ['name' => $_POST['name'] ?? '', 'title' => $_POST['title'] ?? '', 'specialty' => $_POST['specialty'] ?? '',
    'organization' => $_POST['organization'] ?? '', 'photo_url' => $_POST['photo_url'] ?? '', 'is_acibadem' => isset($_POST['is_acibadem']) ? 1 : 0, 'id' => (int)($_POST['id'] ?? 0)]);
$tab = fn($key, $label) => '<a href="people.php' . ($key !== 'all' ? '?f=' . $key : '') . '" class="px-3 py-1.5 rounded-full text-sm ' . ($filter === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50') . '">' . $label . '</a>';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kişiler - Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ekipler</a>
            <a href="events.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Etkinlikler</a>
            <a href="people.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Kişiler</a>
            <a href="event_requests.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Talepler</a>
            <a href="settings.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ayarlar</a>
            <a href="admins.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Yöneticiler</a>
            <a href="../apply_update.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Güncellemeler</a>
            <a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Siteyi gör ↗</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8">
            <h2 class="text-xl font-semibold text-gray-800">Kişiler</h2>
        </header>

        <div class="p-4 md:p-8 flex-1 overflow-y-auto space-y-6">
            <?php if (!$ready): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-4 rounded-lg max-w-2xl">
                    <p class="font-semibold">Kişi listesi için veritabanı yaması gerekiyor.</p>
                    <p class="mt-1 text-sm"><code>patch_20261011_events_relations.sql</code> uygulanınca etkinliklere hekim, heyet ve yönetici eklenebilir.</p>
                    <a href="../apply_update.php" class="inline-block mt-3 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium">Güncellemeler</a>
                </div>
            <?php else: ?>
                <?php if ($msg === 'saved'): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg max-w-3xl">Kişi kaydedildi.</div><?php endif; ?>
                <?php if ($msg === 'deleted'): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg max-w-3xl">Kişi silindi (etkinliklerden de çıkarıldı).</div><?php endif; ?>
                <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg max-w-3xl"><?= $h($error) ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 max-w-3xl">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
                    <h3 class="text-base font-semibold text-gray-900 mb-3"><?= $form['id'] ? 'Kişiyi düzenle' : 'Yeni kişi' ?></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700">Ad soyad *</label><input type="text" dir="auto" name="name" value="<?= $h($form['name']) ?>" required class="<?= $input ?>"></div>
                        <div><label class="block text-sm font-medium text-gray-700">Unvan</label><input type="text" dir="auto" name="title" value="<?= $h($form['title']) ?>" placeholder="Prof. Dr., Genel Müdür …" class="<?= $input ?>"></div>
                        <div><label class="block text-sm font-medium text-gray-700">Branş / görev</label><input type="text" dir="auto" name="specialty" value="<?= $h($form['specialty']) ?>" class="<?= $input ?>"></div>
                        <div><label class="block text-sm font-medium text-gray-700">Kurum</label><input type="text" dir="auto" name="organization" value="<?= $h($form['organization']) ?>" class="<?= $input ?>"></div>
                        <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Fotoğraf (isteğe bağlı)</label>
                            <input type="file" name="photo_file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700">
                            <input type="text" name="photo_url" value="<?= $h($form['photo_url']) ?>" placeholder="ya da fotoğraf linki: https://… / assets/images/…" class="<?= $input ?>">
                            <?php if (!empty($form['photo_url'])): ?><img src="<?= $h(admin_image_src($form['photo_url'])) ?>" alt="" class="mt-2 h-16 w-16 rounded-full object-cover border border-gray-200"><?php endif; ?></div>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_acibadem" value="1" <?= $form['is_acibadem'] ? 'checked' : '' ?>> Acıbadem'de çalışıyor</label>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <?php if ($form['id']): ?><a href="people.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">İptal</a><?php endif; ?>
                        <button type="submit" class="py-2 px-4 rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">Kaydet</button>
                    </div>
                </form>

                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <?= $tab('all', 'Tümü') ?><?= $tab('acibadem', 'Acıbadem') ?><?= $tab('external', 'Acıbadem dışı') ?>
                        <form method="GET" class="ml-auto flex gap-2">
                            <?php if ($filter !== 'all'): ?><input type="hidden" name="f" value="<?= $h($filter) ?>"><?php endif; ?>
                            <input type="search" name="q" value="<?= $h($q) ?>" placeholder="Ad, branş ya da kurum ara" class="rounded-md border-gray-300 border px-3 py-1.5 text-sm">
                        </form>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kişi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branş / kurum</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etkinlik</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200">
                            <?php foreach ($people as $p): ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm"><div class="font-bold text-gray-900" dir="auto"><?= $h($p['name']) ?></div>
                                        <div class="text-xs text-gray-500" dir="auto"><?= $h($p['title']) ?><?= $p['is_acibadem'] ? '' : ' · <span class="text-amber-700">Acıbadem dışı</span>' ?></div></td>
                                    <td class="px-4 py-3 text-sm text-gray-700" dir="auto"><?= $h(trim($p['specialty'] . ($p['specialty'] && $p['organization'] ? ' · ' : '') . $p['organization'])) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= (int)$p['events_count'] ?></td>
                                    <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                        <a href="people.php?edit=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Düzenle</a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Bu kişi listeden silinecek ve <?= (int)$p['events_count'] ?> etkinlikten çıkarılacak. Emin misiniz?');">
                                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$people): ?><tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Kişi yok.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
