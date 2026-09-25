<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_login();

$db = getDb();
$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;

// Fetch offices for the dropdown
$offices = $db->query("SELECT id, display_name FROM offices ORDER BY display_name ASC")->fetchAll();

// Default values
$activity = [
    'office_id' => '', 'tag_key' => '', 'title' => '', 'description' => '', 
    'activity_date' => date('Y-m-d'), 'sort_order' => 0
];

if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM office_activities WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $activity = $existing;
    } else {
        header('Location: activities.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity['office_id'] = (int)($_POST['office_id'] ?? 0);
    $activity['tag_key'] = trim($_POST['tag_key'] ?? '');
    $activity['title'] = trim($_POST['title'] ?? '');
    $activity['description'] = trim($_POST['description'] ?? '');
    $activity['activity_date'] = empty($_POST['activity_date']) ? null : $_POST['activity_date'];
    $activity['sort_order'] = (int)($_POST['sort_order'] ?? 0);

    if (empty($activity['title']) || empty($activity['office_id']) || empty($activity['tag_key'])) {
        $error = "Başlık, Ofis ve Etiket (Tag) Anahtarı zorunludur.";
    } else {
        try {
            if ($is_edit) {
                $stmt = $db->prepare("UPDATE office_activities SET 
                    office_id=?, tag_key=?, title=?, description=?, activity_date=?, sort_order=? 
                    WHERE id=?");
                $stmt->execute([
                    $activity['office_id'], $activity['tag_key'], $activity['title'], 
                    $activity['description'], $activity['activity_date'], $activity['sort_order'], $id
                ]);
                $success = "Etkinlik başarıyla güncellendi.";
            } else {
                $stmt = $db->prepare("INSERT INTO office_activities (
                    office_id, tag_key, title, description, activity_date, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $activity['office_id'], $activity['tag_key'], $activity['title'], 
                    $activity['description'], $activity['activity_date'], $activity['sort_order']
                ]);
                $id = $db->lastInsertId();
                $is_edit = true;
                $success = "Etkinlik başarıyla eklendi.";
            }
        } catch (PDOException $e) {
            $error = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Etkinlik Düzenle' : 'Yeni Etkinlik Ekle' ?> - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ekipler</a>
            <a href="activities.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Etkinlikler</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <header class="h-16 bg-white shadow-sm flex items-center px-8">
            <a href="activities.php" class="text-gray-500 hover:text-gray-700 mr-4">← Geri</a>
            <h2 class="text-xl font-semibold text-gray-800"><?= $is_edit ? 'Etkinlik Düzenle' : 'Yeni Etkinlik Ekle' ?></h2>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-2xl space-y-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bağlı Olduğu Ofis *</label>
                    <select name="office_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                        <option value="">Seçiniz...</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?= $office['id'] ?>" <?= $activity['office_id'] == $office['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($office['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tarih</label>
                        <input type="date" name="activity_date" value="<?= htmlspecialchars($activity['activity_date']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Etiket/Tip Anahtarı (Örn: act_tag_doctor) *</label>
                        <input type="text" name="tag_key" required value="<?= htmlspecialchars($activity['tag_key']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Etkinlik Başlığı *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($activity['title']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                    <textarea name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"><?= htmlspecialchars($activity['description']) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Sıralama (0 ilk çıkar)</label>
                    <input type="number" name="sort_order" value="<?= htmlspecialchars($activity['sort_order']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border w-32">
                </div>

                <div class="pt-5 border-t border-gray-200 flex justify-end">
                    <a href="activities.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3">İptal</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">Kaydet</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
