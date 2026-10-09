<?php
/** New design: insurance & contracted institutions. $institutions comes from contracted-institutions.php. */
require __DIR__ . '/../header.php';
?>

<section class="page-head">
    <div class="wrap py-14 sm:py-20 text-center">
        <span class="rise inline-flex w-16 h-16 rounded-2xl bg-white/10 border border-white/15 items-center justify-center"><i class="ph-fill ph-shield-check text-3xl text-[#8fd6f3]" aria-hidden="true"></i></span>
        <h1 class="rise mt-6 text-3xl sm:text-5xl font-extrabold max-w-3xl mx-auto" style="--d:.06s"><?= __('inst_title') ?></h1>
        <p class="rise mt-4 text-[#cfdcef] text-base sm:text-lg max-w-2xl mx-auto leading-relaxed" style="--d:.12s"><?= __('inst_subtitle') ?></p>
        <div class="rise mt-8 max-w-md mx-auto relative" style="--d:.18s">
            <i class="ph ph-magnifying-glass absolute start-5 top-1/2 -translate-y-1/2 text-muted text-xl pointer-events-none z-10" aria-hidden="true"></i>
            <input type="search" id="inst-search" placeholder="<?= e(__('inst_search_ph')) ?>" aria-label="<?= e(__('inst_search_ph')) ?>" class="field">
        </div>
    </div>
</section>

<div class="wrap py-10 sm:py-14">
    <div id="inst-no-results" class="hidden text-center py-14">
        <span class="icon-chip mx-auto w-16 h-16 rounded-2xl text-3xl"><i class="ph-fill ph-magnifying-glass-minus" aria-hidden="true"></i></span>
        <h3 class="mt-5 text-lg font-bold text-navy"><?= __('offices_no_results_h') ?></h3>
        <p class="mt-1 text-sm text-muted"><?= __('offices_no_results_p') ?></p>
    </div>

    <div id="inst-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach ($institutions as $i => $inst): ?>
        <div class="inst-card card card--hover group flex items-center gap-4 p-4 sm:p-5 reveal" style="--d:<?= min($i % 8, 7) * 0.03 ?>s" data-name="<?= strtolower(e($inst)) ?>">
            <span class="icon-chip font-extrabold text-lg" aria-hidden="true"><?= e(mb_substr($inst, 0, 1)) ?></span>
            <span class="font-bold text-ink group-hover:text-navy transition-colors leading-tight" dir="ltr"><?= e($inst) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('inst-search').addEventListener('input', function () {
    const query = this.value.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('.inst-card').forEach(function (card) {
        const match = !query || card.getAttribute('data-name').includes(query);
        card.style.display = match ? '' : 'none';
        if (match) { visible++; card.classList.add('in'); }
    });
    document.getElementById('inst-no-results').classList.toggle('hidden', visible > 0);
    document.getElementById('inst-grid').classList.toggle('hidden', visible === 0);
});
</script>

<?php require __DIR__ . '/../footer.php'; ?>
