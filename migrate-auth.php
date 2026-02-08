<?php

// Load autoloader
require_once __DIR__ . '/app/Autoloader.php';

use App\Core\Config;
use App\Core\Database;

// Load environment
Config::load();

// Get database connection
$db = Database::getInstance();

try {
    echo "🔄 Checking users table structure...\n\n";
    
    // Check if columns exist
    $result = $db->fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users'", [
        Config::get('DB_NAME')
    ]);
    
    $columns = array_column($result, 'COLUMN_NAME');
    echo "Current columns: " . implode(', ', $columns) . "\n\n";
    
    // Add missing columns
    $alterStatements = [];
    
    if (!in_array('refresh_token', $columns)) {
        $alterStatements[] = "ALTER TABLE users ADD COLUMN refresh_token VARCHAR(500) NULL DEFAULT NULL";
        echo "✓ Adding refresh_token column\n";
    }
    
    if (!in_array('token_expires_at', $columns)) {
        $alterStatements[] = "ALTER TABLE users ADD COLUMN token_expires_at DATETIME NULL DEFAULT NULL";
        echo "✓ Adding token_expires_at column\n";
    }
    
    if (!in_array('oauth2_provider', $columns)) {
        $alterStatements[] = "ALTER TABLE users ADD COLUMN oauth2_provider VARCHAR(50) NULL DEFAULT NULL";
        echo "✓ Adding oauth2_provider column\n";
    }
    
    // Execute ALTER statements
    foreach ($alterStatements as $stmt) {
        echo "Executing: $stmt\n";
        $db->execute($stmt);
    }
    
    // Add index on token_expires_at if not exists
    echo "\n✓ Adding index on token_expires_at\n";
    try {
        $db->execute("ALTER TABLE users ADD INDEX idx_token_expires_at (token_expires_at)");
    } catch (\Exception $e) {
        echo "  (Index may already exist)\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n\n";
    
    // Verify
    $result = $db->fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users'", [
        Config::get('DB_NAME')
    ]);
    
    $columns = array_column($result, 'COLUMN_NAME');
    echo "Updated columns: " . implode(', ', $columns) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
