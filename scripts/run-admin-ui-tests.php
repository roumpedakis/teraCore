#!/usr/bin/env php
<?php

require_once __DIR__ . '/../app/Autoloader.php';
require_once __DIR__ . '/../tests/integration/AdminUiTest.php';

use Tests\Integration\AdminUiTest;

echo "╔═══════════════════════════════════════\n";
echo "║   TeraCore Admin UI Tests\n";
echo "╚═══════════════════════════════════════\n";

$baseUrl = getenv('ADMIN_UI_BASE_URL') ?: 'http://localhost';
$ch = curl_init($baseUrl . '/admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode === 0) {
    echo "\n❌ ERROR: Server is not running at {$baseUrl}\n";
    echo "Start server with: php -S localhost:80 -t public public/router.php\n";
    exit(1);
}

echo "\n✓ Server is running at {$baseUrl}\n";

try {
    $test = new AdminUiTest($baseUrl);

    echo "\n────────────────────────────────────────\n";
    echo "RUNNING ADMIN UI TESTS\n";
    echo "────────────────────────────────────────\n";

    $test->test_admin_requires_login();
    $test->test_admin_login_invalid();
    $test->test_admin_login_success();
    $test->test_admin_modules_requires_login();
    $test->test_admin_permissions_requires_login();
    $test->test_admin_modules_page_loads();
    $test->test_admin_permissions_page_loads();

    echo "\n════════════════════════════════════════\n";
    echo "✅ ALL TESTS PASSED (7/7)\n";
    echo "════════════════════════════════════════\n";
} catch (\Exception $e) {
    echo "\n❌ TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
