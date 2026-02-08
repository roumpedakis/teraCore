<?php

use App\Core\Config;
use App\Core\Logger;
use App\Core\DatabaseInstaller;

// Simple script to create database
$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbName = 'teracore_db';

try {
    // Connect to MySQL without database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    
    // Drop old database if exists
    $pdo->exec("DROP DATABASE IF EXISTS $dbName");
    echo "✓ Old database dropped\n";
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Fresh database created!\n\n";
    
    // Now load the autoloader
    require_once __DIR__ . '/app/Autoloader.php';
    
    Config::load();
    Logger::init();
    
    echo "\n--- Running Database Installer ---\n";
    
    $installer = new DatabaseInstaller();
    
    echo "\nInstallation Status:\n";
    $status = $installer->status();
    foreach ($status as $module => $info) {
        echo "  $module: " . $info['installed_version'] . "\n";
    }
    
    echo "\nInstalling tables...\n";
    $installer->install();
    
    echo "\n✓ Installation completed!\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
