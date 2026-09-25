<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_login();

$db = getDb();

// Handle Delete Action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM offices WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: offices.php?msg=deleted');
    exit;
}

// Handle Toggle Active
if (isset($_POST['action']) && $_POST['action'] === 'toggle_active' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE offices SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: offices.php?msg=updated');
    exit;
}

// Fetch all offices
$stmt = $db->query("SELECT * FROM offices ORDER BY sort_order ASC, country ASC, name ASC");
$offices = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ofis Yönetimi - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">
            Acıbadem Admin
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Ekipler</a>
            <a href="activities.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Etkinlikler</a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <div class="text-sm text-gray-400 mb-2">Giriş yapan: <?= htmlspecialchars($_SESSION['admin_username']) ?></div>
            <a href="index.php?action=logout" class="block w-full text-center px-4 py-2 border border-gray-600 rounded-md text-sm text-gray-300 hover:bg-gray-800 hover:text-white">Çıkış Yap</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <!-- Top header -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h2 class="text-xl font-semibold text-gray-800">Ofis Yönetimi</h2>
            <a href="office_edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Yeni Ofis Ekle
            </a>
        </header>

        <!-- Content Area -->
        <div class="p-8 flex-1 overflow-y-auto">
            
            <?php if ($msg === 'deleted'): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Ofis başarıyla silindi.</div>
            <?php elseif ($msg === 'updated'): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Güncelleme başarılı.</div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ülke / Şehir</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İletişim</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($offices as $office): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" action="offices.php" class="inline">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?= $office['id'] ?>">
                                    <button type="submit" class="inline-flex items-center">
                                        <?php if ($office['is_active']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Pasif</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($office['display_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($office['country']) ?> (<?= htmlspecialchars($office['country_code']) ?>)</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($office['email']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($office['phone']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="office_edit.php?id=<?= $office['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Düzenle</a>
                                
                                <form method="POST" action="offices.php" class="inline" onsubmit="return confirm('Bu ofisi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $office['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($offices)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Henüz hiç ofis eklenmemiş.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
