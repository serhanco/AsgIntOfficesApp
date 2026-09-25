<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_login();

// Logout logic
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    admin_logout();
    header('Location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">
            Acıbadem Admin
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="block px-4 py-2 rounded-md bg-gray-800 text-white">Dashboard</a>
            <a href="offices.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Ofisler</a>
            <a href="teams.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Ekipler</a>
            <a href="activities.php" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Etkinlikler</a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <div class="text-sm text-gray-400 mb-2">Giriş yapan: <?= htmlspecialchars($_SESSION['admin_username']) ?></div>
            <a href="?action=logout" class="block w-full text-center px-4 py-2 border border-gray-600 rounded-md text-sm text-gray-300 hover:bg-gray-800 hover:text-white">Çıkış Yap</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <!-- Top header -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </header>

        <!-- Content Area -->
        <div class="p-8">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sisteme Hoş Geldiniz</h3>
                <p class="text-gray-600">Sol menüyü kullanarak ofisleri, ekipleri ve etkinlikleri yönetebilirsiniz.</p>
                
                <!-- Quick Stats placeholder -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-blue-50 border border-blue-100 p-6 rounded-lg">
                        <div class="text-blue-500 font-medium mb-1">Toplam Ofis</div>
                        <div class="text-3xl font-bold text-gray-900">-</div>
                    </div>
                    <div class="bg-green-50 border border-green-100 p-6 rounded-lg">
                        <div class="text-green-500 font-medium mb-1">Takım Üyeleri</div>
                        <div class="text-3xl font-bold text-gray-900">-</div>
                    </div>
                    <div class="bg-purple-50 border border-purple-100 p-6 rounded-lg">
                        <div class="text-purple-500 font-medium mb-1">Etkinlikler</div>
                        <div class="text-3xl font-bold text-gray-900">-</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
