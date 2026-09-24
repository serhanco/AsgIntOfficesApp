    <!-- Footer -->
    <footer class="bg-acibadem-dark mt-auto border-t border-gray-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row justify-between items-center md:items-start space-y-8 md:space-y-0">
                
                <!-- Left: Logo & Subtitle -->
                <div class="flex flex-col items-center md:items-start">
                    <div class="flex flex-col items-center">
                        <img class="h-6 w-auto mb-3" src="<?= getBaseUrl() ?>/assets/images/acibadem-white-logo.webp" alt="Acıbadem Logo">
                        <span class="text-gray-400 text-sm font-medium tracking-wide">acibadem.world</span>
                    </div>
                </div>
                
                <!-- Center: Quick links -->
                <div class="flex flex-col items-center md:items-start">
                    <h3 class="text-white text-sm font-semibold mb-4 uppercase tracking-wider"><?= __('footer_quick_links') ?></h3>
                    <ul class="space-y-2 text-center md:text-left">
                        <li><a href="<?= getBaseUrl() ?>/" class="text-gray-400 hover:text-white transition-colors text-sm"><?= __('footer_home') ?></a></li>
                        <li><a href="<?= getBaseUrl() ?>/offices" class="text-gray-400 hover:text-white transition-colors text-sm"><?= __('nav_all_offices') ?></a></li>
                        <li><a href="<?= getBaseUrl() ?>/map" class="text-gray-400 hover:text-white transition-colors text-sm"><?= __('nav_map') ?></a></li>
                        <li><a href="<?= getBaseUrl() ?>/contracted-institutions" class="text-gray-400 hover:text-white transition-colors text-sm"><?= __('inst_title') ?></a></li>
                        <li><a href="https://partner.acibademinternational.com/london/" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors text-sm"><?= __('footer_partner') ?></a></li>
                        <li><a href="https://www.acibadem.com.tr/acibademonline/#/login" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors text-sm">Acıbadem Online</a></li>
                        <li><a href="<?= getBaseUrl() ?>/api" target="_blank" class="text-gray-400 hover:text-white transition-colors text-sm flex items-center gap-1"><i class="ph-fill ph-code"></i> API (JSON)</a></li>
                    </ul>
                </div>
                
                <!-- Right: Contact info -->
                <div class="flex flex-col items-center md:items-start">
                    <h3 class="text-white text-sm font-semibold mb-4 uppercase tracking-wider"><?= __('footer_contact') ?></h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="https://wa.me/905359650466" target="_blank" rel="noopener noreferrer"
                               class="text-gray-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                                <i class="ph-fill ph-whatsapp-logo text-lg text-green-400"></i>
                                +90 535 965 0466
                            </a>
                        </li>
                        <li>
                            <a href="tel:+902164445544"
                               class="text-gray-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                                <i class="ph-fill ph-phone text-lg"></i>
                                +90 216 444 5544
                            </a>
                        </li>
                        <li>
                            <a href="mailto:international@acibadem.com"
                               class="text-gray-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                                <i class="ph-fill ph-envelope-simple text-lg"></i>
                                international@acibadem.com
                            </a>
                        </li>
                    </ul>
                </div>
                
            </div>
        </div>
        
        <!-- Bottom copyright bar -->
        <div class="bg-black/20 py-4">
            <div class="max-w-6xl mx-auto px-4 text-center">
                <p class="text-gray-400 text-xs">
                    <?= __('footer_copyright') ?>
                </p>
            </div>
        </div>
    </footer>

    <!-- App JS -->
    <script src="<?= getBaseUrl() ?>/assets/js/app.js"></script>
</body>
</html>
