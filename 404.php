<?php
if (!defined('APP_INSTALLED')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/functions.php';
}

http_response_code(404);
$pageTitle = 'Page Not Found';
$currentPage = '';
$needsMap = false;
$metaDescription = 'The page you are looking for could not be found.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[70vh] flex items-center justify-center py-20 px-4 bg-gray-50">
    <div class="text-center max-w-xl mx-auto bg-white p-12 rounded-3xl shadow-sm border border-gray-100">
        <div class="text-[8rem] md:text-[10rem] font-black text-[#0c2d74]/10 leading-none select-none mb-4 flex justify-center items-center gap-4">
            4<i class="ph-fill ph-map-pin-slash text-[#1a4ba0]/20"></i>4
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-[#0c2d74] mb-4">Page Not Found</h1>
        <p class="text-lg text-gray-500 mb-10">
            The office or page you're looking for doesn't exist or has been moved.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <a href="<?= getBaseUrl() ?>/index.php" class="bg-[#0c2d74] text-white hover:bg-[#1a4ba0] font-semibold py-3 px-8 rounded-xl shadow transition-colors w-full sm:w-auto">
                Go to Homepage
            </a>
            <a href="<?= getBaseUrl() ?>/offices.php" class="bg-gray-100 text-[#0c2d74] hover:bg-gray-200 font-semibold py-3 px-8 rounded-xl transition-colors w-full sm:w-auto">
                View All Offices
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
