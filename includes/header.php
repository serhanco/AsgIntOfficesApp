<?php
// Ensure variables have default values if not set
$pageTitle = $pageTitle ?? 'Home';
$currentPage = $currentPage ?? 'home';
$needsMap = $needsMap ?? false;
$metaDescription = $metaDescription ?? 'Acıbadem International Offices - Find our global healthcare network locations.';
$ogImage = $ogImage ?? (getBaseUrl() . '/assets/images/og-logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?> | Acıbadem International Offices</title>
    
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> | Acıbadem International Offices">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= getBaseUrl() ?>/assets/images/favicon.png">
    <link rel="icon" type="image/x-icon" href="<?= getBaseUrl() ?>/assets/images/favicon.ico">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        acibadem: {
                            blue: '#0c2d74',
                            light: '#E6F0FA',
                            dark: '#0A1C36',
                            accent: '#1a4ba0'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <?php if ($needsMap): ?>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/style.css">
</head>
<body class="font-sans bg-gray-50 min-h-screen antialiased">
    <!-- Top accent bar -->
    <div class="h-[3px] w-full bg-gradient-to-r from-[#0c2d74] to-[#1a4ba0]"></div>
    
    <!-- Header / Nav -->
    <header class="bg-acibadem-blue sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="<?= getBaseUrl() ?>/" class="flex items-center">
                        <img class="h-8 w-auto" src="<?= getBaseUrl() ?>/assets/images/acibadem-white-logo.webp" alt="Acıbadem Logo">
                    </a>
                </div>
                
                <!-- Desktop Nav -->
                <nav class="hidden md:flex space-x-2">
                    <a href="<?= getBaseUrl() ?>/" class="flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $currentPage === 'home' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        <i class="ph ph-target text-lg mr-2"></i>Nearest Office
                    </a>
                    <a href="<?= getBaseUrl() ?>/map" class="flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $currentPage === 'map' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        <i class="ph ph-globe-hemisphere-west text-lg mr-2"></i>Global Map
                    </a>
                    <a href="<?= getBaseUrl() ?>/offices" class="flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $currentPage === 'offices' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        <i class="ph ph-buildings text-lg mr-2"></i>All Offices
                    </a>
                </nav>
                
                <!-- Mobile Nav Toggle -->
                <div class="md:hidden flex items-center">
                    <button id="burger-btn" type="button" class="text-white hover:text-gray-200 focus:outline-none p-2" onclick="toggleMobileMenu()">
                        <i class="ph ph-list text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Nav Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-acibadem-blue border-t border-white/10">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?= getBaseUrl() ?>/" class="flex items-center px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'home' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i class="ph ph-target text-xl mr-3"></i>Nearest Office
                </a>
                <a href="<?= getBaseUrl() ?>/map" class="flex items-center px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'map' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i class="ph ph-globe-hemisphere-west text-xl mr-3"></i>Global Map
                </a>
                <a href="<?= getBaseUrl() ?>/offices" class="flex items-center px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'offices' ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i class="ph ph-buildings text-xl mr-3"></i>All Offices
                </a>
            </div>
        </div>
    </header>
