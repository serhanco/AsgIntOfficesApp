<?php
/** New design: one office. Data ($office, $team, $activities, $relatedOffices …) comes from office.php. */
$heroImg = imageUrl($office['image_url'] ?? '', getBaseUrl() . '/assets/images/office-hero.webp');
$extraHead = '<link rel="preload" as="image" href="' . e($heroImg) . '" fetchpriority="high">';
require __DIR__ . '/../header.php';

$tel   = !empty($office['phone']) ? 'tel:' . preg_replace('/[^0-9+]/', '', $office['phone']) : '';
$wa    = !empty($office['phone']) ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $office['phone']) : '';
$mail  = !empty($office['email']) ? 'mailto:' . $office['email'] : '';
$route = 'https://www.google.com/maps/dir/?api=1&destination=' . $office['latitude'] . ',' . $office['longitude'];

$actions = array_values(array_filter([
    $tel  ? ['href' => $tel,   'icon' => 'ph-phone',            'label' => __('office_cta_call'),  'ext' => false, 'cls' => ''] : null,
    $wa   ? ['href' => $wa,    'icon' => 'ph-whatsapp-logo',    'label' => __('js_whatsapp'),      'ext' => true,  'cls' => 'is-wa'] : null,
    $mail ? ['href' => $mail,  'icon' => 'ph-envelope-simple',  'label' => __('office_cta_email'), 'ext' => false, 'cls' => ''] : null,
            ['href' => $route, 'icon' => 'ph-navigation-arrow', 'label' => __('office_cta_route'), 'ext' => true,  'cls' => ''],
]));

// Event cards: icon and colour per tag
$tagStyle = [
    'act_tag_doctor'       => ['icon' => 'ph-stethoscope',        'chip' => 'from-aqua to-navy',          'text' => 'text-azure'],
    'act_tag_presentation' => ['icon' => 'ph-presentation-chart', 'chip' => 'from-violet-400 to-violet-700', 'text' => 'text-violet-700'],
    'act_tag_exhibition'   => ['icon' => 'ph-handshake',          'chip' => 'from-emerald-400 to-emerald-700', 'text' => 'text-emerald-700'],
];
?>

<!-- Hero -->
<section class="hero">
    <img src="<?= e($heroImg) ?>" alt="" class="hero__img" fetchpriority="high" decoding="async">
    <div class="hero__shade"></div>
    <div class="wrap wrap--narrow relative pt-24 pb-10 sm:pt-32 md:pt-40 md:pb-14">
        <a href="<?= getBaseUrl() ?>/offices" class="rise inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-3 py-1.5 text-sm font-semibold backdrop-blur-md hover:bg-white/20 transition-colors" style="-webkit-backdrop-filter: blur(12px)">
            <?= getFlagImg($office['country'], $office['country_code']) ?>
            <bdi><?= e($office['country']) ?></bdi>
        </a>
        <h1 class="rise mt-4 text-4xl sm:text-5xl md:text-6xl" style="--d:.08s"><bdi><?= e($office['display_name']) ?></bdi></h1>
        <p class="hero__lead rise mt-3 flex items-start gap-2 text-sm sm:text-base max-w-2xl" style="--d:.14s">
            <i class="ph-fill ph-map-pin text-aqua mt-0.5" aria-hidden="true"></i>
            <bdi><?= e($office['address']) ?></bdi>
        </p>

        <div class="rise mt-8 grid grid-cols-2 sm:flex sm:flex-wrap gap-3" style="--d:.2s" data-dock-anchor>
            <?php foreach ($actions as $i => $a): ?>
            <a href="<?= e($a['href']) ?>"<?= $a['ext'] ? ' target="_blank" rel="noopener"' : '' ?>
               class="btn <?= $i === 0 ? 'btn--primary' : ($a['cls'] === 'is-wa' ? 'btn--ghost !border-wa/60 hover:!bg-wa hover:!border-wa' : 'btn--ghost') ?>">
                <i class="ph-fill <?= $a['icon'] ?>" aria-hidden="true"></i><?= $a['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="wrap wrap--narrow py-10 sm:py-14 space-y-6 sm:space-y-8">

    <?php if ($hasTeam || $hasActivities): ?>
    <div class="grid gap-6 sm:gap-8 <?= $gridCols ?>">
        <?php if ($hasActivities): ?>
        <section class="card p-6 sm:p-8 reveal">
            <div class="flex items-center justify-between gap-4 mb-6">
                <h2 class="text-xl font-extrabold text-navy"><?= __('office_activities_h', '<bdi>' . e($office['country']) . '</bdi>') ?></h2>
                <span class="count-pill"><?= count($activities) ?></span>
            </div>
            <div class="space-y-3">
                <?php foreach ($activities as $act):
                    $st = $tagStyle[$act['tag_key']] ?? $tagStyle['act_tag_doctor'];
                    // Events are published untranslated in the office's local language:
                    // the card takes the direction of its own text.
                    $actDir = textDir(($act['title'] ?? '') . ' ' . ($act['description'] ?? ''));
                ?>
                <div dir="<?= $actDir ?>" class="group flex gap-4 p-4 rounded-2xl border border-line bg-white hover:border-transparent hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-300">
                    <span class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $st['chip'] ?> text-white flex items-center justify-center flex-shrink-0 shadow-md transition-transform duration-300 group-hover:scale-105 group-hover:-rotate-6">
                        <i class="ph-fill <?= $st['icon'] ?> text-2xl" aria-hidden="true"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <span lang="<?= e(langHtml()) ?>" dir="<?= langDir() ?>" class="text-[0.7rem] font-bold uppercase tracking-wider <?= $st['text'] ?>"><?= __($act['tag_key']) ?></span>
                        <p class="event-text mt-0.5 text-[0.95rem] font-bold text-ink leading-snug"><?= e($act['title']) ?></p>
                        <?php if (!empty($act['description'])): ?>
                        <p class="event-text mt-1 text-sm text-muted"><?= e($act['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($hasTeam): ?>
        <section class="card p-6 sm:p-8 reveal" style="--d:.08s">
            <div class="flex items-center justify-between gap-4 mb-6">
                <h2 class="text-xl font-extrabold text-navy"><?= __('office_team_h') ?></h2>
                <span class="count-pill"><?= count($team) ?></span>
            </div>
            <div class="space-y-3">
                <?php foreach ($team as $member):
                    $status = teamStatus($member);
                    $statusColor = $status === 'online' ? 'bg-emerald-400' : ($status === 'away' ? 'bg-amber-400' : 'bg-gray-300');
                ?>
                <div class="group flex items-center gap-4 p-4 rounded-2xl border border-line bg-white hover:border-transparent hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-300">
                    <div class="relative flex-shrink-0">
                        <?php if ($member['image_url']): ?>
                        <img src="<?= e(imageUrl($member['image_url'])) ?>" alt="<?= e($member['name']) ?>" width="56" height="56" loading="lazy" class="w-14 h-14 rounded-2xl object-cover object-top">
                        <?php else: ?>
                        <span class="icon-chip w-14 h-14 rounded-2xl"><i class="ph-fill ph-user" aria-hidden="true"></i></span>
                        <?php endif; ?>
                        <span class="absolute -bottom-1 -end-1 w-4 h-4 <?= $statusColor ?> border-2 border-white rounded-full" title="<?= ucfirst($status) ?>"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-ink"><bdi><?= e($member['name']) ?></bdi></p>
                        <p class="text-sm text-muted"><?= __($member['role_key']) ?></p>
                        <?php if (!empty($member['languages'])): ?>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <?php foreach (array_map('trim', explode(',', $member['languages'])) as $lang): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-aqua-soft text-azure px-2 py-0.5 rounded-full font-semibold"><i class="ph-fill ph-translate" aria-hidden="true"></i> <?= e($lang) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <?php if ($tel): ?><a href="<?= e($tel) ?>" class="w-10 h-10 rounded-full inline-flex items-center justify-center text-muted-2 hover:bg-navy hover:text-white transition-colors" title="<?= e(__('office_cta_call')) ?>"><i class="ph-fill ph-phone text-lg" aria-hidden="true"></i></a><?php endif; ?>
                        <?php if ($mail): ?><a href="<?= e($mail) ?>" class="w-10 h-10 rounded-full inline-flex items-center justify-center text-muted-2 hover:bg-navy hover:text-white transition-colors" title="<?= e(__('office_cta_email')) ?>"><i class="ph-fill ph-envelope-simple text-lg" aria-hidden="true"></i></a><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Contact + map -->
    <div class="grid gap-6 sm:gap-8 md:grid-cols-2">
        <section class="card p-6 sm:p-8 reveal">
            <h2 class="text-xl sm:text-2xl font-extrabold text-navy mb-6"><?= __('office_contact_info') ?></h2>
            <ul class="space-y-2">
                <li class="flex items-start gap-4 p-3 -mx-3 rounded-2xl">
                    <span class="icon-chip"><i class="ph-fill ph-map-pin" aria-hidden="true"></i></span>
                    <div class="pt-0.5">
                        <p class="text-sm font-bold text-ink"><?= __('office_address') ?></p>
                        <p class="text-sm text-muted leading-relaxed mt-0.5"><bdi><?= nl2br(e($office['address'])) ?></bdi></p>
                    </div>
                </li>
                <?php if ($tel): ?>
                <li><a href="<?= e($tel) ?>" class="group flex items-start gap-4 p-3 -mx-3 rounded-2xl hover:bg-surface transition-colors">
                    <span class="icon-chip"><i class="ph-fill ph-phone" aria-hidden="true"></i></span>
                    <span class="pt-0.5">
                        <span class="block text-sm font-bold text-ink"><?= __('office_phone') ?></span>
                        <span class="block text-sm text-muted mt-0.5 group-hover:text-navy" dir="ltr"><?= e($office['phone']) ?></span>
                    </span>
                </a></li>
                <?php endif; ?>
                <?php if ($mail): ?>
                <li><a href="<?= e($mail) ?>" class="group flex items-start gap-4 p-3 -mx-3 rounded-2xl hover:bg-surface transition-colors">
                    <span class="icon-chip"><i class="ph-fill ph-envelope-simple" aria-hidden="true"></i></span>
                    <span class="pt-0.5 min-w-0">
                        <span class="block text-sm font-bold text-ink"><?= __('office_email') ?></span>
                        <span class="block text-sm text-muted mt-0.5 break-all group-hover:text-navy" dir="ltr"><?= e($office['email']) ?></span>
                    </span>
                </a></li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="card relative overflow-hidden min-h-[320px] reveal" style="--d:.08s">
            <div id="office-map" class="absolute inset-0 z-0"></div>
            <a href="<?= e($route) ?>" target="_blank" rel="noopener" class="btn btn--white btn--sm absolute bottom-4 start-4 z-[500]">
                <i class="ph-fill ph-navigation-arrow" aria-hidden="true"></i><?= __('office_cta_route') ?>
            </a>
        </section>
    </div>

    <!-- FAQ + quick access -->
    <div class="grid gap-6 sm:gap-8 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
        <section class="card p-6 sm:p-8 faq reveal">
            <div class="flex items-center gap-3 mb-5">
                <span class="icon-chip w-10 h-10 text-xl rounded-xl"><i class="ph-fill ph-question" aria-hidden="true"></i></span>
                <h2 class="text-xl font-extrabold text-navy"><?= __('office_faq_h') ?></h2>
            </div>
            <div class="space-y-1">
                <?php for ($q = 1; $q <= 5; $q++): ?>
                <details name="faq" class="rounded-2xl transition-colors">
                    <summary class="flex items-center justify-between gap-4 p-4 text-start">
                        <span class="text-[0.95rem] font-semibold text-ink transition-colors"><?= __('faq_q' . $q) ?></span>
                        <span class="w-8 h-8 rounded-full bg-aqua-soft text-azure inline-flex items-center justify-center flex-shrink-0"><i class="ph ph-plus" aria-hidden="true"></i></span>
                    </summary>
                    <div class="faq__a px-4 pb-4 pe-12 text-sm text-muted leading-relaxed"><?= __('faq_a' . $q) ?></div>
                </details>
                <?php endfor; ?>
            </div>
        </section>

        <section class="card p-6 sm:p-8 reveal" style="--d:.08s">
            <h2 class="text-xl font-extrabold text-navy mb-5"><?= __('office_quick_h') ?></h2>
            <?php
            $quick = [
                ['url' => 'https://acibademinternational.com/second-opinion/',      'icon' => 'ph-microscope',   'h' => __('quick_2nd_opinion_h'), 'p' => __('quick_2nd_opinion_p')],
                ['url' => 'https://acibademinternational.com/online-consultation/', 'icon' => 'ph-video-camera', 'h' => __('quick_online_h'),      'p' => __('quick_online_p')],
                ['url' => 'https://acibademinternational.com/contact/',             'icon' => 'ph-stethoscope',  'h' => __('office_free_consult'), 'p' => __('office_free_consul_p')],
            ];
            ?>
            <div class="space-y-3">
                <?php foreach ($quick as $qa): ?>
                <a href="<?= e($qa['url']) ?>" target="_blank" rel="noopener" class="group flex items-center gap-4 p-4 rounded-2xl border border-line hover:border-transparent hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-300">
                    <span class="icon-chip"><i class="ph-fill <?= $qa['icon'] ?>" aria-hidden="true"></i></span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-bold text-ink group-hover:text-navy"><?= $qa['h'] ?></span>
                        <span class="block text-xs text-muted mt-0.5"><?= $qa['p'] ?></span>
                    </span>
                    <i class="ph ph-arrow-up-right text-muted-2 group-hover:text-aqua transition-colors" aria-hidden="true"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Institutions + partner -->
    <div class="grid gap-6 sm:gap-8 md:grid-cols-2">
        <a href="<?= getBaseUrl() ?>/contracted-institutions" class="dark-card group p-7 sm:p-8 flex flex-col justify-between gap-6 reveal transition-transform duration-300 hover:-translate-y-1">
            <div>
                <span class="w-12 h-12 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center mb-4"><i class="ph-fill ph-shield-check text-2xl text-[#8fd6f3]" aria-hidden="true"></i></span>
                <h3 class="text-xl font-extrabold"><?= __('inst_card_h') ?></h3>
                <p class="mt-2 text-sm text-[#cfdcef] leading-relaxed line-clamp-2"><?= __('inst_card_p') ?></p>
            </div>
            <span class="inline-flex items-center gap-2 font-bold text-sm"><?= __('inst_card_btn') ?> <i class="ph ph-arrow-right arrow" aria-hidden="true"></i></span>
        </a>
        <a href="https://partner.acibademinternational.com/<?= e($office['slug']) ?>/" target="_blank" rel="noopener" class="group relative overflow-hidden rounded-[30px] p-7 sm:p-8 flex flex-col justify-between gap-6 text-white bg-gradient-to-br from-coral to-coral-ink shadow-lift reveal transition-transform duration-300 hover:-translate-y-1" style="--d:.08s">
            <i class="ph-fill ph-handshake absolute -end-6 -bottom-8 text-[10rem] text-white/10 transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" aria-hidden="true"></i>
            <div class="relative">
                <span class="w-12 h-12 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center mb-4"><i class="ph-fill ph-handshake text-2xl" aria-hidden="true"></i></span>
                <h3 class="text-xl font-extrabold"><?= __('office_partner_h') ?></h3>
                <p class="mt-2 text-sm text-white/85 leading-relaxed line-clamp-2"><?= __('office_partner_p') ?></p>
            </div>
            <span class="relative inline-flex items-center gap-2 font-bold text-sm"><?= __('office_partner_btn') ?> <i class="ph ph-arrow-up-right" aria-hidden="true"></i></span>
        </a>
    </div>

    <?php if (!empty($relatedOffices)): ?>
    <section class="pt-6 reveal">
        <div class="flex items-center gap-3 mb-5">
            <h2 class="text-2xl font-extrabold text-navy"><?= __('office_other_in', '<bdi>' . e($office['country']) . '</bdi>') ?></h2>
            <span class="count-pill"><?= count($relatedOffices) ?></span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($relatedOffices as $ro): ?>
            <a href="<?= officeUrl($ro['slug']) ?>" class="card card--hover group flex items-center gap-4 p-5">
                <span class="icon-chip"><i class="ph-fill ph-buildings" aria-hidden="true"></i></span>
                <span class="flex-1 min-w-0">
                    <span class="block font-bold text-ink group-hover:text-navy"><bdi><?= e($ro['display_name']) ?></bdi></span>
                    <span class="block text-xs text-muted mt-1 line-clamp-2"><bdi><?= e($ro['address']) ?></bdi></span>
                </span>
                <i class="ph ph-arrow-right arrow text-muted-2 group-hover:text-aqua" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="pt-6 pb-16 md:pb-0 text-center">
        <a href="<?= getBaseUrl() ?>/offices" class="btn btn--line btn--lg">
            <i class="ph ph-list" aria-hidden="true"></i><?= __('office_back') ?>
        </a>
    </div>
</div>

<!-- Phones: contact buttons stay at hand once the hero buttons scroll away -->
<nav class="action-dock" data-dock aria-label="<?= e(__('office_contact_info')) ?>">
    <?php foreach ($actions as $a): ?>
    <a href="<?= e($a['href']) ?>"<?= $a['ext'] ? ' target="_blank" rel="noopener"' : '' ?> class="<?= $a['cls'] ?>">
        <i class="ph-fill <?= $a['icon'] ?>" aria-hidden="true"></i><span class="line-clamp-1"><?= $a['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initMap !== 'function' || typeof L === 'undefined') return;
    initMap('office-map', [{
        lat: <?= (float)$office['latitude'] ?>,
        lon: <?= (float)$office['longitude'] ?>,
        display_name: <?= json_encode($office['display_name'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        country: <?= json_encode($office['country'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
    }], {
        center: [<?= (float)$office['latitude'] ?>, <?= (float)$office['longitude'] ?>],
        zoom: 16,
        singleOffice: true,
        markerHtml: '<div class="map-pin"><i class="ph-fill ph-buildings"></i></div>'
    });
});
</script>

<?php require __DIR__ . '/../footer.php'; ?>
