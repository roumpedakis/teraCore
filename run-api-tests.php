#!/usr/bin/env php
<?php

// Load autoloader
require_once __DIR__ . '/app/Autoloader.php';

// Load test class directly
require_once __DIR__ . '/tests/integration/ApiTest.php';

use Tests\Integration\ApiTest;

echo "╔═══════════════════════════════════════\n";
echo "║   TeraCore API Integration Tests\n";
echo "╚═══════════════════════════════════════\n";

// Check if server is running
$ch = curl_init('http://localhost:8000/api');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode === 0) {
    echo "\n❌ ERROR: PHP server is not running at http://localhost:8000\n";
    echo "Start server with: php -S localhost:8000 -t public\n";
    exit(1);
}

echo "\n✓ Server is running at http://localhost:8000\n";

try {
    $test = new ApiTest();
    
    echo "\n────────────────────────────────────────\n";
    echo "RUNNING API INTEGRATION TESTS\n";
    echo "────────────────────────────────────────\n";
    
    // Run all test methods
    $test->test_user_registration();
    $test->test_user_login();
    $test->test_verify_token();
    $test->test_refresh_token();
    $test->test_create_article();
    $test->test_get_articles_public();
    $test->test_get_single_article();
    $test->test_update_article();
    $test->test_get_user_profile();
    $test->test_update_user_profile();
    $test->test_admin_blocked();
    $test->test_user_create_blocked();
    $test->test_user_delete_blocked();
    $test->test_invalid_token();
    $test->test_delete_article();
    $test->test_logout();
    $test->test_documentation();
    
    echo "\n════════════════════════════════════════\n";
    echo "✅ ALL TESTS PASSED (17/17)\n";
    echo "════════════════════════════════════════\n";
    
} catch (\Exception $e) {
    echo "\n❌ TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
