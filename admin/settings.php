<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$db = getDb();
$ready = (bool)$db->query("SHOW TABLES LIKE 'site_settings'")->fetchColumn();

$fields = [
    'head_code'   => ['<head> içine eklenecek kod', 'Her sayfanın <head> bölümünün sonuna eklenir. Doğrulama etiketleri (meta) ve ek stil için uygundur. Google Tag Manager, GA4 ve Meta Pixel için buraya kod koymayın; yukarıdaki Ölçüm bölümündeki alanları kullanın.'],
    'body_code'   => ['<body> başlangıcına eklenecek kod', 'Açılan <body> etiketinden hemen sonra eklenir. Google Tag Manager\'ın <noscript> parçasını site kendisi ekler, buraya koymanıza gerek yoktur.'],
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
    // Measurement method (direct GA4 / Google Tag Manager / off), GTM ID and Meta Pixel IDs.
    // A pasted snippet is accepted: only the ID is kept.
    $method = (string)($_POST['measure_method'] ?? 'ga4');
    $values['measure_method'] = in_array($method, ['ga4', 'gtm', 'off'], true) ? $method : 'ga4';
    $gtmRaw = trim((string)($_POST['gtm_id'] ?? ''));
    $values['gtm_id'] = parseGtmId($gtmRaw);
    if ($values['measure_method'] === 'gtm' && $values['gtm_id'] === '') $err = 'Google Tag Manager seçildi ama GTM kimliği girilmedi.';
    if ($gtmRaw !== '' && $values['gtm_id'] === '') $err = 'Google Tag Manager kimliği bulunamadı. "GTM-XXXXXXX" biçiminde yazın ya da GTM\'in verdiği kodu olduğu gibi yapıştırın.';
    $pixelRaw = trim((string)($_POST['meta_pixel_id'] ?? ''));
    $values['meta_pixel_id'] = parseMetaPixelId($pixelRaw);
    if ($pixelRaw !== '' && $values['meta_pixel_id'] === '') $err = 'Meta Pixel kimliği bulunamadı. Yalnızca rakamlardan oluşan kimliği (ör. 1234567890123456) yazın ya da Meta\'nın verdiği kodu yapıştırın.';
    $values['consent_mode'] = ($_POST['consent_mode'] ?? '') === 'advanced' ? 'advanced' : 'basic';
    $served = trim((string)($_POST['stat_served'] ?? ''));
    if ($served !== '' && !preg_match('/^\d{1,5}\+?$/', $served)) $err = 'Hizmet verilen ülke sayısı bir sayı olmalı (ör. 90 veya 90+).';
    $values['stat_served'] = $served;
    $values['office_whatsapp'] = ($_POST['office_whatsapp'] ?? '') === '1' ? '1' : '0';
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
    if ($ready) foreach (array_merge(array_keys($fields), ['ga4_id', 'consent_mode', 'stat_served', 'office_whatsapp', 'measure_method', 'gtm_id', 'meta_pixel_id']) as $k) $cur[$k] = siteSetting($k);
}
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$nav = ['index.php' => 'Dashboard', 'offices.php' => 'Ofisler', 'teams.php' => 'Ekipler', 'events.php' => 'Etkinlikler', 'people.php' => 'Kişiler', 'event_requests.php' => 'Talepler', 'settings.php' => 'Ayarlar', 'admins.php' => 'Yöneticiler', '../apply_update.php' => 'Güncellemeler'];
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
                        <h3 class="font-semibold text-gray-900">WhatsApp düğmeleri (ofisler ve etkinlikler)</h3>
                        <p class="text-sm text-gray-600 mt-1">Kapalıyken ofis sayfalarında, etkinlik detay sayfalarında ve ana sayfadaki ofis kartında WhatsApp düğmesi görünmez; diğer düğmeler (ara, e-posta, yol tarifi) boşluk bırakmadan yerleşir. Ofislere girilen WhatsApp numaraları silinmez, açtığınızda hemen geri gelir. Üst menü, alt bilgi ve genel merkez kartındaki merkez WhatsApp hattı bu ayardan etkilenmez.</p>
                        <label class="mt-3 flex gap-3 items-start text-sm text-gray-800">
                            <input type="checkbox" name="office_whatsapp" value="1" class="mt-0.5 h-4 w-4" <?= ($cur['office_whatsapp'] ?? '') === '1' ? 'checked' : '' ?>>
                            <span><strong>Ofis ve etkinlik WhatsApp düğmelerini göster</strong></span>
                        </label>
                        <p class="mt-2 text-xs text-gray-500">Şu an sitede: <?= ($cur['office_whatsapp'] ?? '') === '1' ? '<span class="font-semibold text-green-700">açık</span>' : '<span class="font-semibold text-gray-700">kapalı</span>' ?></p>
                    </section>

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6">
                        <h3 class="font-semibold text-gray-900">Ölçüm: Google Analytics (GA4), Google Tag Manager, Meta Pixel</h3>
                        <p class="text-sm text-gray-600 mt-1">Site, her sayfada ve her tıklamada (WhatsApp, telefon, e-posta, yol tarifi, form…) ne olduğunu sabit adlarla kendisi bildirir. Burada bu bilgilerin hangi yolla ölçüleceğini seçersiniz. Aynı anda yalnızca biri yüklenir, bu yüzden ziyaretler iki kez sayılmaz.</p>
                        <?php $mm = $cur['measure_method'] ?? ''; $mm = in_array($mm, ['ga4', 'gtm', 'off'], true) ? $mm : 'ga4'; ?>
                        <div class="mt-4 space-y-4 text-sm text-gray-800">
                            <label class="flex gap-3 items-start"><input type="radio" name="measure_method" value="ga4" class="mt-1" <?= $mm === 'ga4' ? 'checked' : '' ?>>
                                <span><strong>Doğrudan GA4</strong> (varsayılan): GA4 kodu sitede doğrudan yüklenir, tıklamalar da GA4'e doğrudan gider.</span></label>
                            <div class="ms-7">
                                <label for="ga4_id" class="block text-xs font-medium text-gray-700">GA4 ölçüm kimliği</label>
                                <input type="text" id="ga4_id" name="ga4_id" value="<?= $h($cur['ga4_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" maxlength="30"
                                       class="mt-1 w-full sm:w-72 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono">
                                <p class="text-xs text-gray-500 mt-1">Boş bırakırsanız mevcut kimlik (<code><?= $h(ga4Id() ?: 'yok') ?></code>) kullanılır. Ölçümü tamamen kapatmak için <code>kapat</code> yazın. Google Tag Manager kullanırken bu kimlik GTM'in içinde tanımlanır.</p>
                            </div>
                            <label class="flex gap-3 items-start"><input type="radio" name="measure_method" value="gtm" class="mt-1" <?= $mm === 'gtm' ? 'checked' : '' ?>>
                                <span><strong>Google Tag Manager:</strong> Sitede yalnızca GTM yüklenir; GA4 ve diğer etiketler GTM'in içinden yönetilir. Doğrudan GA4 kodu otomatik olarak kapanır.</span></label>
                            <div class="ms-7">
                                <label for="gtm_id" class="block text-xs font-medium text-gray-700">GTM kapsayıcı kimliği</label>
                                <input type="text" id="gtm_id" name="gtm_id" value="<?= $h($cur['gtm_id'] ?? '') ?>" placeholder="GTM-XXXXXXX" maxlength="5000"
                                       class="mt-1 w-full sm:w-72 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono">
                                <p class="text-xs text-gray-500 mt-1">Yalnızca kimliği yazın. GTM'in verdiği uzun kodu yapıştırırsanız kimlik kendiliğinden ayıklanır; kodu aşağıdaki özel kod alanlarına <strong>koymayın</strong>. Kimliği kaydedip yöntemi henüz değiştirmeden GTM'in "Önizleme" düğmesiyle canlı sitede deneme yapabilirsiniz.</p>
                            </div>
                            <label class="flex gap-3 items-start"><input type="radio" name="measure_method" value="off" class="mt-1" <?= $mm === 'off' ? 'checked' : '' ?>>
                                <span><strong>Kapalı:</strong> Hiçbir ölçüm kodu yüklenmez.</span></label>
                            <div class="pt-2 border-t border-gray-200">
                                <label for="meta_pixel_id" class="block text-sm font-medium text-gray-800">Meta Pixel kimliği</label>
                                <input type="text" id="meta_pixel_id" name="meta_pixel_id" value="<?= $h($cur['meta_pixel_id'] ?? '') ?>" placeholder="1234567890123456" maxlength="5000"
                                       class="mt-1 w-full sm:w-72 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono">
                                <p class="text-xs text-gray-500 mt-1">Boşken Pixel yüklenmez. Doluyken: Doğrudan GA4 modunda site Pixel'i kendisi yükler; Google Tag Manager modunda kimlik GTM'e iletilir ve GTM'deki Meta etiketi kullanır. Her iki durumda da onay şeridi gösterilen ziyaretçide Pixel ancak "Kabul et"ten sonra çalışır. WhatsApp, telefon, e-posta ve yol tarifi tıklamaları Meta'ya "Contact", etkinlik talebi ve konsültasyon tıklaması "Lead" olarak gider.</p>
                            </div>
                        </div>
                        <?php
                        $gaSrc = ga4IdSource();
                        $gaSrcText = ['admin' => 'bu sayfadaki alan', 'config' => 'sunucudaki config.php (GA4_ID)', 'default' => 'yerleşik varsayılan', 'off' => 'ölçüm kapalı'][$gaSrc];
                        $cc = cfCountry();
                        $mode = measureMode();
                        $modeText = ['ga4' => 'Doğrudan GA4 (' . (ga4Id() ?: '-') . ')', 'gtm' => 'Google Tag Manager (' . gtmId() . ')', 'off' => 'kapalı'][$mode];
                        $warnings = customCodeMeasureWarnings();
                        if (measureMethodSetting() === 'gtm' && $mode !== 'gtm') $warnings[] = 'Google Tag Manager seçili ama geçerli GTM kimliği yok: site doğrudan GA4 ile ölçmeye devam ediyor.';
                        ?>
                        <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 space-y-1.5">
                            <p class="font-medium text-gray-900">Ölçüm durumu</p>
                            <p>Sitede şu an kullanılan yöntem: <strong><?= $h($modeText) ?></strong></p>
                            <p>GA4 kimliğinin kaynağı: <?= $h($gaSrcText) ?></p>
                            <p>Meta Pixel: <?= metaPixelId() !== '' ? '<code class="font-mono">' . $h(metaPixelId()) . '</code>' . ($mode === 'gtm' ? ' (GTM içindeki Meta etiketi kullanır)' : ($mode === 'ga4' ? ' (site yükler)' : ' (ölçüm kapalı olduğu için yüklenmez)')) : 'yok' ?></p>
                            <p>Cloudflare ülke bilgisi (sizin bu isteğiniz): <?php if ($cc !== ''): ?><code><?= $h($cc) ?></code><?php else: ?><strong class="text-red-700">gelmiyor</strong>. Bu durumda tüm ziyaretçiler "ülkesi bilinmeyen" sayılır, çerez şeridi herkese çıkar ve Temel modda onay vermeyen hiçbir ziyaretçi ölçülmez. Cloudflare'de "IP Geolocation" ayarını açın.<?php endif; ?></p>
                            <p>Çerez onayı modu: <?= ($cur['consent_mode'] ?? '') === 'advanced' ? 'Gelişmiş' : 'Temel' ?></p>
                            <p class="text-gray-600">Yönetici olarak giriş yapmış tarayıcıda ölçüm kodu yüklenmez, kendi ziyaretleriniz ölçülmez (GTM önizlemesi hariç). Ölçümü denemek için siteyi gizli pencerede açın ve GA4'te <em>Raporlar &gt; Gerçek zamanlı</em> ekranına bakın. Sayfa kaynağında <code>olcum:</code> ile başlayan not, o sayfada neyin yüklendiğini ya da neden yüklenmediğini söyler.</p>
                        </div>
                        <?php if ($warnings): ?>
                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 space-y-1.5">
                            <p class="font-medium">Dikkat: ölçüm iki kez yüklenebilir veya beklenenden farklı çalışıyor</p>
                            <?php foreach ($warnings as $w): ?><p>• <?= $h($w) ?></p><?php endforeach; ?>
                            <p class="text-xs">Özel kod alanlarındaki GA4/GTM/Meta kodları yukarıdaki seçimden bağımsız yüklenir. Ölçüm kodunu yalnızca bu bölümdeki alanlara girin, özel kod alanından silin.</p>
                        </div>
                        <?php endif; ?>
                    </section>

                    <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 md:p-6">
                        <h3 class="font-semibold text-gray-900">Çerez onayı modu (Google Consent Mode v2)</h3>
                        <p class="text-sm text-gray-600 mt-1">Onay şeridi AB/AEA, Birleşik Krallık, İsviçre, Rusya ve ülkesi bilinmeyen ziyaretçilere çıkar. Her iki modda da Google'a gerekli onay sinyalleri (analytics_storage, ad_storage, ad_user_data, ad_personalization) gönderilir: onaydan önce "denied", "Kabul et"ten sonra "granted".</p>
                        <div class="mt-3 space-y-3 text-sm text-gray-800">
                            <label class="flex gap-3 items-start"><input type="radio" name="consent_mode" value="basic" class="mt-1" <?= ($cur['consent_mode'] ?? '') !== 'advanced' ? 'checked' : '' ?>>
                                <span><strong>Temel (önerilen başlangıç):</strong> GA4 ve aşağıdaki özel kodlar onay verilmeden hiç yüklenmez. En güvenli seçenek, onaysız ziyaretçi ölçülmez.</span></label>
                            <label class="flex gap-3 items-start"><input type="radio" name="consent_mode" value="advanced" class="mt-1" <?= ($cur['consent_mode'] ?? '') === 'advanced' ? 'checked' : '' ?>>
                                <span><strong>Gelişmiş:</strong> GA4/GTM ve özel kodlar her zaman yüklenir, ama Google etiketleri onay verilene kadar çerez kullanmadan (denied) çalışır; Meta Pixel ise onaya kadar bekletilir. Google Tag Manager ile önerilen moddur. <em>Dikkat: bu modda özel alanlara Google dışı araçlar (Meta Pixel, sohbet vb.) koymayın, onaysız çalışırlar. Onları GTM içinde onay koşuluna bağlayın.</em></span></label>
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
        </div>
    </main>
</body>
</html>
