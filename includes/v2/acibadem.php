<?php
/**
 * Home page: "about Acıbadem" section (text + three pillars, photo with number badges).
 * Numbers: offices/countries come from the DB, 'countries served' from Ayarlar (stat_served); hospitals and years are Ayarlar fields (acb_hospitals, acb_years) and their badge
 * is hidden while the field is empty. The world map button is an Ayarlar switch (acb_map_btn), off by default.
 * Shows the numbers that used to sit in the hero as one glass strip on the photo. Expects $countryCount and $officeCount from the home page.
 */
$acbNum = function (string $key): string {
    $v = trim(siteSetting($key));
    return preg_match('/^\d{1,5}\+?$/', $v) ? $v : '';
};
// Number tiles: Acıbadem-wide numbers from Ayarlar first (hidden while empty), then the former hero stats (DB / Ayarlar)
$served = statServed();
$acbStats = array_values(array_filter([
    ['n' => $acbNum('acb_hospitals'), 'l' => __('acb_b_hospitals')],
    ['n' => $acbNum('acb_years'),     'l' => __('acb_b_years')],
    ['n' => $served[0] . $served[1],   'l' => __('stat_served')],
    ['n' => (string)$officeCount,     'l' => __('stat_offices')],
    ['n' => (string)$countryCount,    'l' => __('stat_countries')],
    ['n' => '24/7',                   'l' => __('stat_support')],
], fn($b) => $b['n'] !== '' && $b['n'] !== '0'));
$acbPillars = [
    ['icon' => 'ph-buildings',  'h' => __('acb_p1_h'), 'p' => __('acb_p1_p')],
    ['icon' => 'ph-seal-check', 'h' => __('acb_p2_h'), 'p' => __('acb_p2_p')],
    ['icon' => 'ph-translate',  'h' => __('acb_p3_h'), 'p' => __('acb_p3_p')],
];
?>
<section data-track-area="home_acibadem" class="acb" aria-labelledby="acb-h">
    <div class="wrap">
        <div class="acb__grid">
            <div class="reveal">
                <p class="eyebrow"><?= e(__('acb_eyebrow')) ?></p>
                <h2 id="acb-h" class="acb__title"><?= e(__('acb_title_a')) ?> <span><?= e(__('acb_title_b')) ?></span></h2>
                <p class="acb__lead"><?= e(__('acb_lead')) ?></p>
                <ul class="acb__pillars">
                    <?php foreach ($acbPillars as $p): ?>
                    <li class="acb__pillar">
                        <span class="acb__ico" aria-hidden="true"><i class="ph-fill <?= $p['icon'] ?>"></i></span>
                        <span class="min-w-0"><b><?= e($p['h']) ?></b><small><?= e($p['p']) ?></small></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (siteSetting('acb_map_btn') === '1'): ?>
                <div class="acb__cta">
                    <a href="<?= getBaseUrl() ?>/map" class="btn btn--primary btn--lg"><i class="ph-fill ph-globe-hemisphere-west" aria-hidden="true"></i> <?= e(__('acb_map_btn')) ?> <i class="ph ph-arrow-right arrow" aria-hidden="true"></i></a>
                </div>
                <?php endif; ?>
            </div>
            <div class="acb__media reveal" style="--d:.12s">
                <img src="<?= asset('assets/images/office-hero.webp') ?>" alt="" width="1600" height="892" loading="lazy" decoding="async">
                <span class="acb__seal"><i class="ph-fill ph-seal-check" aria-hidden="true"></i><?= e(__('acb_seal')) ?></span>
                <dl class="acb__stats<?= count($acbStats) > 4 ? ' acb__stats--many' : '' ?>" style="--n:<?= count($acbStats) ?>">
                    <?php foreach ($acbStats as $t):
                        $cnt = preg_match('/^(\d+)(\+?)$/', $t['n'], $m) ? ' data-count="' . $m[1] . '"' . ($m[2] ? ' data-suffix="+"' : '') : ''; ?>
                    <div class="acb__stat">
                        <dt class="sr-only"><?= e($t['l']) ?></dt>
                        <dd><b><bdi dir="ltr"<?= $cnt ?>><?= e($t['n']) ?></bdi></b><span aria-hidden="true"><?= e($t['l']) ?></span></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </div>
</section>
