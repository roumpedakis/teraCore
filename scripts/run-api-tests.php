#!/usr/bin/env php
<?php

// Load autoloader
require_once __DIR__ . '/../app/Autoloader.php';

// Load test class directly
require_once __DIR__ . '/../tests/integration/ApiTest.php';

use Tests\Integration\ApiTest;

echo "╔═══════════════════════════════════════\n";
echo "║   TeraCore API Integration Tests\n";
echo "╚═══════════════════════════════════════\n";

// Check if server is running
$baseUrl = getenv('API_BASE_URL') ?: 'http://localhost:8000/api';
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode === 0) {
    echo "\n❌ ERROR: Server is not running at {$baseUrl}\n";
    echo "Start server with: php -S localhost:8000 -t public\n";
    exit(1);
}

echo "\n✓ Server is running at {$baseUrl}\n";

try {
    $test = new ApiTest();
    
    echo "\n────────────────────────────────────────\n";
    echo "RUNNING API INTEGRATION TESTS\n";
    echo "────────────────────────────────────────\n";
    
    // Run all test methods
    $test->test_user_registration();
    $test->test_user_login();
    $test->test_assign_module_access();
    $test->test_verify_token();
    $test->test_refresh_token();
    $test->test_create_article();
    $test->test_articles_require_auth();
    $test->test_get_articles_public();
    $test->test_get_single_article();
    $test->test_articles_pagination();
    $test->test_filter_articles_by_id();
    $test->test_order_articles_desc();
    $test->test_comments_no_access();
    $test->test_articles_create_insufficient();
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
    echo "✅ ALL TESTS PASSED (24/24)\n";
    echo "════════════════════════════════════════\n";
    
} catch (\Exception $e) {
    echo "\n❌ TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
