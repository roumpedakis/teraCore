#!/usr/bin/env php
<?php

require_once __DIR__ . '/app/Autoloader.php';
require_once __DIR__ . '/tests/integration/AdminUiTest.php';

use Tests\Integration\AdminUiTest;

echo "╔═══════════════════════════════════════\n";
echo "║   TeraCore Admin UI Tests\n";
echo "╚═══════════════════════════════════════\n";

$ch = curl_init('http://localhost/admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode === 0) {
    echo "\n❌ ERROR: Server is not running at http://localhost\n";
    echo "Start server with: php -S localhost:80 -t public public/router.php\n";
    exit(1);
}

echo "\n✓ Server is running at http://localhost\n";

try {
    $test = new AdminUiTest();

    echo "\n────────────────────────────────────────\n";
    echo "RUNNING ADMIN UI TESTS\n";
    echo "────────────────────────────────────────\n";

    $test->test_admin_requires_login();
    $test->test_admin_login_invalid();
    $test->test_admin_login_success();

    echo "\n════════════════════════════════════════\n";
    echo "✅ ALL TESTS PASSED (3/3)\n";
    echo "════════════════════════════════════════\n";
} catch (\Exception $e) {
    echo "\n❌ TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
