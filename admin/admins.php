<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$db = getDb();
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$meId = (int)$_SESSION['admin_user_id'];
$msg = $_GET['msg'] ?? '';
$err = '';
$errIn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $total = (int)$db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

    if ($act === 'change_own') {
        $cur_pw = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new_pw = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $new_pw2 = is_string($_POST['new_password2'] ?? null) ? $_POST['new_password2'] : '';
        $row = $db->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
        $row->execute([$meId]);
        $hash = $row->fetchColumn();
        $ownErr = '';
        if (is_login_locked((string)$_SESSION['admin_username'])) {
            $ownErr = 'Çok fazla hatalı deneme. Lütfen 15 dakika sonra tekrar deneyin.';
        } elseif (!$hash || !password_verify($cur_pw, $hash)) {
            record_login_failure((string)$_SESSION['admin_username']);
            $ownErr = 'Mevcut şifre yanlış.';
        } elseif (mb_strlen($new_pw) < 12) {
            $ownErr = 'Yeni şifre en az 12 karakter olmalı.';
        } elseif ($new_pw !== $new_pw2) {
            $ownErr = 'Yeni şifreler aynı değil.';
        } elseif ($new_pw === $cur_pw) {
            $ownErr = 'Yeni şifre eskisinden farklı olmalı.';
        } else {
            $newHash = password_hash($new_pw, PASSWORD_DEFAULT);
            $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$newHash, $meId]);
            $_SESSION['admin_pw_fp'] = hash('sha256', $newHash); // keeps this session logged in
            session_regenerate_id(true);
            header('Location: admins.php?msg=own');
            exit;
        }
        $err = $ownErr;
        $errIn = 'own';
    } elseif ($act === 'add') {
        $u = trim((string)($_POST['username'] ?? ''));
        $p1 = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $p2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';
        $exists = $db->prepare('SELECT 1 FROM admin_users WHERE username = ?');
        $exists->execute([$u]);
        if (!preg_match('/^[A-Za-z0-9._@-]{3,60}$/', $u)) $err = 'Kullanıcı adı 3-60 karakter olmalı (harf, rakam, . _ @ -).';
        elseif ($exists->fetchColumn()) $err = 'Bu kullanıcı adı zaten var.';
        elseif (mb_strlen($p1) < 12) $err = 'Şifre en az 12 karakter olmalı.';
        elseif ($p1 !== $p2) $err = 'Şifreler aynı değil.';
        else {
            $db->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)')->execute([$u, password_hash($p1, PASSWORD_DEFAULT)]);
            header('Location: admins.php?msg=added');
            exit;
        }
    } elseif ($act === 'reset' && $id > 0 && $id !== $meId) {
        $p1 = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (mb_strlen($p1) < 12) $err = 'Yeni şifre en az 12 karakter olmalı.';
        else {
            $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([password_hash($p1, PASSWORD_DEFAULT), $id]);
            header('Location: admins.php?msg=reset');
            exit;
        }
    } elseif ($act === 'delete' && $id > 0) {
        if ($id === $meId) $err = 'Kendi hesabınızı silemezsiniz.';
        elseif ($total <= 1) $err = 'Son yönetici silinemez.';
        else {
            $db->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
            header('Location: admins.php?msg=deleted');
            exit;
        }
    }
}

$admins = $db->query("SELECT id, username, created_at FROM admin_users ORDER BY username")->fetchAll();
$nav = ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'event_requests.php' => 'Talepler', 'settings.php' => 'Ayarlar', 'admins.php' => 'Yöneticiler', '../apply_update.php' => 'Güncellemeler'];
$input = 'border border-gray-300 rounded-md px-3 py-2 text-sm';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yöneticiler - Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="block px-4 py-2 rounded-md <?= $href === 'admins.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Siteyi gör ↗</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8">
            <h2 class="text-xl font-semibold text-gray-800">Yöneticiler</h2>
        </header>
        <nav class="md:hidden bg-gray-900 text-sm flex overflow-x-auto">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="px-4 py-3 whitespace-nowrap <?= $href === 'admins.php' ? 'text-white bg-gray-800' : 'text-gray-300' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 md:p-8 max-w-4xl w-full space-y-6">
            <?php if ($msg): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?= $h(['added' => 'Yönetici eklendi.', 'reset' => 'Şifre değiştirildi. Bu kişinin açık oturumları kapandı.', 'deleted' => 'Yönetici silindi.', 'own' => 'Şifreniz değiştirildi.'][$msg] ?? '') ?></div>
            <?php endif; ?>
            <?php if ($err && $errIn !== 'own'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?= $h($err) ?></div>
            <?php endif; ?>

            <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-3 text-left">Kullanıcı</th><th class="px-4 py-3 text-left">Eklenme</th><th class="px-4 py-3 text-right">İşlem</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php foreach ($admins as $a): $isMe = (int)$a['id'] === $meId; ?>
                        <tr class="align-middle">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= $h($a['username']) ?><?= $isMe ? ' <span class="ml-2 text-xs font-normal text-blue-600">(siz)</span>' : '' ?></td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= $h(date('d.m.Y', strtotime($a['created_at']))) ?></td>
                            <td class="px-4 py-3 text-right">
                            <?php if ($isMe): ?>
                                <a href="#sifrem" class="text-indigo-600 hover:underline text-xs">Şifremi değiştir</a>
                            <?php else: ?>
                                <form method="POST" class="inline-flex flex-wrap items-center justify-end gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <input type="password" name="password" minlength="12" placeholder="Yeni şifre (12+)" autocomplete="new-password" class="<?= $input ?> w-36 !py-1 text-xs">
                                    <button name="action" value="reset" class="text-indigo-600 hover:underline text-xs">Şifre sıfırla</button>
                                    <button name="action" value="delete" class="text-red-600 hover:underline text-xs" formnovalidate onclick="return confirm('<?= $h($a['username']) ?> yöneticisi silinsin mi?');">Sil</button>
                                </form>
                            <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <form method="POST" id="sifrem" action="admins.php#sifrem" class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6 space-y-4 scroll-mt-4" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_own">
                <div>
                    <h3 class="font-semibold text-gray-900">Şifremi değiştir <span class="font-normal text-gray-500">(<?= $h($_SESSION['admin_username']) ?>)</span></h3>
                    <p class="text-sm text-gray-600 mt-1">Yalnızca kendi hesabınızın şifresini değiştirir. Yeni şifre en az 12 karakter olmalı; uzun bir cümle en iyisidir. Başkasının şifresi için yukarıdaki listede "Şifre sıfırla" kullanılır.</p>
                </div>
                <?php if ($err && $errIn === 'own'): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"><?= $h($err) ?></div>
                <?php endif; ?>
                <div class="grid sm:grid-cols-3 gap-4">
                    <label class="block text-sm text-gray-700">Mevcut şifre
                        <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 w-full <?= $input ?>"></label>
                    <label class="block text-sm text-gray-700">Yeni şifre
                        <input type="password" name="new_password" required minlength="12" autocomplete="new-password" class="mt-1 w-full <?= $input ?>"></label>
                    <label class="block text-sm text-gray-700">Yeni şifre (tekrar)
                        <input type="password" name="new_password2" required minlength="12" autocomplete="new-password" class="mt-1 w-full <?= $input ?>"></label>
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-md text-sm font-medium">Şifremi değiştir</button>
            </form>

            <form method="POST" class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6 space-y-4" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div>
                    <h3 class="font-semibold text-gray-900">Yeni yönetici ekle</h3>
                    <p class="text-sm text-gray-600 mt-1">Her yönetici tüm ekranlara erişir. Şifre en az 12 karakter olmalı; ilk girişten sonra kişi şifresini bu sayfadaki "Şifremi değiştir" bölümünden değiştirebilir. Silinen veya şifresi sıfırlanan kişinin açık oturumları hemen kapanır.</p>
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    <label class="block text-sm text-gray-700">Kullanıcı adı
                        <input type="text" name="username" required maxlength="60" autocomplete="off" class="mt-1 w-full <?= $input ?>"></label>
                    <label class="block text-sm text-gray-700">Şifre
                        <input type="password" name="password" required minlength="12" autocomplete="new-password" class="mt-1 w-full <?= $input ?>"></label>
                    <label class="block text-sm text-gray-700">Şifre (tekrar)
                        <input type="password" name="password2" required minlength="12" autocomplete="new-password" class="mt-1 w-full <?= $input ?>"></label>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium">Yönetici ekle</button>
            </form>
        </div>
    </main>
</body>
</html>
