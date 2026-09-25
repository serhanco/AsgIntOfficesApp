<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_login();

$db = getDb();

// Handle Delete Action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM office_teams WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: teams.php?msg=deleted');
    exit;
}

// Fetch all teams with their office names
$stmt = $db->query("
    SELECT t.*, o.display_name as office_name 
    FROM office_teams t
    JOIN offices o ON t.office_id = o.id
    ORDER BY o.display_name ASC, t.sort_order ASC
");
$teams = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekip Yönetimi - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Ekipler</a>
            <a href="activities.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Etkinlikler</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h2 class="text-xl font-semibold text-gray-800">Ekip Üyeleri</h2>
            <a href="team_edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Yeni Üye Ekle
            </a>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            <?php if ($msg === 'deleted'): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Üye başarıyla silindi.</div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Üye / Rol</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bağlı Olduğu Ofis</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($teams as $member): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $statusColor = 'bg-green-100 text-green-800';
                                    if ($member['status'] === 'offline') $statusColor = 'bg-gray-100 text-gray-800';
                                    if ($member['status'] === 'away') $statusColor = 'bg-yellow-100 text-yellow-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColor ?>">
                                    <?= htmlspecialchars(ucfirst($member['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($member['name']) ?></div>
                                <div class="text-sm text-gray-500 font-mono"><?= htmlspecialchars($member['role_key']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?= htmlspecialchars($member['office_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="team_edit.php?id=<?= $member['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Düzenle</a>
                                <form method="POST" action="teams.php" class="inline" onsubmit="return confirm('Bu üyeyi silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $member['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($teams)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Henüz hiç takım üyesi eklenmemiş.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
