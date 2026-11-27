<?php
/**
 * Office photos: actions sent by the "Fotoğraflar" box on the office edit screen (admins only, POST + CSRF).
 * Every answer is JSON: {ok: true, photos: [...]} with the office's current photos, or {ok: false, error: "..."}.
 *   action=upload  file=photo                 add a photo (at most OFFICE_PHOTOS_MAX); the first one becomes the cover
 *   action=delete  photo_id                   remove a photo (the file is removed later by the unused-upload cleanup)
 *   action=cover   photo_id                   make a photo the cover
 *   action=alt     photo_id, alt              save the English alt text
 *   action=order   ids[]                      save the order
 * After each change the cover is copied into offices.image_url (the office page's top image).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/../includes/office_photos.php';
require_admin_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function photos_reply(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Photos of one office as the admin box needs them. */
function photos_admin_list(int $officeId): array {
    $rows = office_photos_for([$officeId])[$officeId] ?? [];
    return array_map(fn($p) => [
        'id' => (int)$p['id'],
        'thumb' => admin_image_src($p['path_sm'] ?: $p['path']),
        'large' => admin_image_src($p['path']),
        'width' => (int)$p['width'],
        'height' => (int)$p['height'],
        'alt' => (string)($p['alt_text'] ?? ''),
        'is_cover' => (bool)$p['is_cover'],
    ], $rows);
}

/** Keep exactly one cover when there are photos, renumber the order and copy the cover into offices.image_url. */
function photos_sync(PDO $db, int $officeId): void {
    $stmt = $db->prepare('SELECT id, path, is_cover FROM office_photos WHERE office_id = ? ORDER BY is_cover DESC, sort_order, id');
    $stmt->execute([$officeId]);
    $rows = $stmt->fetchAll();
    $coverId = $rows ? (int)$rows[0]['id'] : 0;
    $db->prepare('UPDATE office_photos SET is_cover = (id = ?) WHERE office_id = ?')->execute([$coverId, $officeId]);
    $db->prepare('UPDATE offices SET image_url = ? WHERE id = ?')->execute([$rows ? $rows[0]['path'] : null, $officeId]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') photos_reply(['ok' => false, 'error' => 'Geçersiz istek.'], 405);
if (!office_photos_ready()) photos_reply(['ok' => false, 'error' => 'Önce Güncellemeler ekranından veritabanı güncellemesini uygulayın.'], 409);

$db = getDb();
$officeId = (int)($_POST['office_id'] ?? 0);
$stmt = $db->prepare('SELECT id FROM offices WHERE id = ?');
$stmt->execute([$officeId]);
if (!$stmt->fetchColumn()) photos_reply(['ok' => false, 'error' => 'Ofis bulunamadı.'], 404);

/** The photo with this id, only if it belongs to this office. */
$photoId = (int)($_POST['photo_id'] ?? 0);
$ownPhoto = function () use ($db, $officeId, $photoId): array {
    $s = $db->prepare('SELECT * FROM office_photos WHERE id = ? AND office_id = ?');
    $s->execute([$photoId, $officeId]);
    $row = $s->fetch();
    if (!$row) photos_reply(['ok' => false, 'error' => 'Fotoğraf bulunamadı.'], 404);
    return $row;
};

try {
    switch ((string)($_POST['action'] ?? '')) {
        case 'upload':
            $count = $db->prepare('SELECT COUNT(*) FROM office_photos WHERE office_id = ?');
            $count->execute([$officeId]);
            if ((int)$count->fetchColumn() >= OFFICE_PHOTOS_MAX) {
                photos_reply(['ok' => false, 'error' => 'Bir ofise en fazla ' . OFFICE_PHOTOS_MAX . ' fotoğraf eklenebilir. Yeni fotoğraf için önce birini silin.'], 422);
            }
            $path = handle_image_upload('photo', 'offices', OFFICE_PHOTO_SIZES['large']);
            if ($path === null) photos_reply(['ok' => false, 'error' => 'Dosya gelmedi.'], 422);
            $md = upload_make_variant($path, 'md', OFFICE_PHOTO_SIZES['medium']);
            $sm = upload_make_variant($path, 'sm', OFFICE_PHOTO_SIZES['small']);
            $abs = upload_file_path($path);
            $info = $abs ? @getimagesize($abs) : false;
            $next = $db->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1, COUNT(*) FROM office_photos WHERE office_id = ?');
            $next->execute([$officeId]);
            [$order, $existing] = $next->fetch(PDO::FETCH_NUM);
            $db->prepare('INSERT INTO office_photos (office_id, path, path_md, path_sm, width, height, sort_order, is_cover, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$officeId, $path, $md, $sm, $info ? $info[0] : null, $info ? $info[1] : null, (int)$order, (int)$existing === 0 ? 1 : 0, (int)($_SESSION['admin_user_id'] ?? 0) ?: null]);
            break;

        case 'delete':
            $ownPhoto();
            $db->prepare('DELETE FROM office_photos WHERE id = ? AND office_id = ?')->execute([$photoId, $officeId]);
            break;

        case 'cover':
            $ownPhoto();
            $db->prepare('UPDATE office_photos SET is_cover = (id = ?) WHERE office_id = ?')->execute([$photoId, $officeId]);
            break;

        case 'alt':
            $ownPhoto();
            $alt = trim(preg_replace('/\s+/u', ' ', (string)($_POST['alt'] ?? '')));
            $alt = function_exists('mb_substr') ? mb_substr($alt, 0, 200) : substr($alt, 0, 200);
            $db->prepare('UPDATE office_photos SET alt_text = ? WHERE id = ? AND office_id = ?')->execute([$alt !== '' ? $alt : null, $photoId, $officeId]);
            break;

        case 'order':
            $ids = array_map('intval', (array)($_POST['ids'] ?? []));
            $set = $db->prepare('UPDATE office_photos SET sort_order = ? WHERE id = ? AND office_id = ?');
            foreach (array_values($ids) as $i => $id) $set->execute([$i, $id, $officeId]);
            break;

        default:
            photos_reply(['ok' => false, 'error' => 'Bilinmeyen işlem.'], 400);
    }
    photos_sync($db, $officeId);
} catch (RuntimeException $e) {
    photos_reply(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('office_photos: ' . $e->getMessage());
    photos_reply(['ok' => false, 'error' => 'Kaydedilemedi. Lütfen tekrar deneyin.'], 500);
}

photos_reply(['ok' => true, 'photos' => photos_admin_list($officeId)]);
