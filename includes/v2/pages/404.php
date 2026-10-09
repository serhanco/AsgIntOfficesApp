<?php
/** New design: page not found. */
require __DIR__ . '/../header.php';
?>

<section class="page-head min-h-[70vh] flex items-center">
    <div class="wrap py-20 text-center">
        <p class="rise text-[7rem] sm:text-[10rem] font-extrabold leading-none tracking-tighter select-none flex items-center justify-center gap-2" aria-hidden="true">
            <span class="text-gradient">4</span>
            <span class="relative inline-flex w-[0.8em] h-[0.8em] rounded-full bg-gradient-to-br from-aqua to-navy items-center justify-center shadow-cta">
                <i class="ph-fill ph-compass text-[0.4em] text-white"></i>
            </span>
            <span class="text-gradient">4</span>
        </p>
        <h1 class="rise mt-6 text-3xl sm:text-4xl font-extrabold" style="--d:.08s"><?= __('404_title') ?></h1>
        <p class="rise mt-3 text-[#cfdcef] text-lg max-w-xl mx-auto" style="--d:.14s"><?= __('404_subtitle') ?></p>
        <div class="rise mt-9 flex flex-col sm:flex-row justify-center gap-3" style="--d:.2s">
            <a href="<?= getBaseUrl() ?>/" class="btn btn--primary btn--lg"><i class="ph-fill ph-target" aria-hidden="true"></i><?= __('404_btn_home') ?></a>
            <a href="<?= getBaseUrl() ?>/offices" class="btn btn--ghost btn--lg"><i class="ph-fill ph-buildings" aria-hidden="true"></i><?= __('404_btn_offices') ?></a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../footer.php'; ?>
