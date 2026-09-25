<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_login();

$db = getDb();

// Handle Delete Action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM office_activities WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: activities.php?msg=deleted');
    exit;
}

// Fetch all activities with their office names
$stmt = $db->query("
    SELECT a.*, o.display_name as office_name 
    FROM office_activities a
    JOIN offices o ON a.office_id = o.id
    ORDER BY a.activity_date DESC, o.display_name ASC
");
$activities = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlik Yönetimi - Yönetim Paneli</title>
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
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h2 class="text-xl font-semibold text-gray-800">Etkinlikler</h2>
            <a href="activity_edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Yeni Etkinlik Ekle
            </a>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            <?php if ($msg === 'deleted'): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Etkinlik başarıyla silindi.</div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etkinlik Başlığı</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bağlı Olduğu Ofis</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $activity['activity_date'] ? date('d.m.Y', strtotime($activity['activity_date'])) : '-' ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($activity['title']) ?></div>
                                <div class="text-xs text-blue-600 font-mono mt-1"><?= htmlspecialchars($activity['tag_key']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?= htmlspecialchars($activity['office_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="activity_edit.php?id=<?= $activity['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Düzenle</a>
                                <form method="POST" action="activities.php" class="inline" onsubmit="return confirm('Bu etkinliği silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $activity['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Henüz hiç etkinlik eklenmemiş.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
