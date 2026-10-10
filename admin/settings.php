<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$db = getDb();
$ready = (bool)$db->query("SHOW TABLES LIKE 'site_settings'")->fetchColumn();

$fields = [
    'head_code'   => ['<head> içine eklenecek kod', 'Her sayfanın <head> bölümünün sonuna eklenir. Google Tag Manager (üst parça), Meta Pixel, doğrulama etiketleri (meta) ve ek stil için uygundur.'],
    'body_code'   => ['<body> başlangıcına eklenecek kod', 'Açılan <body> etiketinden hemen sonra eklenir. Google Tag Manager\'ın <noscript> parçası burada olmalıdır.'],
    'footer_code' => ['Sayfa sonuna eklenecek kod', 'Kapanan </body> etiketinden hemen önce eklenir. Sohbet (chat) araçları, ısı haritası ve gecikmesi sorun olmayan izleme kodları için uygundur.'],
];
$maxLen = 20000;
$msg = '';
$err = '';

$isPwForm = ($_POST['form'] ?? '') === 'password';
$pwErr = '';

if ($isPwForm) {
    $cur_pw = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    $new_pw = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $new_pw2 = is_string($_POST['new_password2'] ?? null) ? $_POST['new_password2'] : '';
    $row = $db->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $row->execute([(int)$_SESSION['admin_user_id']]);
    $hash = $row->fetchColumn();
    if (is_login_locked((string)$_SESSION['admin_username'])) {
        $pwErr = 'Çok fazla hatalı deneme. Lütfen 15 dakika sonra tekrar deneyin.';
    } elseif (!$hash || !password_verify($cur_pw, $hash)) {
        record_login_failure((string)$_SESSION['admin_username']);
        $pwErr = 'Mevcut şifre yanlış.';
    } elseif (mb_strlen($new_pw) < 12) {
        $pwErr = 'Yeni şifre en az 12 karakter olmalı.';
    } elseif ($new_pw !== $new_pw2) {
        $pwErr = 'Yeni şifreler aynı değil.';
    } elseif ($new_pw === $cur_pw) {
        $pwErr = 'Yeni şifre eskisinden farklı olmalı.';
    } else {
        $newHash = password_hash($new_pw, PASSWORD_DEFAULT);
        $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$newHash, (int)$_SESSION['admin_user_id']]);
        $_SESSION['admin_pw_fp'] = hash('sha256', $newHash);
        session_regenerate_id(true);
        header('Location: settings.php?msg=pw');
        exit;
    }
}

if ($ready && !$isPwForm && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];
    foreach ($fields as $key => $_) {
        $v = str_replace("\r\n", "\n", (string)($_POST[$key] ?? ''));
        if (mb_strlen($v) > $maxLen) { $err = 'Bir alan ' . $maxLen . ' karakterden uzun olamaz.'; break; }
        $values[$key] = trim($v);
    }
    $ga = strtoupper(trim((string)($_POST['ga4_id'] ?? '')));
    if ($ga !== '' && $ga !== 'KAPAT' && !preg_match('/^G-[A-Z0-9]{4,20}$/', $ga)) {
        $err = 'GA4 ölçüm kimliği "G-XXXXXXXXXX" biçiminde olmalı (kapatmak için "kapat" yazın, varsayılan için boş bırakın).';
    }
    $values['ga4_id'] = $ga === 'KAPAT' ? 'kapat' : $ga;
    $values['consent_mode'] = ($_POST['consent_mode'] ?? '') === 'advanced' ? 'advanced' : 'basic';
    $served = trim((string)($_POST['stat_served'] ?? ''));
    if ($served !== '' && !preg_match('/^\d{1,5}\+?$/', $served)) $err = 'Hizmet verilen ülke sayısı bir sayı olmalı (ör. 90 veya 90+).';
    $values['stat_served'] = $served;
    if ($err === '') {
        $st = $db->prepare("INSERT INTO site_settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
        foreach ($values as $k => $v) $st->execute([$k, $v]);
        header('Location: settings.php?msg=saved');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isPwForm && $err !== '') {
    $cur = $_POST;
} else {
    $cur = [];
    if ($ready) foreach (array_merge(array_keys($fields), ['ga4_id', 'consent_mode', 'stat_served']) as $k) $cur[$k] = siteSetting($k);
}
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$nav = ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'event_requests.php' => 'Talepler', 'settings.php' => 'Ayarlar', 'admins.php' => 'Yöneticiler', '../apply_update.php' => 'Güncellemeler'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?= @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="block px-4 py-2 rounded-md <?= $href === 'settings.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <a href="../" target="_blank" class="block px-4 py-2 rounded-md text-gray-300 hover:bg-gray-800 hover:text-white">Siteyi gör ↗</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8">
            <h2 class="text-xl font-semibold text-gray-800">Ayarlar</h2>
        </header>
        <nav class="md:hidden bg-gray-900 text-sm flex overflow-x-auto">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="px-4 py-3 whitespace-nowrap <?= $href === 'settings.php' ? 'text-white bg-gray-800' : 'text-gray-300' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 md:p-8 max-w-4xl w-full space-y-6">
            <?php if (!$ready): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-4 rounded-lg">
                    <p class="font-semibold">Bu ekran için veritabanı yaması gerekiyor.</p>
                    <p class="mt-1 text-sm"><code>patch_20261010_site_settings.sql</code> bir ayar tablosu ekler, mevcut verilere dokunmaz.</p>
                    <a href="../apply_update.php" class="inline-block mt-3 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium">Yamayı uygula</a>
                </div>
            <?php else: ?>
                <?php if (($_GET['msg'] ?? '') === 'saved'): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Kaydedildi. Değişiklik sitede hemen geçerli olur.</div>
                <?php endif; ?>
                <?php if ($err): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?= $h($err) ?></div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <?= csrf_field() ?>

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6">
                        <h3 class="font-semibold text-gray-900">Google Analytics (GA4)</h3>
                        <p class="text-sm text-gray-600 mt-1">Sitenin ölçüm kimliği. Boş bırakırsanız mevcut kimlik (<code><?= $h(ga4Id()) ?></code>) kullanılır. Ölçümü kapatmak için <code>kapat</code> yazın.</p>
                        <input type="text" name="ga4_id" value="<?= $h($cur['ga4_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" maxlength="30"
                               class="mt-3 w-full sm:w-72 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono">
                    </section>

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6">
                        <h3 class="font-semibold text-gray-900">Çerez onayı modu (Google Consent Mode v2)</h3>
                        <p class="text-sm text-gray-600 mt-1">Onay şeridi AB/AEA, Birleşik Krallık, İsviçre, Rusya ve ülkesi bilinmeyen ziyaretçilere çıkar. Her iki modda da Google'a gerekli onay sinyalleri (analytics_storage, ad_storage, ad_user_data, ad_personalization) gönderilir: onaydan önce "denied", "Kabul et"ten sonra "granted".</p>
                        <div class="mt-3 space-y-3 text-sm text-gray-800">
                            <label class="flex gap-3 items-start"><input type="radio" name="consent_mode" value="basic" class="mt-1" <?= ($cur['consent_mode'] ?? '') !== 'advanced' ? 'checked' : '' ?>>
                                <span><strong>Temel (önerilen başlangıç):</strong> GA4 ve aşağıdaki özel kodlar onay verilmeden hiç yüklenmez. En güvenli seçenek, onaysız ziyaretçi ölçülmez.</span></label>
                            <label class="flex gap-3 items-start"><input type="radio" name="consent_mode" value="advanced" class="mt-1" <?= ($cur['consent_mode'] ?? '') === 'advanced' ? 'checked' : '' ?>>
                                <span><strong>Gelişmiş:</strong> GA4 ve özel kodlar her zaman yüklenir, ama Google etiketleri onay verilene kadar çerez kullanmadan (denied) çalışır. Google Tag Manager kullanacaksanız bu moddur. <em>Dikkat: bu modda özel alanlara Google dışı araçlar (Meta Pixel, sohbet vb.) koymayın, onaysız çalışırlar. Onları GTM içinde onay koşuluna bağlayın.</em></span></label>
                        </div>
                    </section>

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6">
                        <h3 class="font-semibold text-gray-900">Ana sayfa istatistiği: hizmet verilen ülke</h3>
                        <p class="text-sm text-gray-600 mt-1">Ofis ve ülke sayıları veritabanından otomatik hesaplanır. Hastalara hizmet verilen ülke sayısı veritabanında olmadığı için buradan girilir (ör. <code>90+</code> veya <code>92</code>). Boşsa 90+ gösterilir.</p>
                        <input type="text" name="stat_served" value="<?= $h($cur['stat_served'] ?? '') ?>" placeholder="90+" maxlength="6"
                               class="mt-3 w-full sm:w-40 border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </section>

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6 space-y-6">
                        <div>
                            <h3 class="font-semibold text-gray-900">Özel kod alanları</h3>
                            <p class="text-sm text-gray-600 mt-1">Buraya yapıştırdığınız kod sitenin tüm sayfalarında olduğu gibi (değiştirilmeden) yayınlanır. Yalnızca güvendiğiniz kaynaklardan gelen kodu ekleyin. Yönetici olarak giriş yapmışken siteyi gezdiğinizde bu kodlar çalışmaz, böylece kendi ziyaretleriniz ölçüme karışmaz. Onay şeridi gösterilen ziyaretçilerde kodların ne zaman yüklendiği yukarıdaki çerez onayı moduna bağlıdır. Bir kod siteyi bozarsa alanı boşaltıp kaydetmeniz yeterlidir; bu ekran her zaman açılır.</p>
                        </div>
                        <?php foreach ($fields as $key => [$label, $hint]): ?>
                        <div>
                            <label for="<?= $key ?>" class="block text-sm font-medium text-gray-800"><?= $h($label) ?></label>
                            <p class="text-xs text-gray-500 mt-0.5"><?= $h($hint) ?></p>
                            <textarea id="<?= $key ?>" name="<?= $key ?>" rows="6" spellcheck="false" autocomplete="off"
                                      class="mt-2 w-full border border-gray-300 rounded-md px-3 py-2 text-xs font-mono leading-relaxed"
                                      placeholder="<!-- kodu buraya yapıştırın -->"><?= $h($cur[$key] ?? '') ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </section>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium">Kaydet</button>
                        <a href="index.php" class="text-sm text-gray-500 hover:underline">İptal</a>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (($_GET['msg'] ?? '') === 'pw'): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">Şifre değiştirildi.</div>
            <?php endif; ?>
            <?php if ($pwErr): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?= $h($pwErr) ?></div>
            <?php endif; ?>
            <form method="POST" class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6 space-y-4" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="form" value="password">
                <div>
                    <h3 class="font-semibold text-gray-900">Şifre değiştir</h3>
                    <p class="text-sm text-gray-600 mt-1">Giriş yapan kullanıcı: <strong><?= $h($_SESSION['admin_username']) ?></strong>. Yeni şifre en az 12 karakter olmalı; uzun bir cümle en iyisidir.</p>
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    <label class="block text-sm text-gray-700">Mevcut şifre
                        <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></label>
                    <label class="block text-sm text-gray-700">Yeni şifre
                        <input type="password" name="new_password" required minlength="12" autocomplete="new-password" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></label>
                    <label class="block text-sm text-gray-700">Yeni şifre (tekrar)
                        <input type="password" name="new_password2" required minlength="12" autocomplete="new-password" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></label>
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-md text-sm font-medium">Şifreyi değiştir</button>
            </form>
        </div>
    </main>
</body>
</html>
