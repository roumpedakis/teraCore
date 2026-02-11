#!/usr/bin/env php
<?php
/**
 * Cleanup Orphaned Module Permissions
 * 
 * This script removes permissions for modules that:
 * - No longer exist in the filesystem
 * - Are not installed (not in config/modules.json)
 * - Are core modules (users should have automatic access)
 * 
 * Usage: php scripts/cleanup_orphaned_permissions.php
 */

require_once __DIR__ . '/../app/Autoloader.php';

use App\Core\Database;
use App\Core\Config;
use App\Core\Logger;

// Initialize
Config::load();
$db = Database::getInstance();

echo "🧹 Cleanup Orphaned Module Permissions\n";
echo "=====================================\n\n";

// Get installed modules from config
$modulesFile = __DIR__ . '/../config/modules.json';
$installedModules = [];

if (!file_exists($modulesFile)) {
    echo "❌ Error: modules.json not found\n";
    exit(1);
}

$modulesData = json_decode(file_get_contents($modulesFile), true);
if (!$modulesData) {
    echo "❌ Error: Could not parse modules.json\n";
    exit(1);
}

// Get list of non-core installed modules
$availableModules = [];
foreach ($modulesData as $name => $info) {
    $initFile = __DIR__ . '/../app/modules/' . $name . '/init.json';
    if (file_exists($initFile)) {
        $initData = json_decode(file_get_contents($initFile), true);
        // Only include non-core modules for permissions
        if (empty($initData['isCore']) || $initData['isCore'] !== true) {
            $availableModules[] = $name;
        }
    }
}

echo "📦 Installed non-core modules:\n";
foreach ($availableModules as $module) {
    echo "   - $module\n";
}
echo "\n";

// Get all module permissions from database
$sql = "SELECT DISTINCT module_name FROM user_modules";
$dbModules = $db->fetchAll($sql, []);

$orphanedModules = [];
foreach ($dbModules as $row) {
    $moduleName = $row['module_name'];
    if (!in_array($moduleName, $availableModules)) {
        $orphanedModules[] = $moduleName;
    }
}

if (empty($orphanedModules)) {
    echo "✅ No orphaned permissions found!\n";
    exit(0);
}

echo "🗑️  Orphaned modules found in database:\n";
foreach ($orphanedModules as $module) {
    echo "   - $module\n";
}
echo "\n";

// Ask for confirmation (skip if --force flag)
$forceMode = in_array('--force', $argv);

if (!$forceMode) {
    echo "⚠️  This will delete all permissions for these modules.\n";
    echo "Continue? (yes/no): ";
    
    if (PHP_SAPI === 'cli') {
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
    } else {
        $line = 'no';
    }
    
    if (strtolower($line) !== 'yes') {
        echo "❌ Cancelled.\n";
        exit(0);
    }
} else {
    echo "🚀 Running in FORCE mode (no confirmation)\n\n";
}

// Delete orphaned permissions
echo "\n🔄 Cleaning up...\n";
$deletedCount = 0;

foreach ($orphanedModules as $module) {
    $sql = "DELETE FROM user_modules WHERE module_name = :module_name";
    $db->execute($sql, ['module_name' => $module]);
    
    // Get row count from last operation
    $count = $db->getConnection()->exec("SELECT ROW_COUNT()");
    if ($count === false) {
        $count = 0; // Fallback
    }
    
    echo "   ✅ Removed permission(s) for module: $module\n";
    $deletedCount++;
    
    Logger::info('Orphaned permissions cleaned', [
        'module' => $module
    ]);
}

echo "\n✅ Cleanup complete! Removed $deletedCount orphaned module(s).\n";
