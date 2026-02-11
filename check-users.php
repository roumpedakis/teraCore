<?php
require 'app/Autoloader.php';

use App\Core\Config;
use App\Core\Database;

Config::load();
$db = Database::getInstance();

// Check john_doe
echo "=== Checking john_doe ===\n";
$user = $db->fetch('SELECT username, is_active, password FROM users WHERE username = ?', ['john_doe']);
if ($user) {
    echo "Username: " . $user['username'] . "\n";
    echo "Active: " . ($user['is_active'] ? 'YES' : 'NO') . "\n";
    echo "Password hash exists: " . (!empty($user['password']) ? 'YES' : 'NO') . "\n";
    echo "Verify 'password123': " . (password_verify('password123', $user['password']) ? 'YES' : 'NO') . "\n";
} else {
    echo "User not found\n";
}

// Check admin
echo "\n=== Checking admin ===\n";
$admin = $db->fetch('SELECT username, is_active, password FROM users WHERE username = ?', ['admin']);
if ($admin) {
    echo "Username: " . $admin['username'] . "\n";
    echo "Active: " . ($admin['is_active'] ? 'YES' : 'NO') . "\n";
    echo "Password hash exists: " . (!empty($admin['password']) ? 'YES' : 'NO') . "\n";
    echo "Verify 'admin123': " . (password_verify('admin123', $admin['password']) ? 'YES' : 'NO') . "\n";
} else {
    echo "User 'admin' not found - needs to be created\n";
}

// Create admin if not exists
if (!$admin) {
    echo "\n=== Creating admin user ===\n";
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $db->execute(
        "INSERT INTO users (username, email, password, is_active, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)",
        ['admin', 'admin@example.com', $hashedPassword, 1, 'Admin', 'User']
    );
    echo "Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
}

// Activate john_doe if not active
if ($user && !$user['is_active']) {
    echo "\n=== Activating john_doe ===\n";
    $db->execute("UPDATE users SET is_active = 1 WHERE username = ?", ['john_doe']);
    echo "john_doe activated!\n";
}
