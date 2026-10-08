<?php
// Copy this file to config.php and fill in your database credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'asg_offices');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_SECRET', 'change-this-to-random-string');
define('APP_INSTALLED', false);
define('APP_URL', ''); // e.g. https://offices.example.com (no trailing slash)
// Optional: lets apply_update.php run via ?key=... without logging in to the admin panel.
// Leave empty to allow only admin login or the command line.
define('UPDATE_KEY', '');
// Optional: Google Analytics 4 measurement ID. Defaults to the production ID in includes/header.php; set '' to turn the tag off.
// define('GA4_ID', 'G-XXXXXXXXXX');
// Optional: show the team status saved in the admin panel (default: everyone shows as online).
// define('TEAM_STATUS_LIVE', true);
