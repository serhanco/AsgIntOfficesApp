<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_login();

$db = getDb();
$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;

// Default values for new office
$office = [
    'name' => '', 'display_name' => '', 'slug' => '', 'country' => '', 'country_code' => '',
    'address' => '', 'phone' => '', 'email' => '', 'latitude' => '0.0000000', 'longitude' => '0.0000000',
    'image_url' => '', 'is_active' => 1, 'sort_order' => 0
];

if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM offices WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $office = $existing;
    } else {
        header('Location: offices.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect input
    $office['name'] = trim($_POST['name'] ?? '');
    $office['display_name'] = trim($_POST['display_name'] ?? '');
    $office['slug'] = trim($_POST['slug'] ?? '');
    $office['country'] = trim($_POST['country'] ?? '');
    $office['country_code'] = trim($_POST['country_code'] ?? '');
    $office['address'] = trim($_POST['address'] ?? '');
    $office['phone'] = trim($_POST['phone'] ?? '');
    $office['email'] = trim($_POST['email'] ?? '');
    $office['latitude'] = (float)($_POST['latitude'] ?? 0);
    $office['longitude'] = (float)($_POST['longitude'] ?? 0);
    $office['image_url'] = trim($_POST['image_url'] ?? '');
    $office['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $office['sort_order'] = (int)($_POST['sort_order'] ?? 0);

    if (empty($office['name']) || empty($office['display_name']) || empty($office['slug']) || empty($office['country'])) {
        $error = "Lütfen zorunlu alanları doldurun (İsim, Görünen İsim, Slug, Ülke).";
    } else {
        try {
            if ($is_edit) {
                $stmt = $db->prepare("UPDATE offices SET 
                    name=?, display_name=?, slug=?, country=?, country_code=?, address=?, 
                    phone=?, email=?, latitude=?, longitude=?, image_url=?, is_active=?, sort_order=? 
                    WHERE id=?");
                $stmt->execute([
                    $office['name'], $office['display_name'], $office['slug'], $office['country'],
                    $office['country_code'], $office['address'], $office['phone'], $office['email'],
                    $office['latitude'], $office['longitude'], $office['image_url'], $office['is_active'],
                    $office['sort_order'], $id
                ]);
                $success = "Ofis başarıyla güncellendi.";
            } else {
                $stmt = $db->prepare("INSERT INTO offices (
                    name, display_name, slug, country, country_code, address, 
                    phone, email, latitude, longitude, image_url, is_active, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $office['name'], $office['display_name'], $office['slug'], $office['country'],
                    $office['country_code'], $office['address'], $office['phone'], $office['email'],
                    $office['latitude'], $office['longitude'], $office['image_url'], $office['is_active'],
                    $office['sort_order']
                ]);
                $id = $db->lastInsertId();
                $is_edit = true;
                $success = "Ofis başarıyla eklendi.";
            }
        } catch (PDOException $e) {
            // Usually duplicate slug error
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
    <title><?= $is_edit ? 'Ofis Düzenle' : 'Yeni Ofis Ekle' ?> - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Ekipler</a>
            <a href="activities.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800">Etkinlikler</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <header class="h-16 bg-white shadow-sm flex items-center px-8">
            <a href="offices.php" class="text-gray-500 hover:text-gray-700 mr-4">← Geri</a>
            <h2 class="text-xl font-semibold text-gray-800"><?= $is_edit ? 'Ofis Düzenle' : 'Yeni Ofis Ekle' ?></h2>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Temel Bilgiler -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Temel Bilgiler</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Internal Name (İç İsim) *</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($office['name']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Display Name (Görünen İsim) *</label>
                                <input type="text" name="display_name" required value="<?= htmlspecialchars($office['display_name']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">URL Slug *</label>
                                <input type="text" name="slug" required value="<?= htmlspecialchars($office['slug']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ülke *</label>
                                    <input type="text" name="country" required value="<?= htmlspecialchars($office['country']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ülke Kodu (Örn: GB)</label>
                                    <input type="text" name="country_code" value="<?= htmlspecialchars($office['country_code']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                                </div>
                            </div>
                            
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" <?= $office['is_active'] ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Aktif (Sitede Gösterilsin)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- İletişim ve Konum -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">İletişim & Konum</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Telefon</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($office['phone']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">E-posta</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($office['email']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Adres</label>
                                <textarea name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"><?= htmlspecialchars($office['address']) ?></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Enlem (Latitude)</label>
                                    <input type="text" name="latitude" value="<?= htmlspecialchars($office['latitude']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Boylam (Longitude)</label>
                                    <input type="text" name="longitude" value="<?= htmlspecialchars($office['longitude']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ekstra -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Ekstra</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Görsel URL (İsteğe bağlı)</label>
                                <input type="text" name="image_url" value="<?= htmlspecialchars($office['image_url']) ?>" placeholder="/assets/images/offices/london.jpg" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sıralama (Küçük olan önce çıkar)</label>
                                <input type="number" name="sort_order" value="<?= htmlspecialchars($office['sort_order']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200 flex justify-end">
                    <a href="offices.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-3">
                        İptal
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
