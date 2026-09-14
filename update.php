<?php
/**
 * One-time Office Data Updater
 * Run this script once to wipe old offices and insert the new ones from seed.sql.
 * IMPORTANT: Delete this file after running it!
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDb();
    
    // 1. Delete all existing records and reset auto-increment
    $db->exec("TRUNCATE TABLE offices");
    
    // 2. Read the new seed file
    $seedFile = __DIR__ . '/database/seed.sql';
    if (file_exists($seedFile)) {
        $seed = file_get_contents($seedFile);
        $statements = array_filter(array_map('trim', explode(';', $seed)));
        
        $count = 0;
        foreach ($statements as $stmt) {
            if (!empty($stmt) && stripos($stmt, 'INSERT') !== false) {
                $db->exec($stmt);
                $count++;
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Update</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50 flex items-center justify-center min-h-screen">
            <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Güncelleme Başarılı!</h1>
                <p class="text-gray-600 mb-6">Eski ofis verileri temizlendi ve <b><?= $count ?></b> adet yeni ofis başarıyla veritabanına eklendi.</p>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                    <strong>⚠️ ÖNEMLİ:</strong> Güvenliğiniz için lütfen sunucunuzdaki <code>update.php</code> dosyasını hemen <strong>silin</strong>.
                </div>
                <a href="/" class="bg-[#0c2d74] text-white px-6 py-2 rounded-xl font-semibold hover:bg-[#1a4ba0] transition-colors">Ana Sayfaya Dön</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "Hata: database/seed.sql dosyası bulunamadı!";
    }
} catch (PDOException $e) {
    echo "Veritabanı Hatası: " . $e->getMessage();
}
