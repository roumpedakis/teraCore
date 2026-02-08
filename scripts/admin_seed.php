<?php

require_once __DIR__ . '/../app/Autoloader.php';

use App\Core\Config;
use App\Core\Logger;
use App\Core\Database;

Config::load();
Logger::init();

$db = Database::getInstance();

$cols = $db->fetchAll("SHOW COLUMNS FROM admins LIKE 'password'");
if (empty($cols)) {
    $db->execute("ALTER TABLE admins ADD COLUMN password VARCHAR(255) NULL AFTER name");
    echo "Added password column\n";
} else {
    echo "Password column exists\n";
}

$username = 'admin';
$password = 'admin';

$existing = $db->fetchAll("SELECT id FROM admins WHERE name = ?", [$username]);
$hash = password_hash($password, PASSWORD_BCRYPT);

if (empty($existing)) {
    $db->execute(
        "INSERT INTO admins (name, password, description, status) VALUES (?,?,?,?)",
        [$username, $hash, 'Seed admin', 'active']
    );
    echo "Seeded admin\n";
} else {
    $db->execute(
        "UPDATE admins SET password = ?, status = 'active' WHERE name = ?",
        [$hash, $username]
    );
    echo "Updated admin password/status\n";
}
