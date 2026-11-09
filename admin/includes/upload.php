<?php
/**
 * Admin Image Upload Helper
 * Acıbadem International Offices App
 *
 * Validates an uploaded image (JPG, PNG, WEBP), re-encodes it with GD
 * (which also strips anything hidden inside the file), turns phone photos upright, scales it down to
 * $maxWidth and saves it under assets/images/uploads/<subdir>/.
 *
 * Returns the stored path relative to the app root
 * (e.g. "assets/images/uploads/offices/office_6523ab.webp"),
 * or null when no file was sent. Throws RuntimeException with a
 * user-facing (Turkish) message on invalid input.
 */

const UPLOAD_MAX_BYTES = 8 * 1024 * 1024; // 8 MB

/** Bytes from a php.ini size value such as "2M" or "512K" (0 = no limit). */
function upload_ini_bytes(string $value): int {
    $value = trim($value);
    if ($value === '') return 0;
    $num = (float)$value;
    switch (strtolower(substr($value, -1))) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return (int)$num;
}

/** The real image size limit: our 8 MB, or less when the server's PHP settings allow less. */
function upload_limit_bytes(): int {
    $limit = UPLOAD_MAX_BYTES;
    foreach (['upload_max_filesize', 'post_max_size'] as $key) {
        $b = upload_ini_bytes((string)ini_get($key));
        if ($b > 0) $limit = min($limit, $b);
    }
    return $limit;
}

function upload_limit_label(): string {
    $mb = upload_limit_bytes() / 1048576;
    return (floor($mb) == $mb ? (int)$mb : number_format($mb, 1, ',', '')) . ' MB';
}

/** True when the server can shrink and re-encode images (GD). */
function upload_can_process(): bool {
    return extension_loaded('gd') && function_exists('imagecreatetruecolor');
}

/**
 * Admin form note for an image field: accepted types, the real size limit and what happens to the file on upload.
 * $advice is the field-specific recommendation (shape and size). Returns escaped HTML.
 */
function upload_image_hint(int $maxWidth, string $advice = ''): string {
    $parts = ['JPG, PNG veya WEBP; en fazla ' . upload_limit_label() . '.'];
    if ($advice !== '') $parts[] = $advice;
    if (upload_can_process()) {
        $fmt = function_exists('imagewebp') ? 'WebP biçimine çevrilip sıkıştırılır' : 'yeniden sıkıştırılır';
        $parts[] = "Yüklenen görsel otomatik olarak en fazla {$maxWidth} px genişliğe küçültülür, {$fmt}; telefon fotoğraflarının yönü düzeltilir ve konum gibi gizli bilgiler silinir.";
    } else {
        $parts[] = 'Sunucuda görsel işleme kapalı olduğu için dosya olduğu gibi kaydedilir; yüklemeden önce görseli küçültün.';
    }
    return htmlspecialchars(implode(' ', $parts), ENT_QUOTES, 'UTF-8');
}

/** Small script that warns right away when a chosen image is over the limit (before the form is sent). Print once per page. */
function upload_size_guard_script(): string {
    $limit = upload_limit_bytes();
    $label = upload_limit_label();
    return <<<HTML
<script>
document.addEventListener('change', function (e) {
    var el = e.target;
    if (!el || el.type !== 'file' || !el.accept || el.accept.indexOf('image/') !== 0) return;
    var box = el.parentNode.querySelector('[data-upload-warn]');
    if (!box) { box = document.createElement('p'); box.setAttribute('data-upload-warn', ''); box.className = 'mt-1 text-xs font-medium text-red-600'; el.insertAdjacentElement('afterend', box); }
    var f = el.files && el.files[0];
    if (f && f.size > {$limit}) {
        box.textContent = 'Bu dosya ' + (f.size / 1048576).toFixed(1).replace('.', ',') + ' MB; en fazla {$label} yüklenebilir. Lütfen daha küçük bir görsel seçin.';
        el.value = '';
    } else {
        box.textContent = '';
    }
});
</script>
HTML;
}

function handle_image_upload(string $field, string $subdir, int $maxWidth, int $maxHeight = 0): ?string {
    $maxHeight = $maxHeight > 0 ? $maxHeight : $maxWidth * 2; // very tall posters are capped too
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Görsel yüklenemedi (dosya çok büyük olabilir; en fazla ' . upload_limit_label() . ').');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Görsel en fazla ' . upload_limit_label() . ' olabilir.');
    }

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) {
        throw new RuntimeException('Sadece JPG, PNG veya WEBP görsel yükleyebilirsiniz.');
    }

    $subdir = preg_replace('/[^a-z0-9_-]/', '', strtolower($subdir));
    $relDir = 'assets/images/uploads/' . $subdir;
    $absDir = __DIR__ . '/../../' . $relDir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
        throw new RuntimeException('Yükleme klasörü oluşturulamadı: ' . $relDir);
    }

    $name = $subdir . '_' . date('Ymd') . '_' . bin2hex(random_bytes(6));

    // Re-encode with GD when available; fall back to storing the validated file as is
    if (upload_can_process()) {
        $loaders = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        $loader = $loaders[$info[2]];
        $src = function_exists($loader) ? @$loader($file['tmp_name']) : false;
        if (!$src) {
            throw new RuntimeException('Görsel okunamadı. Farklı bir dosya deneyin.');
        }

        if (!imageistruecolor($src)) {
            imagepalettetotruecolor($src); // WEBP encoder needs truecolor
        }

        // Phone photos are stored sideways with an EXIF "orientation" flag; GD ignores it, so turn the pixels upright here
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file['tmp_name']);
            $orientation = (int)($exif['Orientation'] ?? 1);
            if (in_array($orientation, [2, 4, 5, 7], true) && function_exists('imageflip')) {
                imageflip($src, IMG_FLIP_HORIZONTAL);
            }
            $angle = [3 => 180, 4 => 180, 5 => 90, 6 => 270, 7 => 270, 8 => 90][$orientation] ?? 0;
            if ($angle) {
                $rotated = imagerotate($src, $angle, 0);
                if ($rotated) { imagedestroy($src); $src = $rotated; }
            }
        }

        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, $maxWidth / $w, $maxHeight / $h);
        if ($scale < 1) {
            $newW = max(1, (int)round($w * $scale));
            $newH = max(1, (int)round($h * $scale));
            $dst = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        if (function_exists('imagewebp')) {
            imagesavealpha($src, true);
            $ok = imagewebp($src, "$absDir/$name.webp", 82);
            $ext = 'webp';
        } else {
            $ext = $allowed[$info[2]];
            if ($ext === 'jpg') {
                $ok = imagejpeg($src, "$absDir/$name.jpg", 85);
            } elseif ($ext === 'png') {
                $ok = imagepng($src, "$absDir/$name.png");
            } else {
                $ok = false;
            }
        }
        imagedestroy($src);
        if (!$ok) {
            throw new RuntimeException('Görsel kaydedilemedi.');
        }
        return "$relDir/$name.$ext";
    }

    $ext = $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], "$absDir/$name.$ext")) {
        throw new RuntimeException('Görsel kaydedilemedi.');
    }
    return "$relDir/$name.$ext";
}

/**
 * Give an uploaded file a readable, search-friendly name in the same folder: <slug>-<random 8 hex>-<suffix>.<ext>,
 * e.g. "consultation-bulent-tiras-wien-2026-11-26-3fa9c01b-12.webp". The random part makes a replaced image get a
 * new address, so browsers and Cloudflare never keep showing the old one. Returns the new relative path, or the
 * old one if renaming is not possible.
 */
function upload_rename_readable(string $relPath, string $slug, string $suffix): string {
    $abs = __DIR__ . '/../../' . $relPath;
    $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
    $slug = trim(substr(preg_replace('/[^a-z0-9]+/', '-', strtolower($slug)), 0, 90), '-') ?: 'image';
    $suffix = preg_replace('/[^a-z0-9-]/', '', strtolower($suffix));
    $name = $slug . '-' . bin2hex(random_bytes(4)) . ($suffix !== '' ? '-' . $suffix : '') . '.' . $ext;
    $newRel = dirname($relPath) . '/' . $name;
    if (!is_file($abs) || !@rename($abs, __DIR__ . '/../../' . $newRel)) return $relPath;
    return $newRel;
}

/**
 * Turn a stored image path into a URL usable from inside /admin
 */
function admin_image_src(?string $path): string {
    if (!$path) return '';
    if (preg_match('#^(https?:)?//#i', $path) || $path[0] === '/') return $path;
    return '../' . $path;
}

/**
 * PDF upload (e.g. an event announcement). Checks size, the PDF signature and the MIME type, then stores it under
 * assets/images/uploads/<subdir>/ with a random name. Returns the stored path relative to the app root, or null when no file was sent.
 * Throws RuntimeException with a user-facing (Turkish) message on invalid input.
 */
function handle_pdf_upload(string $field, string $subdir): ?string {
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('PDF yüklenemedi (dosya çok büyük olabilir).');
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('PDF en fazla 15 MB olabilir.');
    }
    $head = (string)@file_get_contents($file['tmp_name'], false, null, 0, 5);
    $mime = function_exists('finfo_open') ? (string)@finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : 'application/pdf';
    if ($head !== '%PDF-' || $mime !== 'application/pdf') {
        throw new RuntimeException('Yalnızca PDF dosyası yükleyebilirsiniz.');
    }

    $subdir = preg_replace('/[^a-z0-9_-]/', '', strtolower($subdir));
    $relDir = 'assets/images/uploads/' . $subdir;
    $absDir = __DIR__ . '/../../' . $relDir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
        throw new RuntimeException('Yükleme klasörü oluşturulamadı: ' . $relDir);
    }
    $name = $subdir . '_' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], "$absDir/$name")) {
        throw new RuntimeException('PDF kaydedilemedi.');
    }
    return "$relDir/$name";
}
