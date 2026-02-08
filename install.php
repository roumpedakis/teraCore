#!/usr/bin/env php
<?php

use App\Core\Config;
use App\Core\Logger;
use App\Core\DatabaseInstaller;

// Load autoloader
require_once __DIR__ . '/app/Autoloader.php';

// Initialize
Config::load();
Logger::init();

// Get command from arguments
$command = $argv[1] ?? 'help';

$installer = new DatabaseInstaller();

try {
    switch ($command) {
        case 'install':
            $installer->install();
            break;

        case 'migrate':
            $installer->migrate();
            break;

        case 'install:fresh':
            echo "Are you sure you want to purge all tables? (yes/no): ";
            $answer = trim(fgets(STDIN));
            if ($answer === 'yes') {
                $installer->purge();
                $installer->install();
            } else {
                echo "Cancelled.\n";
            }
            break;

        case 'status':
            $status = $installer->status();
            echo "\n=== Database Installation Status ===\n\n";
            foreach ($status as $module => $info) {
                echo "Module: $module\n";
                echo "  Installed Ver: " . $info['installed_version'] . "\n";
                echo "  Available Ver: " . $info['available_version'] . "\n";
                echo "  Needs Update: " . ($info['needs_update'] ? 'YES' : 'NO') . "\n";
                echo "  Entities: " . implode(', ', $info['entities']) . "\n";
                echo "\n";
            }
            break;

        case 'help':
        default:
            echo <<<HELP
teraCore Database Installer

Usage: php install.php <command>

Commands:
  install              Create all tables from module schemas
  migrate              Update tables if schemas have changed (version check)
  install:fresh        Purge all tables and reinstall from scratch
  status               Show database installation status
  help                 Show this help message

Examples:
  php install.php install
  php install.php migrate
  php install.php status

HELP;
            break;
    }
} catch (\Exception $e) {
    Logger::error("Installation error: " . $e->getMessage());
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone!\n";
exit(0);
