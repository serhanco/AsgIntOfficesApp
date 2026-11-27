<?php
/**
 * Office photos (up to OFFICE_PHOTOS_MAX per office), managed in the admin office screen and shared in the API.
 *
 * Each photo is stored in three widths with the same unique key in the file name, e.g.
 *   offices_20261127_3fa9c01b22aa.webp      large  (up to 1600 px)
 *   offices-md_20261127_3fa9c01b22aa.webp   medium (up to 800 px)
 *   offices-sm_20261127_3fa9c01b22aa.webp   small  (up to 400 px)
 * so the unused-upload cleanup keeps or removes all three together (see admin/includes/upload_cleanup.php).
 *
 * The cover photo is mirrored into offices.image_url, which the office page already uses as its top image.
 * Works before database/patch_20261127_office_photos.sql is applied: office_photos_ready() is false then,
 * the API sends no photos and the admin shows the old single image field.
 */

require_once __DIR__ . '/storage.php';

const OFFICE_PHOTOS_MAX = 5;
const OFFICE_PHOTO_SIZES = ['large' => 1600, 'medium' => 800, 'small' => 400];

/** True once the office_photos table exists. */
function office_photos_ready(): bool {
    static $ready = null;
    if ($ready === null) {
        try {
            $ready = (bool)getDb()->query("SHOW TABLES LIKE 'office_photos'")->fetchColumn();
        } catch (\Throwable $e) {
            $ready = false;
        }
    }
    return $ready;
}

/**
 * Photos of the given offices as [office_id => [rows in display order]]; cover first.
 * Missing width/height (photos copied from the old image field by the patch) are read from the file once and saved.
 */
function office_photos_for(array $officeIds): array {
    $officeIds = array_values(array_unique(array_filter(array_map('intval', $officeIds))));
    if (!$officeIds || !office_photos_ready()) return [];
    $db = getDb();
    $in = implode(',', array_fill(0, count($officeIds), '?'));
    $stmt = $db->prepare("SELECT * FROM office_photos WHERE office_id IN ($in) ORDER BY office_id, is_cover DESC, sort_order, id");
    $stmt->execute($officeIds);
    $out = [];
    foreach ($stmt->fetchAll() as $p) {
        if (empty($p['width']) || empty($p['height'])) {
            $abs = upload_file_path($p['path']);
            // An image that ships with the site (not an upload), e.g. copied from the old field by the patch
            if ($abs === null && !preg_match('#^(https?:)?//#i', (string)$p['path'])) {
                $local = realpath(app_root_dir() . '/' . ltrim((string)$p['path'], '/'));
                $abs = ($local && strpos($local, app_root_dir() . '/') === 0) ? $local : null;
            }
            $info = $abs ? @getimagesize($abs) : false;
            if ($info) {
                [$p['width'], $p['height']] = [(int)$info[0], (int)$info[1]];
                try {
                    $db->prepare('UPDATE office_photos SET width = ?, height = ? WHERE id = ?')->execute([$p['width'], $p['height'], $p['id']]);
                } catch (\Throwable $e) { /* read-only is fine */ }
            }
        }
        $out[(int)$p['office_id']][] = $p;
    }
    return $out;
}

/** Alt text of a photo; when left empty: "<office display name> office". */
function office_photo_alt(array $photo, array $office): string {
    $alt = trim((string)($photo['alt_text'] ?? ''));
    return $alt !== '' ? $alt : trim(($office['display_name'] ?? $office['name'] ?? '') . ' office');
}

/** Absolute URL of a stored path (the API is read by other sites). */
function office_photo_abs_url(string $path): string {
    $url = imageUrl($path);
    if (substr($url, 0, 2) === '//') return 'https:' . $url;
    if ($url !== '' && $url[0] === '/') {
        $host = preg_replace('#^(https?://[^/]+).*$#i', '$1', rtrim(getBaseUrl(), '/'));
        return preg_match('#^https?://#i', $host) ? $host . $url : $url;
    }
    return $url;
}

/** One photo in the API shape: id, order, is_cover, alt, updated_at and sizes (url, width, height). */
function office_photo_payload(array $p, array $office, int $order): array {
    $w = (int)($p['width'] ?? 0);
    $h = (int)($p['height'] ?? 0);
    $paths = ['large' => $p['path'], 'medium' => $p['path_md'] ?: $p['path'], 'small' => $p['path_sm'] ?: $p['path']];
    $sizes = [];
    foreach (OFFICE_PHOTO_SIZES as $name => $max) {
        $own = $name === 'large' || !empty($p[$name === 'medium' ? 'path_md' : 'path_sm']);
        // A smaller copy is the large one scaled to $max px wide (never upscaled); without a copy it is the large file itself
        $scale = ($own && $w > $max) ? $max / $w : 1;
        $sizes[$name] = [
            'url' => office_photo_abs_url($paths[$name]),
            'width' => $w ? (int)round($w * $scale) : null,
            'height' => $h ? (int)round($h * $scale) : null,
        ];
    }
    $updated = $p['updated_at'] ?? $p['created_at'] ?? null;
    return [
        'id' => (int)$p['id'],
        'order' => $order,
        'is_cover' => (bool)$p['is_cover'],
        'alt' => office_photo_alt($p, $office),
        'updated_at' => $updated ? date('c', strtotime($updated)) : null,
        'sizes' => $sizes,
    ];
}

/** API fields for one office: cover_image (or null) and photos (list, empty when none). */
function office_photos_api_fields(array $office, array $photos, bool $withList = true): array {
    $list = [];
    foreach (array_values($photos) as $i => $p) $list[] = office_photo_payload($p, $office, $i + 1);
    $cover = null;
    foreach ($list as $item) {
        if ($item['is_cover']) {
            $cover = ['url' => $item['sizes']['large']['url'], 'width' => $item['sizes']['large']['width'], 'height' => $item['sizes']['large']['height'], 'alt' => $item['alt']];
            break;
        }
    }
    $fields = ['cover_image' => $cover];
    if ($withList) $fields['photos'] = $list;
    return $fields;
}
