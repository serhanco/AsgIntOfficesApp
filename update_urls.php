<?php
$files = [
    '404.php',
    'index.php',
    'map.php',
    'office.php',
    'offices.php',
    'includes/header.php',
    'includes/footer.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Replace /index.php with /
    $content = str_replace('<?= getBaseUrl() ?>/index.php', '<?= getBaseUrl() ?>/', $content);
    // Replace /map.php with /map
    $content = str_replace('<?= getBaseUrl() ?>/map.php', '<?= getBaseUrl() ?>/map', $content);
    // Replace /offices.php with /offices
    $content = str_replace('<?= getBaseUrl() ?>/offices.php', '<?= getBaseUrl() ?>/offices', $content);
    // Replace /api.php with /api/offices
    $content = str_replace('<?= getBaseUrl() ?>/api.php', '<?= getBaseUrl() ?>/api/offices', $content);
    
    file_put_contents($file, $content);
}
echo "URLs updated successfully.\n";
