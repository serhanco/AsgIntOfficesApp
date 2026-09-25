<?php
require_once __DIR__ . '/includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (admin_login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Geçersiz kullanıcı adı veya şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi - Acıbadem Int. Offices</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Yönetim Paneli</h1>
            <p class="text-sm text-gray-500 mt-2">Lütfen giriş bilgilerinizi giriniz.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-md mb-6 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-4 py-2 border">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Şifre</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-4 py-2 border">
            </div>

            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Giriş Yap
            </button>
        </form>
    </div>
</body>
</html>
