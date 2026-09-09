<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$offices = getAllOffices();
$pageTitle = 'Global Map';
$currentPage = 'map';
$needsMap = true;
$metaDescription = 'Interactive map of all Acıbadem International information offices worldwide.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="relative w-full">
    <!-- Map Container -->
    <div id="global-map" style="height: calc(100vh - 64px - 3px); width: 100%; z-index: 0;"></div>

    <!-- Floating Header -->
    <div class="absolute top-4 left-1/2 -translate-x-1/2 z-[1000] w-[95%] max-w-xl bg-white/95 backdrop-blur-md shadow-lg rounded-2xl p-4 flex items-center justify-between border border-gray-100">
        <div>
            <h1 class="text-xl font-bold text-[#0c2d74]">Global Network</h1>
            <p class="text-sm text-gray-500">Select a pin to view details</p>
        </div>
        <a href="<?= getBaseUrl() ?>/offices" class="bg-[#0c2d74] text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-[#1a4ba0] transition-colors flex items-center gap-2">
            <i class="ph-fill ph-list-dashes"></i>
            List View
        </a>
    </div>
</div>

<script src="<?= getBaseUrl() ?>/assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const offices = <?= json_encode(array_map(function($o) {
    return [
      'lat' => (float)$o['latitude'],
      'lon' => (float)$o['longitude'],
      'display_name' => $o['display_name'],
      'country' => $o['country'],
      'url' => officeUrl($o['slug'])
    ];
  }, $offices)) ?>;
  
  if (typeof initMap === 'function') {
      initMap('global-map', offices);
  }
});
</script>

</body>
</html>
