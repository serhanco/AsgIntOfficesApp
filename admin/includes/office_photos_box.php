<?php
/**
 * "Fotoğraflar" box of the office edit screen (included by admin/office_edit.php when office_photos_ready()).
 * Talks to admin/office_photos.php. Big phone photos are shrunk in the browser first, so the server's upload
 * limit (often 2 MB) is no obstacle. Needs: $is_edit, $id, $office.
 */
$photoRows = $is_edit ? (office_photos_for([(int)$id])[(int)$id] ?? []) : [];
$photoData = array_map(fn($p) => [
    'id' => (int)$p['id'],
    'thumb' => admin_image_src($p['path_sm'] ?: $p['path']),
    'large' => admin_image_src($p['path']),
    'width' => (int)$p['width'],
    'height' => (int)$p['height'],
    'alt' => (string)($p['alt_text'] ?? ''),
    'is_cover' => (bool)$p['is_cover'],
], $photoRows);
$altFallback = trim(($office['display_name'] ?? '') . ' office');
?>
<section id="fotograflar" class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b pb-2 mb-4">
        <h3 class="text-lg font-medium text-gray-900">Fotoğraflar</h3>
        <span class="text-sm text-gray-500" data-ph-count></span>
    </div>

    <?php if (!$is_edit): ?>
        <p class="text-sm text-gray-500">Fotoğraf eklemek için önce ofisi kaydedin.</p>
    <?php else: ?>
    <p class="text-sm text-gray-600">
        En fazla <?= OFFICE_PHOTOS_MAX ?> fotoğraf. <strong>Kapak</strong> fotoğrafı ofis sayfasının üst görseli olur ve her zaman ilk sırada durur.
        Fotoğraflar ve İngilizce açıklamaları (alt metin) ofisler API'sinde de paylaşılır.
        Önerilen: yatay, 1600 px genişlik ya da daha büyük. Hasta içeren fotoğraf yüklemeyin; konum gibi gizli bilgiler otomatik silinir.
    </p>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <label class="inline-flex items-center gap-2 cursor-pointer rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" data-ph-add>
            <span>+ Fotoğraf ekle</span>
            <input type="file" accept=".jpg,.jpeg,.png,.webp" multiple class="sr-only" data-ph-input>
        </label>
        <span class="text-sm text-gray-600" data-ph-status aria-live="polite"></span>
    </div>
    <p class="mt-2 text-sm text-red-600" data-ph-error aria-live="assertive"></p>

    <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" data-ph-list></ul>
    <p class="mt-2 text-sm text-gray-500" data-ph-empty>Henüz fotoğraf yok; ofis sayfasında varsayılan görsel gösteriliyor.</p>

    <script>
    (function () {
        var MAX = <?= (int)OFFICE_PHOTOS_MAX ?>;
        var LIMIT = <?= (int)upload_limit_bytes() ?>;
        var OFFICE = <?= (int)$id ?>;
        var CSRF = <?= json_encode(csrf_token()) ?>;
        var FALLBACK_ALT = <?= json_encode($altFallback, JSON_UNESCAPED_UNICODE) ?>;
        var photos = <?= json_encode($photoData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var box = document.getElementById('fotograflar');
        var $ = function (s) { return box.querySelector(s); };
        var list = $('[data-ph-list]'), input = $('[data-ph-input]'), statusEl = $('[data-ph-status]'), errEl = $('[data-ph-error]');

        function send(fields, file) {
            var fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('office_id', OFFICE);
            Object.keys(fields).forEach(function (k) {
                [].concat(fields[k]).forEach(function (v) { fd.append(Array.isArray(fields[k]) ? k + '[]' : k, v); });
            });
            if (file) fd.append('photo', file, file.name);
            return fetch('office_photos.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.text(); })
                .then(function (t) {
                    var d; try { d = JSON.parse(t); } catch (e) { throw new Error(t && t.length < 300 ? t : 'Sunucu yanıtı okunamadı. Sayfayı yenileyip tekrar deneyin.'); }
                    if (!d.ok) throw new Error(d.error || 'İşlem yapılamadı.');
                    photos = d.photos; render();
                });
        }
        function fail(e) { errEl.textContent = e.message; }

        // Big photos are scaled down to 2000 px and saved as JPEG in the browser, so they pass the upload limit
        function shrink(file) {
            return new Promise(function (resolve) {
                if (!window.createImageBitmap || !window.HTMLCanvasElement) return resolve(file);
                createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return createImageBitmap(file); }).then(function (bmp) {
                    var big = Math.max(bmp.width, bmp.height) > 2400;
                    if (!big && file.size <= LIMIT * 0.95) return resolve(file);
                    var scale = Math.min(1, 2000 / Math.max(bmp.width, bmp.height));
                    var c = document.createElement('canvas');
                    c.width = Math.round(bmp.width * scale); c.height = Math.round(bmp.height * scale);
                    var g = c.getContext('2d');
                    g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
                    g.drawImage(bmp, 0, 0, c.width, c.height);
                    var tryQ = function (q) {
                        c.toBlob(function (b) {
                            if (!b) return resolve(file);
                            if (b.size > LIMIT * 0.95 && q > 0.5) return tryQ(q - 0.15);
                            resolve(new File([b], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
                        }, 'image/jpeg', q);
                    };
                    tryQ(0.9);
                }).catch(function () { resolve(file); });
            });
        }

        input.addEventListener('change', function () {
            var files = [].slice.call(input.files || []);
            input.value = '';
            errEl.textContent = '';
            var room = MAX - photos.length;
            if (files.length > room) {
                errEl.textContent = room > 0 ? 'En fazla ' + MAX + ' fotoğraf olabilir; ilk ' + room + ' dosya yükleniyor.' : 'En fazla ' + MAX + ' fotoğraf olabilir. Yeni fotoğraf için önce birini silin.';
                files = files.slice(0, Math.max(0, room));
            }
            var i = 0, okCount = 0;
            (function next() {
                if (i >= files.length) { statusEl.textContent = okCount ? okCount + ' fotoğraf eklendi.' : ''; return; }
                statusEl.textContent = 'Yükleniyor ' + (i + 1) + '/' + files.length + '…';
                var f = files[i++];
                shrink(f).then(function (small) {
                    if (small.size > LIMIT) throw new Error(f.name + ': dosya çok büyük, küçültülemedi.');
                    return send({ action: 'upload' }, small);
                }).then(function () { okCount++; }).catch(function (e) { errEl.textContent = f.name + ': ' + e.message.replace(f.name + ': ', ''); }).then(next);
            })();
        });

        function btn(label, title, action, cls) {
            var b = document.createElement('button');
            b.type = 'button'; b.textContent = label; b.title = title; b.setAttribute('aria-label', title);
            b.className = 'rounded-md border px-2 py-1 text-xs font-medium ' + (cls || 'border-gray-300 text-gray-700 hover:bg-gray-50');
            b.addEventListener('click', function () { errEl.textContent = ''; action(); });
            return b;
        }
        function move(idx, dir) {
            var ids = photos.map(function (p) { return p.id; });
            var j = idx + dir;
            var t = ids[idx]; ids[idx] = ids[j]; ids[j] = t;
            send({ action: 'order', ids: ids }).catch(fail);
        }

        function render() {
            list.innerHTML = '';
            photos.forEach(function (p, idx) {
                var li = document.createElement('li');
                li.className = 'rounded-lg border ' + (p.is_cover ? 'border-blue-500 ring-1 ring-blue-500' : 'border-gray-200') + ' overflow-hidden bg-gray-50';
                var a = document.createElement('a');
                a.href = p.large; a.target = '_blank'; a.rel = 'noopener'; a.className = 'block relative aspect-video bg-gray-200';
                var img = document.createElement('img');
                img.src = p.thumb; img.alt = ''; img.loading = 'lazy'; img.className = 'absolute inset-0 w-full h-full object-cover';
                a.appendChild(img);
                if (p.is_cover) {
                    var tag = document.createElement('span');
                    tag.textContent = '★ Kapak'; tag.className = 'absolute top-2 start-2 rounded bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white';
                    a.appendChild(tag);
                }
                li.appendChild(a);

                var body = document.createElement('div'); body.className = 'p-3 space-y-2';
                var lab = document.createElement('label'); lab.className = 'block text-xs font-medium text-gray-700'; lab.textContent = 'Açıklama (İngilizce, alt metin)';
                var alt = document.createElement('input');
                alt.type = 'text'; alt.maxLength = 200; alt.value = p.alt; alt.placeholder = FALLBACK_ALT; alt.dir = 'ltr'; alt.lang = 'en';
                alt.className = 'mt-1 block w-full rounded-md border border-gray-300 px-2 py-1 text-sm';
                alt.addEventListener('change', function () { send({ action: 'alt', photo_id: p.id, alt: alt.value }).then(function () { statusEl.textContent = 'Açıklama kaydedildi.'; }).catch(fail); });
                lab.appendChild(alt); body.appendChild(lab);

                var row = document.createElement('div'); row.className = 'flex flex-wrap items-center gap-2';
                if (!p.is_cover) {
                    row.appendChild(btn('★ Kapak yap', 'Kapak yap', function () { send({ action: 'cover', photo_id: p.id }).catch(fail); }));
                    if (idx > 1) row.appendChild(btn('↑', 'Öne al', function () { move(idx, -1); }));
                    if (idx < photos.length - 1) row.appendChild(btn('↓', 'Geriye al', function () { move(idx, 1); }));
                }
                if (p.width) {
                    var dim = document.createElement('span'); dim.className = 'text-xs text-gray-500'; dim.textContent = p.width + '×' + p.height;
                    row.appendChild(dim);
                }
                var del = btn('Sil', 'Fotoğrafı sil', function () {
                    if (confirm('Bu fotoğraf silinsin mi?' + (p.is_cover ? ' Kapak bir sonraki fotoğrafa geçer.' : ''))) send({ action: 'delete', photo_id: p.id }).catch(fail);
                }, 'border-red-200 text-red-700 hover:bg-red-50 ms-auto');
                row.appendChild(del);
                body.appendChild(row);
                li.appendChild(body);
                list.appendChild(li);
            });
            $('[data-ph-count]').textContent = photos.length + '/' + MAX;
            $('[data-ph-empty]').hidden = photos.length > 0;
            $('[data-ph-add]').classList.toggle('opacity-50', photos.length >= MAX);
            $('[data-ph-add]').classList.toggle('pointer-events-none', photos.length >= MAX);
        }
        render();
    })();
    </script>
    <?php endif; ?>
</section>
