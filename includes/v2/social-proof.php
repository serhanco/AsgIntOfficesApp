<?php
/**
 * Home page: "social proof" band under the contracted institutions card.
 * DRAFT: sample content until the real texts arrive. Patient stories stay in the patients' own languages (not translated);
 * all other static texts come from the lang files (proof_* keys).
 */

// Each story is written in the patient's own language ('lang') and is shown as is; country and topic labels belong to the story
$proofQuotes = [
    ['lang' => 'az', 'q' => 'İlk zəngdən əməliyyatdan sonrakı yoxlamalara qədər Bakı komandası hər addımda yanımda idi. Özümü bir an belə yad hiss etmədim.',
     'name' => 'Leyla M.', 'cc' => 'az', 'country' => 'Azerbaijan', 'topic' => 'Orthopaedics'],
    ['lang' => 'ka', 'q' => 'ფრენიდან თარჯიმნამდე ყველაფერს ერთი ადამიანი აგვარებდა. ჩემი ერთადერთი საქმე გამოჯანმრთელება იყო.',
     'name' => 'Giorgi K.', 'cc' => 'ge', 'country' => 'Georgia', 'topic' => 'Cardiology'],
    ['lang' => 'ro', 'q' => 'După ce m-am întors acasă, faptul că îmi puteam urmări rapoartele prin Acıbadem Online mi-a adus o liniște enormă.',
     'name' => 'Elena P.', 'cc' => 'ro', 'country' => 'Romania', 'topic' => 'Oncology'],
    ['lang' => 'en', 'q' => 'I flew in for a full check-up on a Monday and by Wednesday I had every result and a clear plan from my doctor. It was the most organised health visit I have ever had.',
     'name' => 'Michael R.', 'cc' => 'us', 'country' => 'United States', 'topic' => 'Executive check-up'],
];

$proofMetrics = [
    ['n' => 30, 'pre' => '', 'suf' => '+',  'l' => __('proof_m_langs'), 'icon' => 'ph-translate'],
    ['n' => 35, 'pre' => '', 'suf' => '+',  'l' => __('proof_m_years'), 'icon' => 'ph-medal'],
    ['n' => 24, 'pre' => '', 'suf' => '/7', 'l' => __('proof_m_support'), 'icon' => 'ph-headset'],
];

$proofSeals = [
    ['icon' => 'ph-seal-check',   'h' => 'JCI',                    'p' => __('proof_s_jci_p')],
    ['icon' => 'ph-certificate',  'h' => 'ISO 9001',               'p' => __('proof_s_iso_p')],
    ['icon' => 'ph-shield-check', 'h' => __('proof_s_lic_h'),      'p' => __('proof_s_lic_p')],
    ['icon' => 'ph-trophy',       'h' => __('proof_s_awards_h'),   'p' => __('proof_s_awards_p')],
];

// Smiling people of all ages (Unsplash License stock: atmosphere only, never paired with a quote)
$proofPeople = ['assets/images/proof/people-1.webp', 'assets/images/proof/people-2.webp', 'assets/images/proof/people-3.webp',
                'assets/images/proof/people-4.webp', 'assets/images/proof/people-5.webp'];
$proofPeople = array_values(array_filter($proofPeople, fn($f) => is_file(__DIR__ . '/../../' . $f)));
?>
<section class="proof" aria-labelledby="proof-h">
    <div class="wrap">
        <div class="proof__head reveal">
            <p class="eyebrow justify-center"><?= e(__('proof_eyebrow')) ?></p>
            <h2 id="proof-h" class="proof__title"><?= e(__('proof_title_a')) ?> <span><?= e(__('proof_title_b')) ?></span></h2>
            <p class="proof__lead"><?= e(__('proof_lead')) ?></p>
        </div>

        <?php if ($proofPeople): ?>
        <div class="proof__people reveal" aria-hidden="true">
            <?php foreach ($proofPeople as $k => $f): ?>
            <span class="proof__person" style="--k:<?= $k ?>"><img src="<?= asset($f) ?>" alt="" width="480" height="600" loading="lazy" decoding="async"></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="proof__grid">
            <!-- Rotating patient stories -->
            <figure class="proof__stage reveal" data-proof>
                <i class="ph-fill ph-quotes proof__mark" aria-hidden="true"></i>
                <div class="proof__slides" aria-live="polite">
                    <?php foreach ($proofQuotes as $k => $t):
                        $ini = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', $t['name']), 0, 2))); ?>
                    <div class="proof__slide<?= $k === 0 ? ' is-on' : '' ?>" lang="<?= e($t['lang']) ?>" dir="ltr" data-slide<?= $k === 0 ? '' : ' aria-hidden="true"' ?>>
                        <blockquote class="proof__quote"><?= e($t['q']) ?></blockquote>
                        <figcaption class="proof__who">
                            <span class="proof__avatar" aria-hidden="true"><?= e($ini) ?></span>
                            <span class="min-w-0">
                                <b class="proof__name"><?= e($t['name']) ?></b>
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
                    <div class="proof__bars" role="tablist" aria-label="<?= e(__('proof_stories')) ?>">
                        <?php foreach ($proofQuotes as $k => $t): ?>
                        <button type="button" class="proof__bar<?= $k === 0 ? ' is-on' : '' ?>" role="tab" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>" aria-label="<?= $k + 1 ?> / <?= count($proofQuotes) ?>" data-go="<?= $k ?>"><i></i></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="proof__arrows">
                        <button type="button" class="proof__arrow" data-step="-1" aria-label="<?= e(__('proof_prev')) ?>"><i class="ph ph-arrow-left" aria-hidden="true"></i></button>
                        <button type="button" class="proof__arrow" data-step="1" aria-label="<?= e(__('proof_next')) ?>"><i class="ph ph-arrow-right" aria-hidden="true"></i></button>
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
