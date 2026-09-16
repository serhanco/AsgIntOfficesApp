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

<div class="relative h-72 md:h-[45vh] w-full bg-[#0A1C36] overflow-hidden">
    <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1200" alt="Acibadem Office" class="absolute inset-0 w-full h-full object-cover opacity-50">
    <div class="absolute inset-0 bg-gradient-to-t from-[#0A1C36] via-transparent to-transparent"></div>
    <div class="absolute bottom-10 left-0 w-full">
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

<div class="max-w-5xl mx-auto px-4 relative z-10 pb-16">
    <!-- CTA Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 -mt-6 mb-10">
        <?php if (!empty($office['phone'])): ?>
            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
                <div class="w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                    <i class="ph-fill ph-phone text-2xl"></i>
                </div>
                <span class="block text-sm font-bold text-gray-800">Call Now</span>
            </a>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $office['phone']) ?>" target="_blank" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-green-600 group-hover:text-white transition-colors">
                    <i class="ph-fill ph-whatsapp-logo text-2xl"></i>
                </div>
                <span class="block text-sm font-bold text-gray-800">WhatsApp</span>
            </a>
        <?php endif; ?>

        <?php if (!empty($office['email'])): ?>
            <a href="mailto:<?= e($office['email']) ?>" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
                <div class="w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                    <i class="ph-fill ph-envelope-simple text-2xl"></i>
                </div>
                <span class="block text-sm font-bold text-gray-800">Email Us</span>
            </a>
        <?php endif; ?>
        
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $office['latitude'] ?>,<?= $office['longitude'] ?>" target="_blank" class="cta-btn bg-white rounded-2xl shadow-lg p-4 text-center transform transition hover:-translate-y-1 hover:shadow-xl group border border-gray-100">
            <div class="w-12 h-12 bg-blue-50 text-[#0c2d74] rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                <i class="ph-fill ph-navigation-arrow text-2xl"></i>
            </div>
            <span class="block text-sm font-bold text-gray-800">Get Route</span>
        </a>
    </div>

    <!-- ===== Recent Activities + Our Team ===== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">

        <!-- Recent Activities -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[#0c2d74]">Recent Activities</h3>
                <span class="text-xs font-semibold bg-[#E6F0FA] text-[#0c2d74] px-3 py-1 rounded-full">2025</span>
            </div>
            <div class="space-y-4">

                <!-- Activity 1: Meet the Doctor -->
                <div class="group flex gap-4 p-4 rounded-2xl bg-blue-50 hover:bg-[#0c2d74] transition-all duration-300 cursor-pointer">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#0c2d74] group-hover:bg-white flex items-center justify-center transition-colors">
                        <i class="ph-fill ph-stethoscope text-2xl text-white group-hover:text-[#0c2d74]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#1a4ba0] group-hover:text-blue-200 transition-colors">Meet the Doctor</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-white transition-colors leading-snug">Prof. Yaşar Çolak</p>
                        <p class="text-xs text-gray-500 group-hover:text-blue-200 transition-colors mt-1">Thoracic Surgery Specialist · London</p>
                    </div>
                    <div class="flex-shrink-0 self-center text-gray-300 group-hover:text-white transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </div>

                <!-- Activity 2: Presentation -->
                <div class="group flex gap-4 p-4 rounded-2xl bg-purple-50 hover:bg-purple-700 transition-all duration-300 cursor-pointer">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-purple-700 group-hover:bg-white flex items-center justify-center transition-colors">
                        <i class="ph-fill ph-presentation-chart text-2xl text-white group-hover:text-purple-700"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide text-purple-700 group-hover:text-purple-200 transition-colors">Presentation</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-white transition-colors leading-snug">Robotic Single Port Surgery</p>
                        <p class="text-xs text-gray-500 group-hover:text-purple-200 transition-colors mt-1">Latest techniques in minimal-invasive care</p>
                    </div>
                    <div class="flex-shrink-0 self-center text-gray-300 group-hover:text-white transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </div>

                <!-- Activity 3: Exhibition -->
                <div class="group flex gap-4 p-4 rounded-2xl bg-emerald-50 hover:bg-emerald-700 transition-all duration-300 cursor-pointer">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-600 group-hover:bg-white flex items-center justify-center transition-colors">
                        <i class="ph-fill ph-handshake text-2xl text-white group-hover:text-emerald-700"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 group-hover:text-emerald-200 transition-colors">Exhibition</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-white transition-colors leading-snug">Growing B2B Network</p>
                        <p class="text-xs text-gray-500 group-hover:text-emerald-200 transition-colors mt-1">International healthcare partnerships fair</p>
                    </div>
                    <div class="flex-shrink-0 self-center text-gray-300 group-hover:text-white transition-colors">
                        <i class="ph ph-arrow-right text-lg"></i>
                    </div>
                </div>

            </div>
        </div>

        <!-- Our Team -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[#0c2d74]">Our Team</h3>
                <span class="text-xs font-semibold bg-[#E6F0FA] text-[#0c2d74] px-3 py-1 rounded-full">3 members</span>
            </div>
            <div class="space-y-4">

                <!-- Member 1 -->
                <div class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#0c2d74] hover:shadow-md transition-all duration-200 group">
                    <div class="flex-shrink-0 relative">
                        <img src="<?= getBaseUrl() ?>/assets/images/team-cem-ustundag.jpg" alt="Cem Üstündağ" class="w-14 h-14 rounded-2xl object-cover object-top shadow-sm">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors">Cem Üstündağ</p>
                        <p class="text-xs text-gray-500 mt-0.5">Office Coordinator</p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> Turkish</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> English</span>
                        </div>
                    </div>
                    <a href="mailto:<?= e($office['email']) ?>" class="flex-shrink-0 text-gray-300 hover:text-[#0c2d74] transition-colors">
                        <i class="ph-fill ph-envelope-simple text-xl"></i>
                    </a>
                </div>

                <!-- Member 2 -->
                <div class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#0c2d74] hover:shadow-md transition-all duration-200 group">
                    <div class="flex-shrink-0 relative">
                        <img src="<?= getBaseUrl() ?>/assets/images/team-erim-ekiz.jpg" alt="Erim Ekiz" class="w-14 h-14 rounded-2xl object-cover object-top shadow-sm">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors">Erim Ekiz</p>
                        <p class="text-xs text-gray-500 mt-0.5">Patient Relations</p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> Turkish</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> English</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> German</span>
                        </div>
                    </div>
                    <a href="mailto:<?= e($office['email']) ?>" class="flex-shrink-0 text-gray-300 hover:text-[#0c2d74] transition-colors">
                        <i class="ph-fill ph-envelope-simple text-xl"></i>
                    </a>
                </div>

                <!-- Member 3 -->
                <div class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#0c2d74] hover:shadow-md transition-all duration-200 group">
                    <div class="flex-shrink-0 relative">
                        <img src="<?= getBaseUrl() ?>/assets/images/team-ionea-ruxandra.jpg" alt="Ionea Ruxandra" class="w-14 h-14 rounded-2xl object-cover object-top shadow-sm">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#0c2d74] transition-colors">Ionea Ruxandra</p>
                        <p class="text-xs text-gray-500 mt-0.5">International Liaison</p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> Romanian</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> English</span>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-[#0c2d74] px-2 py-0.5 rounded-full font-medium"><i class="ph-fill ph-translate text-xs"></i> French</span>
                        </div>
                    </div>
                    <a href="mailto:<?= e($office['email']) ?>" class="flex-shrink-0 text-gray-300 hover:text-[#0c2d74] transition-colors">
                        <i class="ph-fill ph-envelope-simple text-xl"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- ===== Contact Info + Map (existing) ===== -->
    <!-- ===== FAQ ===== -->
    <div class="mt-10 bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-[#E6F0FA] text-[#0c2d74] rounded-xl flex items-center justify-center">
                <i class="ph-fill ph-question text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-[#0c2d74]">Frequently Asked Questions</h3>
        </div>
        <div class="divide-y divide-gray-100" id="faq-accordion">

            <div class="faq-item py-4">
                <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                    <span class="text-sm font-semibold text-gray-900">Is the office a clinic where I can be treated?</span>
                    <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                </button>
                <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                    No. It is a patient-support and information office. Your treatment takes place at an Acıbadem hospital in Turkey. The office team guides you through the entire process — from your first enquiry to your follow-up care after returning home.
                </div>
            </div>

            <div class="faq-item py-4">
                <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                    <span class="text-sm font-semibold text-gray-900">How much will my treatment cost?</span>
                    <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                </button>
                <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                    You receive a personalised, itemised cost estimate after your case is reviewed by the relevant specialist. Prices are confirmed only after a consultation — there is no obligation before that point.
                </div>
            </div>

            <div class="faq-item py-4">
                <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                    <span class="text-sm font-semibold text-gray-900">How do I get a second opinion or online consultation?</span>
                    <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                </button>
                <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                    Send your medical reports, scans and test results to the office team by WhatsApp or email. The team will forward them to the relevant Acıbadem specialist and arrange either an online video consultation or a written second opinion — before you travel.
                </div>
            </div>

            <div class="faq-item py-4">
                <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                    <span class="text-sm font-semibold text-gray-900">Do I need a visa to travel to Turkey?</span>
                    <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                </button>
                <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                    Entry requirements depend on your nationality. Please check the current Turkey entry rules for your country. The office can provide an official invitation letter from Acıbadem where one is needed for visa or entry formalities.
                </div>
            </div>

            <div class="faq-item py-4">
                <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left" onclick="toggleFaq(this)">
                    <span class="text-sm font-semibold text-gray-900">How can I be sure Acıbadem hospitals are safe?</span>
                    <i class="ph ph-caret-down text-[#0c2d74] text-lg flex-shrink-0 transition-transform duration-200"></i>
                </button>
                <div class="faq-answer hidden mt-3 text-sm text-gray-600 leading-relaxed pr-8">
                    Acıbadem operates JCI-accredited hospitals held to international quality and patient-safety standards — the same accreditation used by leading hospitals in the US, UK and across Europe. Each facility is independently audited and certified.
                </div>
            </div>

        </div>
    </div>

    <!-- ===== Contact Info + Map ===== -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Contact Details -->
        <div>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-xl font-bold text-[#0c2d74] mb-6">Contact Information</h3>
                
                <div class="space-y-6">
                    <div class="flex items-start gap-4 pb-4 border-b border-gray-50">
                        <div class="mt-1 bg-gray-50 p-2 rounded-lg text-[#1a4ba0]">
                            <i class="ph-fill ph-map-pin text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 mb-1">Address</div>
                            <div class="text-gray-600 text-sm leading-relaxed"><?= nl2br(e($office['address'])) ?></div>
                        </div>
                    </div>

                    <?php if (!empty($office['phone'])): ?>
                    <div class="flex items-start gap-4 pb-4 border-b border-gray-50">
                        <div class="mt-1 bg-gray-50 p-2 rounded-lg text-[#1a4ba0]">
                            <i class="ph-fill ph-phone text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 mb-1">Phone</div>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $office['phone']) ?>" class="text-gray-600 text-sm hover:text-[#0c2d74]"><?= e($office['phone']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($office['email'])): ?>
                    <div class="flex items-start gap-4">
                        <div class="mt-1 bg-gray-50 p-2 rounded-lg text-[#1a4ba0]">
                            <i class="ph-fill ph-envelope-simple text-xl"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 mb-1">Email</div>
                            <a href="mailto:<?= e($office['email']) ?>" class="text-gray-600 text-sm hover:text-[#0c2d74]"><?= e($office['email']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mini Map -->
        <div>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-2 h-full min-h-[300px]">
                <div id="office-map" class="w-full h-full rounded-2xl z-0 min-h-[300px]"></div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($relatedOffices)): ?>
    <div class="mt-16">
        <h3 class="text-2xl font-bold text-[#0c2d74] mb-6">Other offices in <?= e($office['country']) ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($relatedOffices as $ro): ?>
                <a href="<?= officeUrl($ro['slug']) ?>" class="office-card block bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="flex items-start gap-4">
                        <div class="bg-gray-50 p-3 rounded-xl text-[#0c2d74] group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                            <i class="ph-fill ph-buildings text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 group-hover:text-[#1a4ba0] transition-colors"><?= e($ro['display_name']) ?></h4>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= e($ro['address']) ?></p>
                        </div>
                        <div class="text-gray-300 group-hover:text-[#1a4ba0] transition-colors">
                            <i class="ph ph-arrow-right"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-12 text-center">
        <a href="<?= getBaseUrl() ?>/offices" class="inline-flex items-center gap-2 border-2 border-[#0c2d74] text-[#0c2d74] hover:bg-[#0c2d74] hover:text-white font-semibold py-3 px-8 rounded-xl transition-colors">
            <i class="ph ph-list"></i>
            View All Offices
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
          zoom: 14, 
          singleOffice: true 
      });
  }
});

function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const icon   = btn.querySelector('i');
    const isOpen = !answer.classList.contains('hidden');

    // Close all
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.add('hidden'));
    document.querySelectorAll('.faq-trigger i').forEach(i => i.style.transform = '');

    if (!isOpen) {
        answer.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
