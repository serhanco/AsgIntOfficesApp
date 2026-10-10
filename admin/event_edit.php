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
$typeLabels = eventTypeLabelsTr();
$v2 = eventsV2Ready(); // stage 1 columns and tables (patch_20261011_events_relations.sql)

// Countries that have an office (a country relation means all of its offices)
$countries = [];
foreach ($offices as $o) if ($o['country_code'] !== '') $countries[strtolower($o['country_code'])] = $o['country'];
asort($countries);

$event = ['title' => '', 'slug' => '', 'tag_key' => 'act_tag_doctor', 'audience' => 'b2c', 'visibility' => 'listed', 'featured_rank' => 0, 'relates_hq' => 0,
          'description' => '', 'image_url' => '', 'link_url' => '', 'file_url' => '', 'is_published' => 1, 'sort_order' => 0];
$blankStop = ['kind' => 'office', 'office_id' => '', 'venue_name' => '', 'city' => '', 'country_code' => '', 'address' => '', 'coords' => '',
              'starts_on' => '', 'ends_on' => '', 'start_time' => '', 'end_time' => ''];
$blankPerson = ['person_id' => '', 'role' => 'speaker', 'name' => '', 'title' => '', 'specialty' => '', 'organization' => '', 'photo_url' => '', 'is_acibadem' => '1'];
$blankSession = ['day' => '', 'start_time' => '', 'end_time' => '', 'kind' => 'session', 'title' => '', 'speakers' => ''];
$stops = [$blankStop];
$relOffices = $relCountries = [];
$eventPeople = $program = [];
$allPeople = $v2 ? $db->query("SELECT id, name, title, is_acibadem FROM people ORDER BY name ASC")->fetchAll() : [];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { header('Location: events.php'); exit; }
    $event = array_merge($event, $row);
    $stmt = $db->prepare("SELECT * FROM event_locations WHERE event_id = ? ORDER BY sort_order, id");
    $stmt->execute([$id]);
    $stops = array_map(function ($s) {
        $venue = trim($s['venue_name'] . $s['city'] . $s['address']) !== '';
        return [
            'kind' => $s['is_online'] ? 'online' : ($venue ? 'venue' : ($s['office_id'] ? 'office' : 'floating')),
            'office_id' => $s['office_id'], 'venue_name' => $s['venue_name'], 'city' => $s['city'], 'country_code' => $s['country_code'],
            'address' => $s['address'], 'coords' => $s['latitude'] !== null ? rtrim(rtrim($s['latitude'], '0'), '.') . ', ' . rtrim(rtrim($s['longitude'], '0'), '.') : '',
            'starts_on' => $s['starts_on'], 'ends_on' => $s['ends_on'],
            'start_time' => $s['start_time'] ? substr($s['start_time'], 0, 5) : '', 'end_time' => $s['end_time'] ? substr($s['end_time'], 0, 5) : '',
        ];
    }, $stmt->fetchAll()) ?: [$blankStop];
    if ($v2) {
        $stmt = $db->prepare("SELECT * FROM event_relations WHERE event_id = ?");
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $r) {
            if ($r['office_id'] !== null) $relOffices[] = (int)$r['office_id'];
            elseif ($r['country_code'] !== null) $relCountries[] = strtolower($r['country_code']);
        }
        $stmt = $db->prepare("SELECT ep.role, ep.person_id FROM event_people ep WHERE ep.event_id = ? ORDER BY ep.sort_order, ep.id");
        $stmt->execute([$id]);
        $eventPeople = array_map(fn($r) => array_merge($blankPerson, ['person_id' => (string)$r['person_id'], 'role' => $r['role']]), $stmt->fetchAll());
        $stmt = $db->prepare("SELECT * FROM event_sessions WHERE event_id = ? ORDER BY sort_order, id");
        $stmt->execute([$id]);
        $program = array_map(fn($r) => [
            'day' => $r['day'], 'start_time' => $r['start_time'] ? substr($r['start_time'], 0, 5) : '', 'end_time' => $r['end_time'] ? substr($r['end_time'], 0, 5) : '',
            'kind' => $r['kind'], 'title' => $r['title'], 'speakers' => (string)$r['speakers'],
        ], $stmt->fetchAll());
    }
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
    if ($v2) {
        $event['audience'] = in_array($_POST['audience'] ?? '', EVENT_AUDIENCES, true) ? $_POST['audience'] : 'b2c';
        $event['visibility'] = in_array($_POST['visibility'] ?? '', EVENT_VISIBILITY, true) ? $_POST['visibility'] : 'listed';
        $event['featured_rank'] = max(0, min(3, (int)($_POST['featured_rank'] ?? 0)));
        $event['relates_hq'] = isset($_POST['relates_hq']) ? 1 : 0;
        $validOffice = array_map('intval', array_column($offices, 'id'));
        $relOffices = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['rel_offices'] ?? [])), fn($i) => in_array($i, $validOffice, true))));
        $relCountries = array_values(array_unique(array_filter(array_map('strtolower', array_map('strval', (array)($_POST['rel_countries'] ?? []))), fn($c) => isset($countries[$c]))));
        if (isset($_POST['remove_file'])) $event['file_url'] = '';
    }
    try {
        $uploaded = handle_image_upload('image_file', 'events', 1600);
        if ($uploaded !== null) $event['image_url'] = $uploaded;
        if ($v2) {
            $pdf = handle_pdf_upload('file_pdf', 'files');
            if ($pdf !== null) $event['file_url'] = $pdf;
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }

    $stops = [];
    foreach ((array)($_POST['stops'] ?? []) as $s) {
        $stop = array_merge($blankStop, array_map(fn($v) => is_string($v) ? trim($v) : '', (array)$s));
        if (!in_array($stop['kind'], ['office', 'venue', 'online', 'floating'], true)) $stop['kind'] = 'office';
        $empty = $stop['office_id'] === '' && $stop['venue_name'] === '' && $stop['city'] === '' && $stop['address'] === '' && $stop['starts_on'] === '' && !in_array($stop['kind'], ['online', 'floating'], true);
        if (!$empty) $stops[] = $stop;
    }

    // People of this event: an existing person, or a new one typed in the row
    $eventPeople = [];
    if ($v2) {
        foreach ((array)($_POST['people'] ?? []) as $r) {
            $r = array_merge($blankPerson, array_map(fn($v) => is_string($v) ? trim($v) : '', (array)$r));
            if (!in_array($r['role'], EVENT_ROLES, true)) $r['role'] = 'speaker';
            if ($r['person_id'] === '' && $r['name'] === '') continue;
            $eventPeople[] = $r;
        }
        $program = [];
        foreach ((array)($_POST['program'] ?? []) as $r) {
            $r = array_merge($blankSession, array_map(fn($v) => is_string($v) ? trim($v) : '', (array)$r));
            if ($r['title'] === '') continue;
            if (!in_array($r['kind'], ['session', 'heading'], true)) $r['kind'] = 'session';
            $program[] = $r;
        }
    }

    if (!$error && $event['title'] === '') $error = 'Başlık zorunludur.';
    if (!$error && !$stops) $error = 'En az bir lokasyon ekleyin (ofis, ofis dışı mekân, online ya da konumdan bağımsız tarih).';
    if (!$error && $event['link_url'] !== '' && !preg_match('#^https?://#i', $event['link_url'])) $error = 'Kayıt/bilgi linki http:// veya https:// ile başlamalı.';
    foreach ($stops as $i => $s) {
        if ($error) break;
        $n = $i + 1;
        if ($s['kind'] === 'office' && $s['office_id'] === '') $error = "$n. lokasyon: ofis seçin.";
        elseif ($s['kind'] === 'venue' && $s['venue_name'] === '' && $s['city'] === '') $error = "$n. lokasyon: mekân adı ya da şehir girin.";
        elseif ($s['kind'] === 'venue' && $s['office_id'] === '' && !preg_match('/^[a-z]{2}$/i', $s['country_code'])) $error = "$n. lokasyon: ofis seçmediyseniz ülke kodunu girin (ör. US).";
        elseif ($s['coords'] !== '' && !preg_match('/^\s*-?\d{1,2}(\.\d+)?\s*,\s*-?\d{1,3}(\.\d+)?\s*$/', $s['coords'])) $error = "$n. lokasyon: koordinat \"40.4093, 49.8671\" biçiminde olmalı.";
        elseif ($s['ends_on'] !== '' && $s['starts_on'] !== '' && $s['ends_on'] < $s['starts_on']) $error = "$n. lokasyon: bitiş tarihi başlangıçtan önce olamaz.";
    }
    if ($v2) {
        foreach ($eventPeople as $i => $r) {
            if ($error) break;
            if ($r['person_id'] === '' && $r['name'] === '') $error = ($i + 1) . '. katılımcı: kişi seçin ya da yeni kişinin adını yazın.';
        }
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
            $cols = ['slug' => $event['slug'], 'tag_key' => $event['tag_key'], 'title' => $event['title'], 'description' => $event['description'],
                     'image_url' => $event['image_url'] ?: null, 'link_url' => $event['link_url'] ?: null, 'is_published' => $event['is_published'], 'sort_order' => $event['sort_order']];
            if ($v2) {
                $cols += ['audience' => $event['audience'], 'visibility' => $event['visibility'], 'featured_rank' => $event['featured_rank'],
                          'relates_hq' => $event['relates_hq'], 'file_url' => $event['file_url'] ?: null];
            }
            if ($isEdit) {
                $db->prepare("UPDATE events SET " . implode(', ', array_map(fn($c) => "$c = ?", array_keys($cols))) . " WHERE id = ?")
                   ->execute(array_merge(array_values($cols), [$id]));
                $db->prepare("DELETE FROM event_locations WHERE event_id = ?")->execute([$id]);
            } else {
                $db->prepare("INSERT INTO events (" . implode(', ', array_keys($cols)) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")")
                   ->execute(array_values($cols));
                $id = (int)$db->lastInsertId();
            }
            $ins = $db->prepare("INSERT INTO event_locations (event_id, office_id, venue_name, city, country_code, address, latitude, longitude, is_online,
                                 starts_on, ends_on, start_time, end_time, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($stops as $i => $s) {
                $atVenue = $s['kind'] === 'venue';
                [$lat, $lon] = ($atVenue && $s['coords'] !== '') ? array_map('floatval', explode(',', $s['coords'])) : [null, null];
                $ins->execute([
                    $id, ($s['kind'] === 'floating' || $s['office_id'] === '') ? null : (int)$s['office_id'],
                    $atVenue ? ($s['venue_name'] ?: null) : null, $atVenue ? ($s['city'] ?: null) : null,
                    $atVenue && $s['country_code'] !== '' ? strtolower($s['country_code']) : null,
                    $atVenue ? ($s['address'] ?: null) : null, $lat, $lon, $s['kind'] === 'online' ? 1 : 0,
                    $s['starts_on'] ?: null, $s['ends_on'] ?: null, $s['start_time'] ?: null, $s['end_time'] ?: null, $i,
                ]);
            }
            if ($v2) {
                // Relations
                $db->prepare("DELETE FROM event_relations WHERE event_id = ?")->execute([$id]);
                $rel = $db->prepare("INSERT INTO event_relations (event_id, office_id, country_code) VALUES (?, ?, ?)");
                foreach ($relOffices as $oid) $rel->execute([$id, $oid, null]);
                foreach ($relCountries as $cc) $rel->execute([$id, null, $cc]);
                // People
                $db->prepare("DELETE FROM event_people WHERE event_id = ?")->execute([$id]);
                $newPerson = $db->prepare("INSERT INTO people (name, title, specialty, organization, photo_url, is_acibadem) VALUES (?, ?, ?, ?, ?, ?)");
                $link = $db->prepare("INSERT INTO event_people (event_id, person_id, role, sort_order) VALUES (?, ?, ?, ?)");
                $seen = [];
                foreach ($eventPeople as $i => $r) {
                    if ($r['person_id'] !== '') {
                        $pid = (int)$r['person_id'];
                    } else {
                        $newPerson->execute([$r['name'], $r['title'] ?: null, $r['specialty'] ?: null, $r['organization'] ?: null,
                                             preg_match('#^(https?://|assets/)#i', $r['photo_url']) ? $r['photo_url'] : null, $r['is_acibadem'] === '0' ? 0 : 1]);
                        $pid = (int)$db->lastInsertId();
                    }
                    if (isset($seen[$pid])) continue; // the same person twice in one event
                    $seen[$pid] = true;
                    $link->execute([$id, $pid, $r['role'], $i]);
                }
                // Program
                $db->prepare("DELETE FROM event_sessions WHERE event_id = ?")->execute([$id]);
                $sess = $db->prepare("INSERT INTO event_sessions (event_id, day, start_time, end_time, kind, title, speakers, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($program as $i => $r) {
                    $sess->execute([$id, $r['day'] ?: null, $r['start_time'] ?: null, $r['end_time'] ?: null, $r['kind'], $r['title'], $r['speakers'] ?: null, $i]);
                }
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
    // Re-selected people keep the row values; a person that does not exist anymore is dropped
    if ($v2) {
        $allPeople = $db->query("SELECT id, name, title, is_acibadem FROM people ORDER BY name ASC")->fetchAll();
    }
}
if (isset($_GET['saved'])) $success = 'Etkinlik kaydedildi.';

$h = fn($v) => htmlspecialchars((string)$v);
$input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border';

function stopRow(int $i, array $s, array $offices, callable $h, string $input): string {
    ob_start(); ?>
    <div class="stop rounded-lg border border-gray-200 bg-gray-50 p-4" data-stop>
        <div class="flex items-center justify-between gap-3 mb-3">
            <p class="font-semibold text-gray-800 text-sm"><span data-stop-no><?= $i + 1 ?></span>. lokasyon</p>
            <button type="button" class="text-sm text-red-600 hover:text-red-800" data-stop-remove>Kaldır</button>
        </div>
        <div class="flex flex-wrap gap-4 text-sm mb-3">
            <?php foreach (['office' => 'Ofiste', 'venue' => 'Ofis dışı mekânda', 'online' => 'Online', 'floating' => 'Konumdan bağımsız (yalnız tarih)'] as $k => $label): ?>
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

function personRow(int $i, array $r, array $allPeople, callable $h, string $input): string {
    ob_start(); ?>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4" data-person data-row>
        <div class="grid grid-cols-1 md:grid-cols-[1fr_12rem_auto] gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Kişi</label>
                <select name="people[<?= $i ?>][person_id]" class="<?= $input ?>" data-person-select>
                    <option value="">+ Yeni kişi ekle…</option>
                    <?php foreach ($allPeople as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (string)$r['person_id'] === (string)$p['id'] ? 'selected' : '' ?>><?= $h($p['name']) ?><?= $p['title'] ? ' · ' . $h($p['title']) : '' ?><?= $p['is_acibadem'] ? '' : ' (Acıbadem dışı)' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Bu etkinlikteki rolü</label>
                <select name="people[<?= $i ?>][role]" class="<?= $input ?>">
                    <?php foreach (EVENT_ADMIN_ROLES as $k => $label): ?><option value="<?= $k ?>" <?= $r['role'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="text-sm text-red-600 hover:text-red-800 pb-2" data-row-remove>Kaldır</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3" data-new-person>
            <div><label class="block text-sm font-medium text-gray-700">Ad soyad *</label><input type="text" dir="auto" name="people[<?= $i ?>][name]" value="<?= $h($r['name']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Unvan</label><input type="text" dir="auto" name="people[<?= $i ?>][title]" value="<?= $h($r['title']) ?>" placeholder="Prof. Dr., Genel Müdür …" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Branş / görev</label><input type="text" dir="auto" name="people[<?= $i ?>][specialty]" value="<?= $h($r['specialty']) ?>" placeholder="Ortopedi, Kardiyoloji …" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Kurum</label><input type="text" dir="auto" name="people[<?= $i ?>][organization]" value="<?= $h($r['organization']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700">Fotoğraf linki (isteğe bağlı)</label><input type="text" name="people[<?= $i ?>][photo_url]" value="<?= $h($r['photo_url']) ?>" placeholder="https://… ya da assets/images/…" class="<?= $input ?>"><p class="mt-1 text-xs text-gray-500">Fotoğraf yüklemek için Kişiler sayfasını kullanın.</p></div>
            <div class="flex items-end pb-2"><label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="people[<?= $i ?>][is_acibadem]" value="1" <?= $r['is_acibadem'] !== '0' ? 'checked' : '' ?> onchange="this.nextElementSibling.disabled=this.checked"><input type="hidden" name="people[<?= $i ?>][is_acibadem]" value="0" <?= $r['is_acibadem'] !== '0' ? 'disabled' : '' ?>> Acıbadem'de çalışıyor</label></div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function sessionRow(int $i, array $r, callable $h, string $input): string {
    ob_start(); ?>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3" data-session data-row>
        <div class="grid grid-cols-2 md:grid-cols-[9rem_6rem_6rem_9rem_1fr_auto] gap-2 items-end">
            <div><label class="block text-xs font-medium text-gray-600">Gün</label><input type="date" name="program[<?= $i ?>][day]" value="<?= $h($r['day']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-xs font-medium text-gray-600">Başlangıç</label><input type="time" name="program[<?= $i ?>][start_time]" value="<?= $h($r['start_time']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-xs font-medium text-gray-600">Bitiş</label><input type="time" name="program[<?= $i ?>][end_time]" value="<?= $h($r['end_time']) ?>" class="<?= $input ?>"></div>
            <div><label class="block text-xs font-medium text-gray-600">Tür</label>
                <select name="program[<?= $i ?>][kind]" class="<?= $input ?>"><option value="session" <?= $r['kind'] === 'session' ? 'selected' : '' ?>>Oturum / satır</option><option value="heading" <?= $r['kind'] === 'heading' ? 'selected' : '' ?>>Bölüm başlığı</option></select></div>
            <div class="col-span-2 md:col-span-1"><label class="block text-xs font-medium text-gray-600">Başlık *</label><input type="text" dir="auto" name="program[<?= $i ?>][title]" value="<?= $h($r['title']) ?>" class="<?= $input ?>"></div>
            <button type="button" class="text-sm text-red-600 hover:text-red-800 pb-2" data-row-remove>Kaldır</button>
        </div>
        <div class="mt-2"><input type="text" dir="auto" name="program[<?= $i ?>][speakers]" value="<?= $h($r['speakers']) ?>" placeholder="Konuşmacı / moderatör (isteğe bağlı)" class="<?= $input ?>"></div>
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
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ekipler</a>
            <a href="events.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Etkinlikler</a>
            <a href="people.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Kişiler</a>
            <a href="event_requests.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Talepler</a>
            <a href="settings.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ayarlar</a>
            <a href="admins.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Yöneticiler</a>
            <a href="../apply_update.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Güncellemeler</a>
            <a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Siteyi gör ↗</a>
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
                    <?php if ($v2): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kitle *</label>
                        <select name="audience" class="<?= $input ?>">
                            <?php foreach (EVENT_ADMIN_AUDIENCE as $k => $label): ?><option value="<?= $k ?>" <?= $event['audience'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Hastalara mı, kurumlara mı yönelik? Sitede etiket olarak görünür.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Görünürlük *</label>
                        <select name="visibility" class="<?= $input ?>">
                            <?php foreach (EVENT_ADMIN_VISIBILITY as $k => $label): ?><option value="<?= $k ?>" <?= $event['visibility'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
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
                    <?php if ($v2): ?>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Duyuru PDF'i (isteğe bağlı)</label>
                        <?php if (!empty($event['file_url'])): ?>
                        <p class="mt-1 text-sm"><a href="<?= $h(admin_image_src($event['file_url'])) ?>" target="_blank" class="text-blue-600 hover:underline">Yüklü PDF'i aç ↗</a>
                            <label class="ml-4 inline-flex items-center gap-2 text-gray-600"><input type="checkbox" name="remove_file" value="1"> Kaldır</label></p>
                        <?php endif; ?>
                        <input type="file" name="file_pdf" accept="application/pdf" class="mt-2 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700">
                        <p class="mt-1 text-xs text-gray-500">Etkinlik sayfasında "Duyuruyu indir" düğmesi olur. En fazla 15 MB.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-gray-900">Nerede ve ne zaman?</h3>
                    <p class="text-xs text-gray-500 mb-3">Bir etkinliğin birden fazla lokasyonu olabilir (ör. 12 Kasım Bakü, 14 Kasım Gence). Bir veya birkaç günlük olabilir: başlangıç ve bitiş tarihini girin. Yeri olmayan bir etkinlik için "Konumdan bağımsız" seçin.</p>
                    <div class="space-y-3" data-stops>
                        <?php foreach ($stops as $i => $s) echo stopRow($i, $s, $offices, $h, $input); ?>
                    </div>
                    <template data-stop-template><?= stopRow(999, $blankStop, $offices, $h, $input) ?></template>
                    <button type="button" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800" data-stop-add>+ Lokasyon ekle</button>
                </div>

                <?php if ($v2): ?>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Kimlerle ilişkili?</h3>
                    <p class="text-xs text-gray-500 mb-3">Etkinlik, buradan seçtikleriniz ve yukarıdaki lokasyonlardaki ofislerle ilişkili sayılır: bu ofislerin sayfalarında görünür. Bir <b>ülke</b> seçerseniz o ülkedeki tüm ofislerle ilişkili olur. Hiçbir şey seçmezseniz ve lokasyon da yoksa etkinlik konumdan bağımsızdır, tüm ziyaretçilere gösterilir.</p>
                    <label class="inline-flex items-center gap-2 text-sm mb-3"><input type="checkbox" name="relates_hq" value="1" <?= $event['relates_hq'] ? 'checked' : '' ?>> Genel Müdürlük ile ilişkili <span class="text-gray-500">(iletişim merkeze gider, sitede "Merkezden" etiketi çıkar)</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ülkeler <span class="font-normal text-gray-500">(Ctrl/⌘ ile birden fazla)</span></label>
                            <select name="rel_countries[]" multiple size="8" class="<?= $input ?>">
                                <?php foreach ($countries as $cc => $name): ?><option value="<?= $h($cc) ?>" <?= in_array($cc, $relCountries, true) ? 'selected' : '' ?>><?= $h($name) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Belirli ofisler</label>
                            <select name="rel_offices[]" multiple size="8" class="<?= $input ?>">
                                <?php $lastCountry = null; foreach ($offices as $o):
                                    if ($o['country'] !== $lastCountry) { if ($lastCountry !== null) echo '</optgroup>'; echo '<optgroup label="' . $h($o['country']) . '">'; $lastCountry = $o['country']; } ?>
                                    <option value="<?= $o['id'] ?>" <?= in_array((int)$o['id'], $relOffices, true) ? 'selected' : '' ?>><?= $h($o['display_name']) ?></option>
                                <?php endforeach; if ($lastCountry !== null) echo '</optgroup>'; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-gray-900">Katılımcılar</h3>
                    <p class="text-xs text-gray-500 mb-3">Hekim, heyet ya da yönetici; hiç katılımcı da olmayabilir. Kişiler ortak bir listede tutulur: bir kez girin, sonraki etkinliklerde listeden seçin. Acıbadem dışı konuşmacılar da eklenebilir. Kişileri düzenlemek için <a href="people.php" class="text-blue-600 hover:underline">Kişiler</a> sayfasına bakın.</p>
                    <div class="space-y-3" data-list="people">
                        <?php foreach ($eventPeople as $i => $r) echo personRow($i, $r, $allPeople, $h, $input); ?>
                    </div>
                    <template data-template="people"><?= personRow(999, $blankPerson, $allPeople, $h, $input) ?></template>
                    <button type="button" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800" data-add="people">+ Katılımcı ekle</button>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-gray-900">Program <span class="font-normal text-gray-500">(isteğe bağlı)</span></h3>
                    <p class="text-xs text-gray-500 mb-3">Çok günlü ya da oturumlu etkinlikler için gün gün satırlar. "Bölüm başlığı" satırı, altındaki oturumları gruplar (ör. Ameliyat Öncesi). Kısa etkinliklerde boş bırakın.</p>
                    <div class="space-y-2" data-list="program">
                        <?php foreach ($program as $i => $r) echo sessionRow($i, $r, $h, $input); ?>
                    </div>
                    <template data-template="program"><?= sessionRow(999, $blankSession, $h, $input) ?></template>
                    <button type="button" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800" data-add="program">+ Satır ekle</button>
                </div>
                <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                    Kitle, görünürlük, ilişkiler, katılımcılar, program ve PDF alanları için <code>patch_20261011_events_relations.sql</code> yaması gerekiyor.
                    <a href="../apply_update.php" class="underline font-medium">Güncellemeler</a> ekranından uygulayabilirsiniz; o zamana kadar etkinlikler eskisi gibi çalışır.
                </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" <?= $event['is_published'] ? 'checked' : '' ?>> Yayında</label>
                    <?php if ($v2): ?>
                    <label class="inline-flex items-center gap-2 text-sm">Öne çıkarma
                        <select name="featured_rank" class="rounded-md border-gray-300 border px-2 py-1">
                            <option value="0" <?= !$event['featured_rank'] ? 'selected' : '' ?>>Yok (tarihe göre)</option>
                            <?php foreach ([1 => '1. sıra (en üstte)', 2 => '2. sıra', 3 => '3. sıra'] as $k => $label): ?><option value="<?= $k ?>" <?= (int)$event['featured_rank'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <?php endif; ?>
                    <label class="inline-flex items-center gap-2 text-sm" title="Aynı derecede ve aynı tarihteki etkinlikleri ayırır">Sıra no <input type="number" name="sort_order" value="<?= $h($event['sort_order']) ?>" class="w-20 rounded-md border-gray-300 border px-2 py-1"></label>
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
    // People and program rows: add / remove, keep the field indexes in order
    document.querySelectorAll('[data-list]').forEach((box) => {
        const name = box.dataset.list;
        const tplEl = document.querySelector('[data-template="' + name + '"]');
        const addBtn = document.querySelector('[data-add="' + name + '"]');
        const renum = () => box.querySelectorAll('[data-row]').forEach((row, i) => {
            row.querySelectorAll('[name^="' + name + '["]').forEach((el) => { el.name = el.name.replace(new RegExp('^' + name + '\\[\\d+\\]'), name + '[' + i + ']'); });
        });
        const syncPerson = (row) => {
            const sel = row.querySelector('[data-person-select]');
            if (sel) row.querySelector('[data-new-person]').hidden = sel.value !== '';
        };
        box.querySelectorAll('[data-row]').forEach(syncPerson);
        box.addEventListener('change', (e) => { if (e.target.matches('[data-person-select]')) syncPerson(e.target.closest('[data-row]')); });
        box.addEventListener('click', (e) => {
            if (!e.target.matches('[data-row-remove]')) return;
            e.target.closest('[data-row]').remove();
            renum();
        });
        addBtn.addEventListener('click', () => {
            const node = tplEl.content.firstElementChild.cloneNode(true);
            box.appendChild(node);
            renum();
            syncPerson(node);
        });
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
