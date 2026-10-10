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

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
    if ($err === '') {
        $st = $db->prepare("INSERT INTO site_settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
        foreach ($values as $k => $v) $st->execute([$k, $v]);
        header('Location: settings.php?msg=saved');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $err !== '') {
    $cur = $_POST;
} else {
    $cur = [];
    if ($ready) foreach (array_merge(array_keys($fields), ['ga4_id']) as $k) $cur[$k] = siteSetting($k);
}
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$nav = ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'settings.php' => 'Ayarlar'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-gray-900 text-white flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-lg">Acıbadem Admin</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <?php foreach ($nav as $href => $label): ?>
            <a href="<?= $href ?>" class="block px-4 py-2 rounded-md <?= $href === 'settings.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>"><?= $label ?></a>
            <?php endforeach; ?>
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

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6 space-y-6">
                        <div>
                            <h3 class="font-semibold text-gray-900">Özel kod alanları</h3>
                            <p class="text-sm text-gray-600 mt-1">Buraya yapıştırdığınız kod sitenin tüm sayfalarında olduğu gibi (değiştirilmeden) yayınlanır. Yalnızca güvendiğiniz kaynaklardan gelen kodu ekleyin. Yönetici olarak giriş yapmışken siteyi gezdiğinizde bu kodlar çalışmaz, böylece kendi ziyaretleriniz ölçüme karışmaz. Bir kod siteyi bozarsa alanı boşaltıp kaydetmeniz yeterlidir; bu ekran her zaman açılır.</p>
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
        </div>
    </main>
</body>
</html>
