<?php
/**
 * New design, home page: "social proof" band under the contracted institutions card.
 * DRAFT for testing: one language only (Turkish) and sample content until the real texts arrive.
 * Edit the three lists below; the markup needs no change. Set $proofDraft = false once the content is real.
 */
$proofDraft = true;
$proofLang  = 'tr'; // the texts below are written in this language; other page languages show them as is

$proofQuotes = [
    ['q' => 'Bakü ofisindeki ekip ilk görüşmeden ameliyat sonrası kontrollere kadar her adımda yanımdaydı. Kendimi bir an bile yabancı hissetmedim.',
     'name' => 'Leyla M.', 'cc' => 'az', 'country' => 'Azerbaycan', 'topic' => 'Ortopedi'],
    ['q' => 'Uçuştan tercümana kadar her şey tek bir kişi üzerinden planlandı. Bana sadece iyileşmek kaldı.',
     'name' => 'Giorgi K.', 'cc' => 'ge', 'country' => 'Gürcistan', 'topic' => 'Kardiyoloji'],
    ['q' => 'Eve döndükten sonra raporlarımı Acıbadem Online üzerinden takip edebilmek içimi çok rahatlattı.',
     'name' => 'Elena P.', 'cc' => 'ro', 'country' => 'Romanya', 'topic' => 'Onkoloji'],
];

$proofMetrics = [
    ['n' => 98, 'pre' => '%', 'suf' => '',  'l' => 'Hasta memnuniyeti',            'icon' => 'ph-heart'],
    ['n' => 35, 'pre' => '',  'suf' => '+', 'l' => 'Yıllık sağlık deneyimi',       'icon' => 'ph-medal'],
    ['n' => 24, 'pre' => '',  'suf' => '/7', 'l' => 'Uluslararası hasta desteği', 'icon' => 'ph-headset'],
];

$proofSeals = [
    ['icon' => 'ph-seal-check',   'h' => 'JCI',              'p' => 'Uluslararası akreditasyon'],
    ['icon' => 'ph-certificate',  'h' => 'ISO 9001',         'p' => 'Kalite yönetimi'],
    ['icon' => 'ph-shield-check', 'h' => 'Yetki Belgesi',    'p' => 'Sağlık turizmi'],
    ['icon' => 'ph-trophy',       'h' => 'Ödüller',          'p' => 'Hasta deneyimi'],
];
?>
<section class="proof" aria-labelledby="proof-h"<?= $proofLang !== ($GLOBALS['current_lang'] ?? '') ? ' lang="' . $proofLang . '" dir="ltr"' : '' ?>>
    <div class="wrap">
        <div class="proof__head reveal">
            <?php if ($proofDraft): ?><span class="proof__draft">Taslak · örnek içerik</span><?php endif; ?>
            <p class="eyebrow justify-center">Hastalarımız anlatıyor</p>
            <h2 id="proof-h" class="proof__title">Sağlığınız için <span>doğru adres.</span></h2>
            <p class="proof__lead">Dünyanın dört bir yanından hastalar, ilk görüşmeden tedavi sonrası takibe kadar Acıbadem'in yanlarında olduğunu biliyor.</p>
        </div>

        <div class="proof__grid">
            <!-- Rotating patient stories -->
            <figure class="proof__stage reveal" data-proof>
                <i class="ph-fill ph-quotes proof__mark" aria-hidden="true"></i>
                <div class="proof__slides" aria-live="polite">
                    <?php foreach ($proofQuotes as $k => $t):
                        $ini = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', $t['name']), 0, 2))); ?>
                    <div class="proof__slide<?= $k === 0 ? ' is-on' : '' ?>" data-slide<?= $k === 0 ? '' : ' aria-hidden="true"' ?>>
                        <blockquote class="proof__quote"><?= e($t['q']) ?></blockquote>
                        <figcaption class="proof__who">
                            <span class="proof__avatar" aria-hidden="true"><?= e($ini) ?></span>
                            <span class="min-w-0">
                                <b class="proof__name"><?= e($t['name']) ?><?php if ($proofDraft): ?> <em>örnek</em><?php endif; ?></b>
                                <span class="proof__from">
                                    <img src="<?= e(getFlagUrl($t['cc'])) ?>" alt="" width="20" height="20" loading="lazy" decoding="async">
                                    <?= e($t['country']) ?> · <?= e($t['topic']) ?>
                                </span>
                            </span>
                        </figcaption>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="proof__nav">
                    <div class="proof__bars" role="tablist" aria-label="Hasta yorumları">
                        <?php foreach ($proofQuotes as $k => $t): ?>
                        <button type="button" class="proof__bar<?= $k === 0 ? ' is-on' : '' ?>" role="tab" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>" aria-label="<?= $k + 1 ?> / <?= count($proofQuotes) ?>" data-go="<?= $k ?>"><i></i></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="proof__arrows">
                        <button type="button" class="proof__arrow" data-step="-1" aria-label="Önceki"><i class="ph ph-arrow-left" aria-hidden="true"></i></button>
                        <button type="button" class="proof__arrow" data-step="1" aria-label="Sonraki"><i class="ph ph-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </div>
            </figure>

            <!-- Numbers -->
            <dl class="proof__metrics">
                <?php foreach ($proofMetrics as $k => $m): ?>
                <div class="proof__metric reveal" style="--d:<?= .08 * ($k + 1) ?>s">
                    <span class="proof__metric-icon" aria-hidden="true"><i class="ph-fill <?= $m['icon'] ?>"></i></span>
                    <dd class="proof__num" dir="ltr"><?= e($m['pre']) ?><span data-count="<?= (int)$m['n'] ?>"><?= (int)$m['n'] ?></span><?= e($m['suf']) ?></dd>
                    <dt class="proof__label"><?= e($m['l']) ?></dt>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>

        <!-- Accreditations / seals -->
        <ul class="proof__seals reveal">
            <?php foreach ($proofSeals as $s): ?>
            <li class="proof__seal">
                <span class="proof__seal-ring" aria-hidden="true"><i class="ph-fill <?= $s['icon'] ?>"></i></span>
                <span><b><?= e($s['h']) ?></b><small><?= e($s['p']) ?></small></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<script>
(function () {
    var box = document.querySelector('[data-proof]');
    if (!box) return;
    var slides = box.querySelectorAll('[data-slide]'), bars = box.querySelectorAll('[data-go]');
    var cur = 0, timer = null, n = slides.length;
    var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function show(i) {
        cur = (i + n) % n;
        slides.forEach(function (s, k) { s.classList.toggle('is-on', k === cur); s.setAttribute('aria-hidden', k === cur ? 'false' : 'true'); });
        bars.forEach(function (b, k) { b.classList.toggle('is-on', k === cur); b.setAttribute('aria-selected', k === cur ? 'true' : 'false'); });
    }
    function play() { if (still || n < 2) return; stop(); box.classList.add('is-playing'); timer = setInterval(function () { show(cur + 1); }, 7000); }
    function stop() { clearInterval(timer); box.classList.remove('is-playing'); }
    bars.forEach(function (b) { b.addEventListener('click', function () { show(+b.dataset.go); play(); }); });
    box.querySelectorAll('[data-step]').forEach(function (b) {
        b.addEventListener('click', function () { var s = +b.dataset.step; if (getComputedStyle(box).direction === 'rtl') s = -s; show(cur + s); play(); });
    });
    box.addEventListener('mouseenter', stop);
    box.addEventListener('mouseleave', play);
    box.addEventListener('focusin', stop);
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) { en[0].isIntersecting ? play() : stop(); }, { threshold: .4 }).observe(box);
    } else { play(); }
})();
</script>
