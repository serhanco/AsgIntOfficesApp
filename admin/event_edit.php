<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

if (!eventsReady()) {
    header('Location: events.php');
    exit;
}

$db = getDb();
$error = '';
$success = '';
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$offices = $db->query("SELECT id, display_name, country, country_code FROM offices ORDER BY country ASC, display_name ASC")->fetchAll();
$typeLabels = ['act_tag_doctor' => 'Doktor görüşmesi', 'act_tag_presentation' => 'Sunum', 'act_tag_exhibition' => 'Fuar / Sergi', 'act_tag_webinar' => 'Online / Webinar'];

$event = ['title' => '', 'slug' => '', 'tag_key' => 'act_tag_doctor', 'description' => '', 'image_url' => '', 'link_url' => '', 'is_published' => 1, 'sort_order' => 0];
$blankStop = ['kind' => 'office', 'office_id' => '', 'venue_name' => '', 'city' => '', 'country_code' => '', 'address' => '', 'coords' => '',
              'starts_on' => '', 'ends_on' => '', 'start_time' => '', 'end_time' => ''];
$stops = [$blankStop];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { header('Location: events.php'); exit; }
    $event = $row;
    $stmt = $db->prepare("SELECT * FROM event_locations WHERE event_id = ? ORDER BY sort_order, id");
    $stmt->execute([$id]);
    $stops = array_map(function ($s) {
        $venue = trim($s['venue_name'] . $s['city'] . $s['address']) !== '';
        return [
            'kind' => $s['is_online'] ? 'online' : ($venue ? 'venue' : 'office'),
            'office_id' => $s['office_id'], 'venue_name' => $s['venue_name'], 'city' => $s['city'], 'country_code' => $s['country_code'],
            'address' => $s['address'], 'coords' => $s['latitude'] !== null ? rtrim(rtrim($s['latitude'], '0'), '.') . ', ' . rtrim(rtrim($s['longitude'], '0'), '.') : '',
            'starts_on' => $s['starts_on'], 'ends_on' => $s['ends_on'],
            'start_time' => $s['start_time'] ? substr($s['start_time'], 0, 5) : '', 'end_time' => $s['end_time'] ? substr($s['end_time'], 0, 5) : '',
        ];
    }, $stmt->fetchAll()) ?: [$blankStop];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event['title'] = trim($_POST['title'] ?? '');
    $event['slug'] = trim($_POST['slug'] ?? '');
    $event['tag_key'] = array_key_exists($_POST['tag_key'] ?? '', $typeLabels) ? $_POST['tag_key'] : 'act_tag_doctor';
    $event['description'] = trim($_POST['description'] ?? '');
    $event['image_url'] = trim($_POST['image_url'] ?? '');
    $event['link_url'] = trim($_POST['link_url'] ?? '');
    $event['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $event['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    try {
        $uploaded = handle_image_upload('image_file', 'events', 1600);
        if ($uploaded !== null) $event['image_url'] = $uploaded;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }

    $stops = [];
    foreach ((array)($_POST['stops'] ?? []) as $s) {
        $stop = array_merge($blankStop, array_map(fn($v) => is_string($v) ? trim($v) : '', (array)$s));
        if (!in_array($stop['kind'], ['office', 'venue', 'online'], true)) $stop['kind'] = 'office';
        $empty = $stop['office_id'] === '' && $stop['venue_name'] === '' && $stop['city'] === '' && $stop['address'] === '' && $stop['starts_on'] === '' && $stop['kind'] !== 'online';
        if (!$empty) $stops[] = $stop;
    }

    if (!$error && $event['title'] === '') $error = 'Başlık zorunludur.';
    if (!$error && !$stops) $error = 'En az bir durak (ofis, mekân ya da online) ekleyin.';
    if (!$error && $event['link_url'] !== '' && !preg_match('#^https?://#i', $event['link_url'])) $error = 'Kayıt/bilgi linki http:// veya https:// ile başlamalı.';
    foreach ($stops as $i => $s) {
        if ($error) break;
        $n = $i + 1;
        if ($s['kind'] === 'office' && $s['office_id'] === '') $error = "$n. durak: ofis seçin.";
        elseif ($s['kind'] === 'venue' && $s['venue_name'] === '' && $s['city'] === '') $error = "$n. durak: mekân adı ya da şehir girin.";
        elseif ($s['kind'] === 'venue' && $s['office_id'] === '' && !preg_match('/^[a-z]{2}$/i', $s['country_code'])) $error = "$n. durak: ofis seçmediyseniz ülke kodunu girin (ör. US).";
        elseif ($s['coords'] !== '' && !preg_match('/^\s*-?\d{1,2}(\.\d+)?\s*,\s*-?\d{1,3}(\.\d+)?\s*$/', $s['coords'])) $error = "$n. durak: koordinat \"40.4093, 49.8671\" biçiminde olmalı.";
        elseif ($s['ends_on'] !== '' && $s['starts_on'] !== '' && $s['ends_on'] < $s['starts_on']) $error = "$n. durak: bitiş tarihi başlangıçtan önce olamaz.";
    }

    if (!$error) {
        // Slug: typed one, else title + first city + year (titles in Arabic, Russian … give an ASCII slug this way too)
        if ($event['slug'] === '') {
            $first = $stops[0];
            $city = $first['city'];
            if ($city === '' && $first['office_id'] !== '') {
                foreach ($offices as $o) if ((int)$o['id'] === (int)$first['office_id']) $city = $o['display_name'];
            }
            $event['slug'] = $event['title'] . ' ' . $city . ' ' . ($first['starts_on'] ? substr($first['starts_on'], 0, 4) : '');
        }
        $event['slug'] = uniqueEventSlug($event['slug'], $id);

        try {
            $db->beginTransaction();
            $fields = [$event['slug'], $event['tag_key'], $event['title'], $event['description'], $event['image_url'] ?: null,
                       $event['link_url'] ?: null, $event['is_published'], $event['sort_order']];
            if ($isEdit) {
                $db->prepare("UPDATE events SET slug=?, tag_key=?, title=?, description=?, image_url=?, link_url=?, is_published=?, sort_order=? WHERE id=?")
                   ->execute(array_merge($fields, [$id]));
                $db->prepare("DELETE FROM event_locations WHERE event_id = ?")->execute([$id]);
            } else {
                $db->prepare("INSERT INTO events (slug, tag_key, title, description, image_url, link_url, is_published, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                   ->execute($fields);
                $id = (int)$db->lastInsertId();
            }
            $ins = $db->prepare("INSERT INTO event_locations (event_id, office_id, venue_name, city, country_code, address, latitude, longitude, is_online,
                                 starts_on, ends_on, start_time, end_time, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($stops as $i => $s) {
                $atVenue = $s['kind'] === 'venue';
                [$lat, $lon] = ($atVenue && $s['coords'] !== '') ? array_map('floatval', explode(',', $s['coords'])) : [null, null];
                $ins->execute([
                    $id, $s['office_id'] !== '' ? (int)$s['office_id'] : null,
                    $atVenue ? ($s['venue_name'] ?: null) : null, $atVenue ? ($s['city'] ?: null) : null,
                    $atVenue && $s['country_code'] !== '' ? strtolower($s['country_code']) : null,
                    $atVenue ? ($s['address'] ?: null) : null, $lat, $lon, $s['kind'] === 'online' ? 1 : 0,
                    $s['starts_on'] ?: null, $s['ends_on'] ?: null, $s['start_time'] ?: null, $s['end_time'] ?: null, $i,
                ]);
            }
            $db->commit();
            header('Location: event_edit.php?id=' . $id . '&saved=1');
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
    if (!$stops) $stops = [$blankStop];
}
if (isset($_GET['saved'])) $success = 'Etkinlik kaydedildi.';

$h = fn($v) => htmlspecialchars((string)$v);
$input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border';

function stopRow(int $i, array $s, array $offices, callable $h, string $input): string {
    ob_start(); ?>
    <div class="stop rounded-lg border border-gray-200 bg-gray-50 p-4" data-stop>
        <div class="flex items-center justify-between gap-3 mb-3">
            <p class="font-semibold text-gray-800 text-sm"><span data-stop-no><?= $i + 1 ?></span>. durak</p>
            <button type="button" class="text-sm text-red-600 hover:text-red-800" data-stop-remove>Kaldır</button>
        </div>
        <div class="flex flex-wrap gap-4 text-sm mb-3">
            <?php foreach (['office' => 'Ofiste', 'venue' => 'Ofis dışı mekânda', 'online' => 'Online'] as $k => $label): ?>
            <label class="inline-flex items-center gap-2"><input type="radio" name="stops[<?= $i ?>][kind]" value="<?= $k ?>" <?= $s['kind'] === $k ? 'checked' : '' ?> data-kind> <?= $label ?></label>
            <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div data-show="office venue online">
                <label class="block text-sm font-medium text-gray-700"><span data-show="office">Ofis *</span><span data-show="venue online">İletişim ofisi (telefon/WhatsApp buradan alınır)</span></label>
                <select name="stops[<?= $i ?>][office_id]" class="<?= $input ?>">
                    <option value="">Seçiniz…</option>
                    <?php $lastCountry = null; foreach ($offices as $o):
                        if ($o['country'] !== $lastCountry) { if ($lastCountry !== null) echo '</optgroup>'; echo '<optgroup label="' . $h($o['country']) . '">'; $lastCountry = $o['country']; } ?>
                        <option value="<?= $o['id'] ?>" <?= (string)$s['office_id'] === (string)$o['id'] ? 'selected' : '' ?>><?= $h($o['display_name']) ?></option>
                    <?php endforeach; if ($lastCountry !== null) echo '</optgroup>'; ?>
                </select>
            </div>
            <div data-show="venue"><label class="block text-sm font-medium text-gray-700">Mekân adı</label><input type="text" dir="auto" name="stops[<?= $i ?>][venue_name]" value="<?= $h($s['venue_name']) ?>" placeholder="Hilton Baku, Expo Center …" class="<?= $input ?>"></div>
            <div data-show="venue"><label class="block text-sm font-medium text-gray-700">Şehir</label><input type="text" dir="auto" name="stops[<?= $i ?>][city]" value="<?= $h($s['city']) ?>" class="<?= $input ?>"></div>
            <div data-show="venue"><label class="block text-sm font-medium text-gray-700">Ülke kodu (ofis seçilmediyse zorunlu)</label><input type="text" name="stops[<?= $i ?>][country_code]" value="<?= $h(strtoupper((string)$s['country_code'])) ?>" maxlength="2" placeholder="AZ, US, DE …" class="<?= $input ?> uppercase"></div>
            <div data-show="venue" class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Adres</label><input type="text" dir="auto" name="stops[<?= $i ?>][address]" value="<?= $h($s['address']) ?>" class="<?= $input ?>"></div>
            <div data-show="venue" class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Koordinat (isteğe bağlı, haritada göstermek için)</label><input type="text" name="stops[<?= $i ?>][coords]" value="<?= $h($s['coords']) ?>" placeholder="40.4093, 49.8671" class="<?= $input ?>"><p class="mt-1 text-xs text-gray-500">Google Maps'te yere sağ tıklayıp ilk satırdaki sayıları kopyalayın.</p></div>
            <div><label class="block text-sm font-medium text-gray-700">Başlangıç tarihi</label><input type="date" name="stops[<?= $i ?>][starts_on]" value="<?= $h($s['starts_on']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Bitiş tarihi (çok günlüyse)</label><input type="date" name="stops[<?= $i ?>][ends_on]" value="<?= $h($s['ends_on']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Başlangıç saati (yerel)</label><input type="time" name="stops[<?= $i ?>][start_time]" value="<?= $h($s['start_time']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Bitiş saati</label><input type="time" name="stops[<?= $i ?>][end_time]" value="<?= $h($s['end_time']) ?>" class="<?= $input ?>"></div>
        </div>
    </div>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Etkinlik Düzenle' : 'Yeni Etkinlik' ?> - Yönetim Paneli</title>
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
            <a href="settings.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ayarlar</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 gap-4">
            <a href="events.php" class="text-gray-500 hover:text-gray-700">← Geri</a>
            <h2 class="text-xl font-semibold text-gray-800"><?= $isEdit ? 'Etkinlik Düzenle' : 'Yeni Etkinlik' ?></h2>
            <?php if ($isEdit): ?><a href="<?= $h(eventUrl($event['slug'])) ?>" target="_blank" class="ml-auto text-sm text-blue-600 hover:underline">Sitede gör ↗</a><?php endif; ?>
        </header>

        <div class="p-4 md:p-8 flex-1 overflow-y-auto">
            <?php if ($error): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg max-w-3xl"><?= $h($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg max-w-3xl"><?= $h($success) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 max-w-3xl space-y-6">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Başlık * <span class="font-normal text-gray-500">(yerel dilde yazabilirsiniz, çevrilmez)</span></label>
                        <input type="text" name="title" dir="auto" required value="<?= $h($event['title']) ?>" class="<?= $input ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tip *</label>
                        <select name="tag_key" class="<?= $input ?>">
                            <?php foreach ($typeLabels as $k => $label): ?><option value="<?= $k ?>" <?= $event['tag_key'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adres (slug) <span class="font-normal text-gray-500">boş bırakılırsa otomatik</span></label>
                        <input type="text" name="slug" value="<?= $h($event['slug']) ?>" placeholder="dr-yasar-colak-baku-2026" class="<?= $input ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                        <textarea name="description" dir="auto" rows="4" class="<?= $input ?>"><?= $h($event['description']) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kapak görseli (isteğe bağlı)</label>
                        <input type="text" name="image_url" value="<?= $h($event['image_url']) ?>" class="<?= $input ?>">
                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700">
                        <p class="mt-1 text-xs text-gray-500">Boş bırakılırsa tipe göre hazır görünüm kullanılır.</p>
                        <?php if (!empty($event['image_url'])): ?><img src="<?= $h(admin_image_src($event['image_url'])) ?>" alt="" class="mt-2 h-20 rounded-md border border-gray-200 object-cover"><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kayıt / bilgi linki (isteğe bağlı)</label>
                        <input type="url" name="link_url" value="<?= $h($event['link_url']) ?>" placeholder="https://…" class="<?= $input ?>">
                        <p class="mt-1 text-xs text-gray-500">Boşsa ziyaretçi ofisi arar ya da WhatsApp'tan yazar.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-gray-900">Nerede ve ne zaman?</h3>
                    <p class="text-xs text-gray-500 mb-3">Bir etkinliğin birden fazla durağı olabilir (ör. 12 Kasım Bakü, 14 Kasım Gence).</p>
                    <div class="space-y-3" data-stops>
                        <?php foreach ($stops as $i => $s) echo stopRow($i, $s, $offices, $h, $input); ?>
                    </div>
                    <template data-stop-template><?= stopRow(999, $blankStop, $offices, $h, $input) ?></template>
                    <button type="button" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800" data-stop-add>+ Durak ekle</button>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" <?= $event['is_published'] ? 'checked' : '' ?>> Yayında</label>
                    <label class="inline-flex items-center gap-2 text-sm">Sıralama <input type="number" name="sort_order" value="<?= $h($event['sort_order']) ?>" class="w-20 rounded-md border-gray-300 border px-2 py-1"></label>
                </div>

                <div class="pt-5 border-t border-gray-200 flex justify-end gap-3">
                    <a href="events.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">İptal</a>
                    <button type="submit" class="py-2 px-4 rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">Kaydet</button>
                </div>
            </form>
        </div>
    </main>

<script>
(function () {
    const list = document.querySelector('[data-stops]');
    const tpl = document.querySelector('[data-stop-template]');
    function sync(stop) {
        const kind = (stop.querySelector('[data-kind]:checked') || {}).value || 'office';
        stop.querySelectorAll('[data-show]').forEach((el) => { el.hidden = !el.dataset.show.split(' ').includes(kind); });
    }
    function renumber() {
        list.querySelectorAll('[data-stop]').forEach((stop, i) => {
            stop.querySelector('[data-stop-no]').textContent = i + 1;
            stop.querySelectorAll('[name^="stops["]').forEach((el) => { el.name = el.name.replace(/^stops\[\d+\]/, 'stops[' + i + ']'); });
        });
    }
    list.querySelectorAll('[data-stop]').forEach(sync);
    list.addEventListener('change', (e) => { if (e.target.matches('[data-kind]')) sync(e.target.closest('[data-stop]')); });
    list.addEventListener('click', (e) => {
        if (!e.target.matches('[data-stop-remove]')) return;
        if (list.querySelectorAll('[data-stop]').length > 1) e.target.closest('[data-stop]').remove();
        renumber();
    });
    document.querySelector('[data-stop-add]').addEventListener('click', () => {
        const stops = list.querySelectorAll('[data-stop]');
        const node = tpl.content.firstElementChild.cloneNode(true);
        // A tour usually keeps the same contact office: copy it from the last stop
        const last = stops[stops.length - 1];
        if (last) node.querySelector('select').value = last.querySelector('select').value;
        list.appendChild(node);
        renumber();
        sync(node);
    });
})();
</script>
</body>
</html>
