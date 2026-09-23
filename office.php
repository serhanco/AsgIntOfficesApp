<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
$office = getOfficeBySlug($slug);

if (!$office) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$relatedOffices = getOfficesByCountry($office['country'], $office['id']);
$pageTitle = $office['display_name'] . ' — ' . $office['country'];
$currentPage = 'office';
$needsMap = true;
$metaDescription = 'Acıbadem Information Office in ' . $office['display_name'] . ', ' . $office['country'] . '. Contact: ' . $office['phone'] . ' | ' . $office['email'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="w-full bg-[#0A1C36]">
    <div class="relative h-72 md:h-[45vh] lg:max-h-[500px] w-full max-w-[1920px] mx-auto overflow-hidden">
        <img src="<?= getBaseUrl() ?>/assets/images/office-hero.webp" alt="Acibadem Office" class="absolute inset-0 w-full h-full object-cover opacity-70">
        
        <!-- Edge Fades for Ultrawide Screens -->
        <div class="absolute inset-y-0 left-0 w-40 bg-gradient-to-r from-[#0A1C36] to-transparent hidden 2xl:block pointer-events-none z-0"></div>
        <div class="absolute inset-y-0 right-0 w-40 bg-gradient-to-l from-[#0A1C36] to-transparent hidden 2xl:block pointer-events-none z-0"></div>
        
        <div class="absolute inset-0 bg-gradient-to-t from-[#0A1C36] via-[#0A1C36]/30 to-transparent pointer-events-none z-0"></div>
        
        <div class="absolute bottom-10 left-0 w-full z-10">
            <div class="max-w-5xl mx-auto px-4">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full mb-3 border border-white/20">
                    <?= getFlagImg($office['country'], $office['country_code']) ?>
                    <span class="text-white text-sm font-semibold"><?= e($office['country']) ?></span>
                </div>
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-2"><?= e($office['display_name']) ?></h1>
                <p class="text-gray-300 text-sm md:text-base max-w-2xl flex items-center gap-2">
                    <i class="ph-fill ph-map-pin"></i>
                    <?= e($office['address']) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 relative z-10 pb-16">
    <!-- CTA Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 -mt-6 mb-10">
        <?php if (!empty($office['phone'])): ?>
            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
                <div class="w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                    <i class="ph-fill ph-phone text-2xl"></i>
                </div>
                <span class="block text-sm font-bold text-gray-800"><?= __('office_cta_call') ?></span>
            </a>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $office['phone']) ?>" target="_blank" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-green-600 group-hover:text-white transition-colors">
                    <i class="ph-fill ph-whatsapp-logo text-2xl"></i>
                </div>
                <span class="block text-sm font-bold text-gray-800"><?= __('js_whatsapp') ?></span>
            </a>
        <?php endif; ?>

        <?php if (!empty($office['email'])): ?>
            <a href="mailto:<?= e($office['email']) ?>" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
                <div class="w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                    <i class="ph-fill ph-envelope-simple text-2xl"></i>
                </div>
                <span class="block text-sm font-bold text-gray-800"><?= __('office_cta_email') ?></span>
            </a>
        <?php endif; ?>
        
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $office['latitude'] ?>,<?= $office['longitude'] ?>" target="_blank" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
            <div class="w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                <i class="ph-fill ph-navigation-arrow text-2xl"></i>
            </div>
            <span class="block text-sm font-bold text-gray-800"><?= __('office_cta_route') ?></span>
        </a>
    </div>

    <!-- ===== Recent Activities + Our Team ===== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">

        <!-- Recent Activities -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[#0c2d74]"><?= __('office_activities_h', e($office['country'])) ?></h3>
                <span class="text-xs font-semibold bg-[#E6F0FA] text-[#0c2d74] px-3 py-1 rounded-full">2026</span>
            </div>
            <div class="space-y-4">

                <!-- Activity 1: Meet the Doctor -->
                <div class="relative overflow-hidden group flex gap-4 p-4 rounded-2xl bg-blue-50 hover:bg-[#0c2d74] hover:shadow-md hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <i class="ph-fill ph-stethoscope absolute right-4 bottom-2 text-5xl text-white opacity-0 group-hover:opacity-10 group-hover:scale-110 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 rounded-xl bg-[#0c2d74] group-hover:bg-white flex items-center justify-center transition-colors">
                        <i class="ph-fill ph-stethoscope text-2xl text-white group-hover:text-[#0c2d74]"></i>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#1a4ba0] group-hover:text-blue-200 transition-colors"><?= __('act_tag_doctor') ?></span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-white transition-colors leading-snug"><?= __('act_doc_title') ?></p>
                        <p class="text-xs text-gray-500 group-hover:text-blue-200 transition-colors mt-1"><?= __('act_doc_spec') ?></p>
                    </div>
                    <div class="relative z-10 flex-shrink-0 self-center text-gray-300 group-hover:text-white transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </div>

                <!-- Activity 2: Presentation -->
                <div class="relative overflow-hidden group flex gap-4 p-4 rounded-2xl bg-purple-50 hover:bg-purple-700 hover:shadow-md hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <i class="ph-fill ph-presentation-chart absolute right-4 bottom-2 text-5xl text-white opacity-0 group-hover:opacity-10 group-hover:scale-110 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 rounded-xl bg-purple-700 group-hover:bg-white flex items-center justify-center transition-colors">
                        <i class="ph-fill ph-presentation-chart text-2xl text-white group-hover:text-purple-700"></i>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide text-purple-700 group-hover:text-purple-200 transition-colors"><?= __('act_tag_presentation') ?></span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-white transition-colors leading-snug"><?= __('act_pres_title') ?></p>
                        <p class="text-xs text-gray-500 group-hover:text-purple-200 transition-colors mt-1"><?= __('act_pres_desc') ?></p>
                    </div>
                    <div class="relative z-10 flex-shrink-0 self-center text-gray-300 group-hover:text-white transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </div>

                <!-- Activity 3: Exhibition -->
                <div class="relative overflow-hidden group flex gap-4 p-4 rounded-2xl bg-emerald-50 hover:bg-emerald-700 hover:shadow-md hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <i class="ph-fill ph-handshake absolute right-4 bottom-2 text-5xl text-white opacity-0 group-hover:opacity-10 group-hover:scale-110 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-600 group-hover:bg-white flex items-center justify-center transition-colors">
                        <i class="ph-fill ph-handshake text-2xl text-white group-hover:text-emerald-700"></i>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 group-hover:text-emerald-200 transition-colors"><?= __('act_tag_exhibition') ?></span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-white transition-colors leading-snug"><?= __('act_exh_title') ?></p>
                        <p class="text-xs text-gray-500 group-hover:text-emerald-200 transition-colors mt-1"><?= __('act_exh_desc') ?></p>
                    </div>
                    <div class="relative z-10 flex-shrink-0 self-center text-gray-300 group-hover:text-white transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </div>

            </div>
        </div>

        <!-- Our Team -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[#0c2d74]"><?= __('office_team_h') ?></h3>
                <span class="text-xs font-semibold bg-[#E6F0FA] text-[#0c2d74] px-3 py-1 rounded-full"><?= __('office_team_count') ?></span>
            </div>
            <div class="space-y-4">

                <!-- Member 1 -->
                <div class="relative overflow-hidden flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 hover:border-[#0c2d74] hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                    <i class="ph-fill ph-user absolute right-4 bottom-2 text-5xl text-gray-50 opacity-0 group-hover:opacity-100 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 relative">
                        <img src="<?= getBaseUrl() ?>/assets/images/team-cem-ustundag-sm.jpg" alt="Cem Üstündağ" class="w-14 h-14 rounded-2xl object-cover object-top shadow-sm">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors">Cem Üstündağ</p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('team_role_coord') ?></p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> Turkish</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> English</span>
                        </div>
                    </div>
                    <div class="relative z-10 flex items-center gap-3 flex-shrink-0">
                        <?php if (!empty($office['phone'])): ?>
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="text-gray-300 hover:text-[#0c2d74] transition-colors" title="Call Office">
                            <i class="ph-fill ph-phone text-xl"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($office['email'])): ?>
                        <a href="mailto:<?= e($office['email']) ?>" class="text-gray-300 hover:text-[#0c2d74] transition-colors" title="Email Office">
                            <i class="ph-fill ph-envelope-simple text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Member 2 -->
                <div class="relative overflow-hidden flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 hover:border-[#0c2d74] hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                    <i class="ph-fill ph-user absolute right-4 bottom-2 text-5xl text-gray-50 opacity-0 group-hover:opacity-100 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 relative">
                        <img src="<?= getBaseUrl() ?>/assets/images/team-erim-ekiz-sm.jpg" alt="Erim Ekiz" class="w-14 h-14 rounded-2xl object-cover object-top shadow-sm">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors">Erim Ekiz</p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('team_role_patient') ?></p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> Turkish</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> English</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> German</span>
                        </div>
                    </div>
                    <div class="relative z-10 flex items-center gap-3 flex-shrink-0">
                        <?php if (!empty($office['phone'])): ?>
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="text-gray-300 hover:text-[#0c2d74] transition-colors" title="Call Office">
                            <i class="ph-fill ph-phone text-xl"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($office['email'])): ?>
                        <a href="mailto:<?= e($office['email']) ?>" class="text-gray-300 hover:text-[#0c2d74] transition-colors" title="Email Office">
                            <i class="ph-fill ph-envelope-simple text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Member 3 -->
                <div class="relative overflow-hidden flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 hover:border-[#0c2d74] hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                    <i class="ph-fill ph-user absolute right-4 bottom-2 text-5xl text-gray-50 opacity-0 group-hover:opacity-100 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 relative">
                        <img src="<?= getBaseUrl() ?>/assets/images/team-ionea-ruxandra-sm.jpg" alt="Ionea Ruxandra" class="w-14 h-14 rounded-2xl object-cover object-top shadow-sm">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors">Ionea Ruxandra</p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('team_role_liaison') ?></p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> Romanian</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> English</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> French</span>
                        </div>
                    </div>
                    <div class="relative z-10 flex items-center gap-3 flex-shrink-0">
                        <?php if (!empty($office['phone'])): ?>
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="text-gray-300 hover:text-[#0c2d74] transition-colors" title="Call Office">
                            <i class="ph-fill ph-phone text-xl"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($office['email'])): ?>
                        <a href="mailto:<?= e($office['email']) ?>" class="text-gray-300 hover:text-[#0c2d74] transition-colors" title="Email Office">
                            <i class="ph-fill ph-envelope-simple text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ===== Contact Info + Map (existing) ===== -->
    <!-- ===== FAQ + Quick Access ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 mt-10">

        <!-- FAQ -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 bg-[#E6F0FA] text-[#0c2d74] rounded-xl flex items-center justify-center">
                    <i class="ph-fill ph-question text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0c2d74]"><?= __('office_faq_h') ?></h3>
            </div>
            <div class="divide-y divide-gray-100" id="faq-accordion">

                <div class="faq-item py-4 transition-all duration-300 rounded-2xl px-4 -mx-4 hover:bg-blue-50/50 group">
                    <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                        <span class="text-sm font-semibold text-gray-900 group-[.is-open]:text-[#0c2d74] transition-colors duration-200"><?= __('faq_q1') ?></span>
                        <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                    </button>
                    <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                        <?= __('faq_a1') ?>
                    </div>
                </div>

                <div class="faq-item py-4 transition-all duration-300 rounded-2xl px-4 -mx-4 hover:bg-blue-50/50 group">
                    <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                        <span class="text-sm font-semibold text-gray-900 group-[.is-open]:text-[#0c2d74] transition-colors duration-200"><?= __('faq_q2') ?></span>
                        <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                    </button>
                    <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                        <?= __('faq_a2') ?>
                    </div>
                </div>

                <div class="faq-item py-4 transition-all duration-300 rounded-2xl px-4 -mx-4 hover:bg-blue-50/50 group">
                    <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                        <span class="text-sm font-semibold text-gray-900 group-[.is-open]:text-[#0c2d74] transition-colors duration-200"><?= __('faq_q3') ?></span>
                        <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                    </button>
                    <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                        <?= __('faq_a3') ?>
                    </div>
                </div>

                <div class="faq-item py-4 transition-all duration-300 rounded-2xl px-4 -mx-4 hover:bg-blue-50/50 group">
                    <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                        <span class="text-sm font-semibold text-gray-900 group-[.is-open]:text-[#0c2d74] transition-colors duration-200"><?= __('faq_q4') ?></span>
                        <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                    </button>
                    <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                        <?= __('faq_a4') ?>
                    </div>
                </div>

                <div class="faq-item py-4 transition-all duration-300 rounded-2xl px-4 -mx-4 hover:bg-blue-50/50 group">
                    <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                        <span class="text-sm font-semibold text-gray-900 group-[.is-open]:text-[#0c2d74] transition-colors duration-200"><?= __('faq_q5') ?></span>
                        <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                    </button>
                    <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                        <?= __('faq_a5') ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Quick Access -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[#0c2d74]"><?= __('office_quick_h') ?></h3>
                <span class="text-xs font-semibold bg-[#E6F0FA] text-[#0c2d74] px-3 py-1 rounded-full"><?= __('office_quick_count') ?></span>
            </div>
            
            <div class="flex flex-col gap-4">
                <!-- Card 1: Second Opinion -->
                <a href="https://acibademinternational.com/second-opinion/" target="_blank" rel="noopener"
                   class="relative overflow-hidden group flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#0c2d74] hover:shadow-lg transition-all duration-300 hover:-translate-y-1 bg-white">
                    <i class="ph-fill ph-microscope absolute right-4 bottom-2 text-5xl text-gray-50 opacity-50 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-xl flex items-center justify-center group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                        <i class="ph-fill ph-microscope text-2xl"></i>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors"><?= __('quick_2nd_opinion_h') ?></p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('quick_2nd_opinion_p') ?></p>
                    </div>
                    <div class="relative z-10 flex-shrink-0 text-gray-300 group-hover:text-[#0c2d74] transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </a>

                <!-- Card 2: Online Consultation -->
                <a href="https://acibademinternational.com/online-consultation/" target="_blank" rel="noopener"
                   class="relative overflow-hidden group flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#0c2d74] hover:shadow-lg transition-all duration-300 hover:-translate-y-1 bg-white">
                    <i class="ph-fill ph-video-camera absolute right-4 bottom-2 text-5xl text-gray-50 opacity-50 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-xl flex items-center justify-center group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                        <i class="ph-fill ph-video-camera text-2xl"></i>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors"><?= __('quick_online_h') ?></p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('quick_online_p') ?></p>
                    </div>
                    <div class="relative z-10 flex-shrink-0 text-gray-300 group-hover:text-[#0c2d74] transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </a>

                <!-- Card 3: Free Medical Consultation -->
                <a href="https://acibademinternational.com/contact/" target="_blank" rel="noopener"
                   class="relative overflow-hidden group flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#0c2d74] hover:shadow-lg transition-all duration-300 hover:-translate-y-1 bg-white">
                    <i class="ph-fill ph-stethoscope absolute right-4 bottom-2 text-5xl text-gray-50 opacity-50 group-hover:scale-110 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-xl flex items-center justify-center group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                        <i class="ph-fill ph-stethoscope text-2xl"></i>
                    </div>
                    <div class="relative z-10 flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors"><?= __('office_free_consult') ?></p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('office_free_consul_p') ?></p>
                    </div>
                    <div class="relative z-10 flex-shrink-0 text-gray-300 group-hover:text-[#0c2d74] transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </a>
            </div>
        </div>

    </div>

    <!-- ===== Dual CTA Bento Grid ===== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Contracted Institutions CTA -->
        <a href="<?= getBaseUrl() ?>/contracted-institutions" class="relative overflow-hidden bg-gradient-to-br from-[#0c2d74] to-[#0A1C36] rounded-3xl p-8 flex flex-col justify-between gap-6 group shadow-xl shadow-blue-900/20 hover:-translate-y-1 transition-all duration-300">
            <i class="ph-fill ph-shield-check absolute -right-6 -bottom-6 text-[10rem] text-white opacity-5 group-hover:scale-110 group-hover:opacity-10 transition-all duration-700 pointer-events-none"></i>
            <div class="relative z-10">
                <div class="w-12 h-12 bg-white/10 backdrop-blur text-white rounded-xl flex items-center justify-center mb-4 border border-white/10">
                    <i class="ph-fill ph-shield-check text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2"><?= __('inst_card_h') ?></h3>
                <p class="text-blue-200/70 text-sm leading-relaxed line-clamp-2"><?= __('inst_card_p') ?></p>
            </div>
            <div class="relative z-10 inline-flex items-center text-white font-bold text-sm opacity-70 group-hover:opacity-100 transition-opacity">
                <?= __('inst_card_btn') ?> <i class="ph ph-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <!-- Become a Partner CTA -->
        <a href="https://partner.acibademinternational.com/london/" target="_blank" rel="noopener" class="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-8 flex flex-col justify-between gap-6 group shadow-xl shadow-slate-900/20 hover:-translate-y-1 transition-all duration-300 border border-slate-700">
            <i class="ph-fill ph-handshake absolute -right-6 -bottom-6 text-[10rem] text-slate-100 opacity-[0.03] group-hover:scale-110 group-hover:opacity-[0.06] transition-all duration-700 pointer-events-none"></i>
            <div class="relative z-10">
                <div class="w-12 h-12 bg-white/5 backdrop-blur text-white rounded-xl flex items-center justify-center mb-4 border border-white/10 group-hover:bg-white/10 transition-colors">
                    <i class="ph-fill ph-handshake text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2"><?= __('office_partner_h') ?></h3>
                <p class="text-slate-400 text-sm leading-relaxed line-clamp-2"><?= __('office_partner_p') ?></p>
            </div>
            <div class="relative z-10 inline-flex items-center text-white font-bold text-sm opacity-70 group-hover:opacity-100 transition-opacity">
                <?= __('office_partner_btn') ?> <i class="ph ph-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>
    </div>

    <!-- ===== Contact Info + Map ===== -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Contact Details -->
        <div class="relative overflow-hidden bg-white rounded-3xl shadow-sm hover:shadow-lg border border-gray-100 hover:border-blue-100 p-8 md:p-10 transition-all duration-300 group hover:-translate-y-1">
            <i class="ph-fill ph-map-pin absolute -right-6 -bottom-6 text-[12rem] text-gray-50 opacity-50 group-hover:scale-110 group-hover:-translate-x-4 group-hover:-translate-y-4 group-hover:text-blue-50 transition-all duration-700 pointer-events-none"></i>
            
            <div class="relative z-10">
                <h3 class="text-xl md:text-2xl font-bold text-[#0c2d74] mb-8"><?= __('office_contact_info') ?></h3>
                
                <div class="space-y-4">
                    <!-- Address -->
                    <div class="flex items-start gap-5 group/item hover:bg-gray-50/50 p-3 -mx-3 rounded-2xl transition-colors duration-300">
                        <div class="mt-1 bg-blue-50 w-12 h-12 flex items-center justify-center rounded-xl text-[#0c2d74] group-hover/item:bg-[#0c2d74] group-hover/item:text-white transition-colors duration-300 shadow-sm flex-shrink-0">
                            <i class="ph-fill ph-map-pin text-2xl"></i>
                        </div>
                        <div class="flex-1 pt-0.5">
                            <div class="text-sm font-bold text-gray-900 mb-1"><?= __('office_address') ?></div>
                            <div class="text-gray-600 text-sm leading-relaxed"><?= nl2br(e($office['address'])) ?></div>
                        </div>
                    </div>

                    <?php if (!empty($office['phone'])): ?>
                    <!-- Phone -->
                    <div class="flex items-start gap-5 group/item hover:bg-gray-50/50 p-3 -mx-3 rounded-2xl transition-colors duration-300">
                        <div class="mt-1 bg-blue-50 w-12 h-12 flex items-center justify-center rounded-xl text-[#0c2d74] group-hover/item:bg-[#0c2d74] group-hover/item:text-white transition-colors duration-300 shadow-sm flex-shrink-0">
                            <i class="ph-fill ph-phone text-2xl"></i>
                        </div>
                        <div class="flex-1 pt-0.5">
                            <div class="text-sm font-bold text-gray-900 mb-1"><?= __('office_phone') ?></div>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="text-gray-600 text-sm hover:text-[#0c2d74] transition-colors"><?= e($office['phone']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($office['email'])): ?>
                    <!-- Email -->
                    <div class="flex items-start gap-5 group/item hover:bg-gray-50/50 p-3 -mx-3 rounded-2xl transition-colors duration-300">
                        <div class="mt-1 bg-blue-50 w-12 h-12 flex items-center justify-center rounded-xl text-[#0c2d74] group-hover/item:bg-[#0c2d74] group-hover/item:text-white transition-colors duration-300 shadow-sm flex-shrink-0">
                            <i class="ph-fill ph-envelope-simple text-2xl"></i>
                        </div>
                        <div class="flex-1 pt-0.5">
                            <div class="text-sm font-bold text-gray-900 mb-1"><?= __('office_email') ?></div>
                            <a href="mailto:<?= e($office['email']) ?>" class="text-gray-600 text-sm hover:text-[#0c2d74] transition-colors"><?= e($office['email']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mini Map -->
        <div class="relative overflow-hidden bg-white rounded-3xl shadow-sm hover:shadow-lg border border-gray-100 hover:border-blue-100 transition-all duration-300 group hover:-translate-y-1 min-h-[350px]">
            <div id="office-map" class="absolute inset-0 w-full h-full z-0"></div>
            <!-- Optional subtle inner shadow for a premium cutout look -->
            <div class="absolute inset-0 pointer-events-none shadow-inner rounded-3xl z-10 border border-black/5"></div>
        </div>
    </div>
    
    <?php if (!empty($relatedOffices)): ?>
    <div class="mt-16">
        <div class="flex items-center gap-3 mb-6">
            <h3 class="text-2xl font-bold text-[#0c2d74]"><?= __('office_other_in', e($office['country'])) ?></h3>
            <span class="text-sm font-bold bg-[#E6F0FA] text-[#0c2d74] px-3 py-1 rounded-full"><?= count($relatedOffices) ?></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($relatedOffices as $ro): ?>
                <a href="<?= officeUrl($ro['slug']) ?>" class="relative overflow-hidden block bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-lg hover:border-[#0c2d74] transition-all duration-300 group hover:-translate-y-1">
                    <i class="ph-fill ph-buildings absolute right-4 -bottom-2 text-7xl text-blue-50 opacity-0 group-hover:opacity-100 group-hover:scale-110 group-hover:-translate-x-2 group-hover:-translate-y-2 group-hover:text-blue-50 transition-all duration-500 pointer-events-none"></i>
                    
                    <div class="relative z-10 flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-50 flex items-center justify-center rounded-xl text-[#0c2d74] flex-shrink-0 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors duration-300 shadow-sm">
                            <i class="ph-fill ph-buildings text-2xl"></i>
                        </div>
                        
                        <div class="flex-1 min-w-0 pt-0.5">
                            <h4 class="font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors text-[1.05rem]"><?= e($ro['display_name']) ?></h4>
                            <p class="text-xs text-gray-500 mt-1.5 line-clamp-2 leading-relaxed"><?= e($ro['address']) ?></p>
                        </div>
                        
                        <div class="flex-shrink-0 text-gray-300 group-hover:text-[#0c2d74] transition-all duration-300 group-hover:translate-x-1 self-center">
                            <i class="ph ph-arrow-right text-lg"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-12 text-center">
        <a href="<?= getBaseUrl() ?>/offices" class="inline-flex items-center gap-3 border-2 border-gray-200 text-gray-700 hover:border-[#0c2d74] hover:bg-[#0c2d74] hover:text-white font-bold py-4 px-10 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-900/20 group">
            <i class="ph ph-list text-xl group-hover:scale-110 transition-transform"></i>
            <?= __('office_back') ?>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof initMap === 'function') {
      initMap('office-map', [{ 
          lat: <?= $office['latitude'] ?>, 
          lon: <?= $office['longitude'] ?>, 
          display_name: '<?= e($office['display_name']) ?>', 
          country: '<?= e($office['country']) ?>' 
      }], { 
          center: [<?= $office['latitude'] ?>, <?= $office['longitude'] ?>], 
          zoom: 16, 
          singleOffice: true 
      });
  }
});

function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const icon   = btn.querySelector('i');
    const faqItem = btn.closest('.faq-item');
    const isOpen = !answer.classList.contains('hidden');

    // Close all
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.add('hidden'));
    document.querySelectorAll('.faq-trigger i').forEach(i => i.style.transform = '');
    document.querySelectorAll('.faq-item').forEach(item => item.classList.remove('is-open', 'bg-blue-50/50'));

    if (!isOpen) {
        answer.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
        if (faqItem) {
            faqItem.classList.add('is-open', 'bg-blue-50/50');
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
