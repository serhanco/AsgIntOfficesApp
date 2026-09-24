<?php
/**
 * Acıbadem Micro App - Safe Patch & Backup Script
 * 
 * 1. Creates a backup of the current 'offices' table in the /backups/ directory
 * 2. Applies the SQL patch from database/patch_teams_activities.sql
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Simple security parameter so random users can't trigger this via URL
// Usage: apply_update.php?key=acb2026
$key = $_GET['key'] ?? '';
if ($key !== 'acb2026') {
    die('<div style="font-family:sans-serif; text-align:center; padding: 50px; color: red;"><h2>Unauthorized</h2><p>Please provide the correct security key in the URL.</p></div>');
}

echo '<div style="font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 40px;">';
echo '<h2>⚙️ System Update & Backup</h2><hr><br>';

try {
    $db = getDb();
    
    // ==========================================
    // 1. BACKUP PROCESS
    // ==========================================
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Ymd_His');
    $backupFile = $backupDir . '/db_backup_' . $timestamp . '.json';
    
    $backupData = [];
    
    // We backup the main 'offices' table.
    $stmt = $db->query("SELECT * FROM offices");
    $backupData['offices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Try backing up existing teams/activities if they exist
    try {
        $stmt = $db->query("SELECT * FROM office_teams");
        $backupData['office_teams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {} // Ignore if table doesn't exist yet
    
    try {
        $stmt = $db->query("SELECT * FROM office_activities");
        $backupData['office_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
    
    file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "<p style='color: green;'>✅ <b>Backup created successfully:</b> /backups/db_backup_{$timestamp}.json</p>";
    
    // ==========================================
    // 2. APPLY SQL PATCH
    // ==========================================
    $patchFile = __DIR__ . '/database/patch_teams_activities.sql';
    
    if (file_exists($patchFile)) {
        $sql = file_get_contents($patchFile);
        
        // Split by semicolon because PDO might not emulate multiple queries natively on all servers
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        foreach ($statements as $stmtSql) {
            if (empty($stmtSql)) continue;
            
            try {
                $db->exec($stmtSql);
                $successCount++;
            } catch (\PDOException $e) {
                echo "<p style='color: red;'>❌ <b>Error executing statement:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre style='background:#f4f4f4; padding:10px; font-size:12px;'>" . htmlspecialchars($stmtSql) . "</pre>";
            }
        }
        echo "<p style='color: green;'>✅ <b>Patch applied successfully!</b> ($successCount statements executed)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ <b>Warning:</b> Patch file not found at " . htmlspecialchars($patchFile) . "</p>";
    }
    
} catch (\Throwable $e) {
    echo "<p style='color: red;'>❌ <b>Critical Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo '<br><hr>';
echo '<p style="color: #666; font-size: 14px;">🎉 Update complete. For security reasons, please delete this file from your server after confirming everything works.</p>';
echo '</div>';
