#!/usr/bin/env php
<?php

echo "╔═══════════════════════════════════════\n";
echo "║   teraCore Unit Test Runner\n";
echo "╚═══════════════════════════════════════\n\n";

$testDir = __DIR__ . '/unit';
$files = glob("$testDir/*.php");

if (empty($files)) {
    echo "No tests found in $testDir\n";
    exit(1);
}

$totalPassed = 0;
$totalFailed = 0;

foreach ($files as $file) {
    echo "Running: " . basename($file) . "\n";
    echo str_repeat("─", 40) . "\n";
    
    ob_start();
    try {
        require_once $file;
    } catch (Exception $e) {
        echo "Error running test: " . $e->getMessage() . "\n";
    }
    $output = ob_get_clean();
    
    // Extract test results from output
    if (preg_match('/Results: (\d+)\/(\d+) passed/', $output, $matches)) {
        $passed = (int)$matches[1];
        $total = (int)$matches[2];
        $failed = $total - $passed;
        
        $totalPassed += $passed;
        $totalFailed += $failed;
        
        echo $output;
    }
}

echo "\n╔═══════════════════════════════════════\n";
echo "║   SUMMARY\n";
echo "╚═══════════════════════════════════════\n\n";

echo "Total Passed: $totalPassed\n";
echo "Total Failed: $totalFailed\n";
echo "Total Tests: " . ($totalPassed + $totalFailed) . "\n\n";

if ($totalFailed === 0) {
    echo "✅ All tests passed!\n";
    exit(0);
} else {
    echo "❌ Some tests failed!\n";
    exit(1);
}
