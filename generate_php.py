import json

with open("institutions.json", "r") as f:
    institutions = json.load(f)

php_array = ",\n        ".join([f"'{inst}'" for inst in institutions])

php_content = f"""<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contracted Institutions';
$currentPage = 'contracted-institutions';
$metaDescription = 'Explore the list of international insurance and assistance companies partnered with Acıbadem Hospitals Group.';

require_once __DIR__ . '/includes/header.php';

$institutions = [
        {php_array}
];
?>

<div class="relative bg-gradient-to-br from-[#0c2d74] to-[#0A1C36] overflow-hidden pt-32 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">Contracted Institutions</h1>
        <p class="text-xl text-blue-100 max-w-3xl mx-auto">We partner with leading global insurance and assistance providers to ensure seamless healthcare access for international patients.</p>
    </div>
</div>

<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($institutions as $inst): ?>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:border-[#0c2d74] hover:shadow-md transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-[#E6F0FA] text-[#0c2d74] flex items-center justify-center font-bold text-lg flex-shrink-0 group-hover:bg-[#0c2d74] group-hover:text-white transition-colors">
                        <?= substr($inst, 0, 1) ?>
                    </div>
                    <span class="font-bold text-gray-800 group-hover:text-[#0c2d74] transition-colors leading-tight"><?= e($inst) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
"""

with open("contracted-institutions.php", "w") as f:
    f.write(php_content)

print("Created contracted-institutions.php")
