<?php

require_once __DIR__ . '/../app/Autoloader.php';

use App\Core\Config;
use App\Core\Logger;
use App\Core\Database;

Config::load();
Logger::init();

$db = Database::getInstance();

$db->execute(
    "DELETE FROM users WHERE username LIKE 'testuser_%' OR username = 'postman_test' OR email LIKE 'updated_%@test.com' OR email LIKE 'testuser_%@test.com'"
);

echo "Removed test/demo users\n";

$demoUsers = [
    [
        'username' => 'maria_admin',
        'email' => 'maria.kosta@example.com',
        'first_name' => 'Μαρία',
        'last_name' => 'Κώστα',
        'is_active' => 1
    ],
    [
        'username' => 'nikos_ops',
        'email' => 'nikos.mavridis@example.com',
        'first_name' => 'Νίκος',
        'last_name' => 'Μαυρίδης',
        'is_active' => 1
    ],
    [
        'username' => 'eleni_support',
        'email' => 'eleni.papadopoulou@example.com',
        'first_name' => 'Ελένη',
        'last_name' => 'Παπαδοπούλου',
        'is_active' => 1
    ]
];

foreach ($demoUsers as $user) {
    $existing = $db->fetchAll(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        [$user['username'], $user['email']]
    );

    $hash = password_hash('demo123', PASSWORD_BCRYPT);

    if (empty($existing)) {
        $db->execute(
            "INSERT INTO users (username, email, password, first_name, last_name, is_active) VALUES (?,?,?,?,?,?)",
            [$user['username'], $user['email'], $hash, $user['first_name'], $user['last_name'], $user['is_active']]
        );
    } else {
        $db->execute(
            "UPDATE users SET email = ?, password = ?, first_name = ?, last_name = ?, is_active = ? WHERE username = ?",
            [$user['email'], $hash, $user['first_name'], $user['last_name'], $user['is_active'], $user['username']]
        );
    }
}

echo "Seeded 3 demo users (password: demo123)\n";
