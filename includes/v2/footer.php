    </main>

    <footer class="site-footer mt-auto">
        <div class="wrap py-14 md:py-16 grid gap-10 md:grid-cols-12">
            <div class="md:col-span-5 text-center md:text-start">
                <img class="h-7 w-auto mx-auto md:mx-0 mb-5" src="<?= getBaseUrl() ?>/assets/images/acibadem-white-logo.webp" alt="Acıbadem" width="175" height="28" loading="lazy">
                <p class="text-sm font-semibold text-white/90"><?= __('site_name') ?></p>
                <p class="text-sm mt-1">acibadem.world</p>
                <div class="mt-6 flex flex-wrap justify-center md:justify-start gap-2">
                    <a href="https://wa.me/905359650466" target="_blank" rel="noopener noreferrer" class="btn btn--sm bg-wa/15 text-[#7ef0a8] hover:!bg-wa hover:!text-white">
                        <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i><?= __('js_whatsapp') ?>
                    </a>
                    <a href="tel:+902164445544" class="btn btn--sm bg-white/5 text-white border-white/15 hover:!bg-white/15">
                        <i class="ph-fill ph-phone" aria-hidden="true"></i><?= __('js_call') ?>
                    </a>
                </div>
            </div>

            <div class="md:col-span-3 text-center md:text-start">
                <h3 class="footer-h"><?= __('footer_quick_links') ?></h3>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?= getBaseUrl() ?>/"><?= __('footer_home') ?></a></li>
                    <li><a href="<?= getBaseUrl() ?>/offices"><?= __('nav_all_offices') ?></a></li>
                    <li><a href="<?= getBaseUrl() ?>/map"><?= __('nav_map') ?></a></li>
                    <li><a href="<?= getBaseUrl() ?>/events"><?= __('nav_events') ?></a></li>
                    <li><a href="<?= getBaseUrl() ?>/contracted-institutions"><?= __('inst_title') ?></a></li>
                    <li><a href="https://partner.acibademinternational.com/london/" target="_blank" rel="noopener noreferrer"><?= __('footer_partner') ?></a></li>
                    <li><a href="https://www.acibadem.com.tr/acibademonline/#/login" target="_blank" rel="noopener noreferrer">Acıbadem Online</a></li>
                </ul>
            </div>

            <div class="md:col-span-4 text-center md:text-start">
                <h3 class="footer-h"><?= __('footer_contact') ?></h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="https://wa.me/905359650466" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2.5"><i class="ph-fill ph-whatsapp-logo text-lg text-[#4ee38a]" aria-hidden="true"></i><span dir="ltr">+90 535 965 0466</span></a></li>
                    <li><a href="tel:+902164445544" class="inline-flex items-center gap-2.5"><i class="ph-fill ph-phone text-lg text-aqua" aria-hidden="true"></i><span dir="ltr">+90 216 444 5544</span></a></li>
                    <li><a href="mailto:international@acibadem.com" class="inline-flex items-center gap-2.5"><i class="ph-fill ph-envelope-simple text-lg text-aqua" aria-hidden="true"></i><span dir="ltr">international@acibadem.com</span></a></li>
                    <li class="inline-flex items-start gap-2.5 text-start"><i class="ph-fill ph-map-pin text-lg text-aqua flex-shrink-0" aria-hidden="true"></i><span dir="ltr">Atatürk Mah. Feza Sok. No:3 Ataşehir / İstanbul</span></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="wrap py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <p><?= __('footer_copyright') ?></p>
                <p class="flex items-center gap-4">
                    <a href="<?= getBaseUrl() ?>/api/offices" target="_blank" class="inline-flex items-center gap-1 opacity-70"><i class="ph-fill ph-code" aria-hidden="true"></i> API</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
