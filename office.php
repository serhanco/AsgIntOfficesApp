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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
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
        <a href="<?= getBaseUrl() ?>/offices.php" class="inline-flex items-center gap-2 border-2 border-[#0c2d74] text-[#0c2d74] hover:bg-[#0c2d74] hover:text-white font-semibold py-3 px-8 rounded-xl transition-colors">
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
