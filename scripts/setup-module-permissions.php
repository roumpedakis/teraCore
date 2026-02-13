#!/usr/bin/env php
<?php

/**
 * Setup Module Permission System
 * Creates user_modules table and initializes sample data
 */

require_once __DIR__ . '/../app/Autoloader.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

Config::load();
Logger::init();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════\n";
echo "║   Module Permission System Setup\n";
echo "╚═══════════════════════════════════════════════════════════\n\n";

try {
    $db = Database::getInstance();
    
    // 1. Create user_modules table
    echo "1️⃣  Creating user_modules table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS user_modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module_name VARCHAR(50) NOT NULL,
        permission_level INT NOT NULL DEFAULT 0,
        enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_module (user_id, module_name),
        INDEX idx_user (user_id),
        INDEX idx_module (module_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->execute($sql);
    echo "   ✓ Table created successfully\n\n";

    // 1b. Create user_module_purchases table
    echo "1️⃣  Creating user_module_purchases table...\n";

    $purchaseSql = "
    CREATE TABLE IF NOT EXISTS user_module_purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module_name VARCHAR(50) NOT NULL,
        status ENUM('active','canceled','refunded') NOT NULL DEFAULT 'active',
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
        billing_period VARCHAR(20) NOT NULL DEFAULT 'monthly',
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        canceled_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_module (user_id, module_name),
        INDEX idx_user (user_id),
        INDEX idx_module (module_name),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $db->execute($purchaseSql);
    echo "   ✓ Table created successfully\n\n";
    
    // 2. Get first user
    echo "2️⃣  Setting up sample user modules...\n";
    
    $users = $db->fetchAll("SELECT id, username FROM users ORDER BY id LIMIT 1");
    
    if (empty($users)) {
        echo "   ⚠️  No users found. Please create a user first.\n";
        echo "   Skipping module assignment...\n\n";
    } else {
        $userId = $users[0]['id'];
        $username = $users[0]['username'];
        
        echo "   Assigning modules to user: {$username} (ID: {$userId})\n";
        
        // Assign modules with different permission levels
        $modules = [
            'articles' => 7,    // Read/Write (READ=1 + CREATE=2 + UPDATE=4)
            'comments' => 3,    // Read + Create (READ=1 + CREATE=2)
        ];
        
        foreach ($modules as $moduleName => $permission) {
            $permissionName = getPermissionName($permission);
            
            $db->execute("
                INSERT INTO user_modules (user_id, module_name, permission_level)
                VALUES (:user_id, :module_name, :permission)
                ON DUPLICATE KEY UPDATE permission_level = VALUES(permission_level)
            ", [
                'user_id' => $userId,
                'module_name' => $moduleName,
                'permission' => $permission
            ]);

            $db->execute("
                INSERT INTO user_module_purchases (user_id, module_name, status)
                VALUES (:user_id, :module_name, 'active')
                ON DUPLICATE KEY UPDATE status = 'active', canceled_at = NULL, updated_at = CURRENT_TIMESTAMP
            ", [
                'user_id' => $userId,
                'module_name' => $moduleName
            ]);
            
            echo "   ✓ {$moduleName}: {$permissionName} ({$permission})\n";
        }
        
        echo "\n";
    }
    
    // 3. Summary
    echo "3️⃣  System Summary:\n";
    echo "   ────────────────────────────────────────────\n";
    
    $totalUsers = $db->fetchAll("SELECT COUNT(*) as count FROM users")[0]['count'];
    $totalModuleAssignments = $db->fetchAll("SELECT COUNT(*) as count FROM user_modules")[0]['count'];
    $usersWithModules = $db->fetchAll("SELECT COUNT(DISTINCT user_id) as count FROM user_modules")[0]['count'];
    
    echo "   Total Users: {$totalUsers}\n";
    echo "   Users with Modules: {$usersWithModules}\n";
    echo "   Total Module Assignments: {$totalModuleAssignments}\n";
    
    echo "\n╔═══════════════════════════════════════════════════════════\n";
    echo "║   Setup Complete! ✅\n";
    echo "╚═══════════════════════════════════════════════════════════\n";
    echo "\nNext steps:\n";
    echo "1. Login to generate JWT with module permissions\n";
    echo "2. Access Admin UI at: /admin/modules\n";
    echo "3. Use API endpoints:\n";
    echo "   - GET  /api/modules\n";
    echo "   - GET  /api/users/{id}/modules\n";
    echo "   - POST /api/users/{id}/modules\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

function getPermissionName($permission) {
    $permissions = [];
    if ($permission & 1) $permissions[] = 'READ';
    if ($permission & 2) $permissions[] = 'CREATE';
    if ($permission & 4) $permissions[] = 'UPDATE';
    if ($permission & 8) $permissions[] = 'DELETE';
    return implode('+', $permissions) ?: 'NONE';
}
