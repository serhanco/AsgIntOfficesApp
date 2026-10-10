<?php
/**
 * Admin Image Upload Helper
 * Acıbadem International Offices App
 *
 * Validates an uploaded image (JPG, PNG, WEBP), re-encodes it with GD
 * (which also strips anything hidden inside the file), scales it down to
 * $maxWidth and saves it under assets/images/uploads/<subdir>/.
 *
 * Returns the stored path relative to the app root
 * (e.g. "assets/images/uploads/offices/office_6523ab.webp"),
 * or null when no file was sent. Throws RuntimeException with a
 * user-facing (Turkish) message on invalid input.
 */

const UPLOAD_MAX_BYTES = 8 * 1024 * 1024; // 8 MB

function handle_image_upload(string $field, string $subdir, int $maxWidth): ?string {
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Görsel yüklenemedi (dosya çok büyük olabilir).');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Görsel en fazla 8 MB olabilir.');
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
    if (extension_loaded('gd')) {
        $loaders = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        $loader = $loaders[$info[2]];
        $src = function_exists($loader) ? @$loader($file['tmp_name']) : false;
        if (!$src) {
            throw new RuntimeException('Görsel okunamadı. Farklı bir dosya deneyin.');
        }

        if (!imageistruecolor($src)) {
            imagepalettetotruecolor($src); // WEBP encoder needs truecolor
        }

        [$w, $h] = [imagesx($src), imagesy($src)];
        if ($w > $maxWidth) {
            $newH = (int)round($h * $maxWidth / $w);
            $dst = imagecreatetruecolor($maxWidth, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $newH, $w, $h);
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
