#!/usr/bin/env php
<?php

/**
 * Module Pricing Demo
 * Demonstrates the module pricing system
 */

require_once __DIR__ . '/app/Autoloader.php';

use App\Core\ModuleLoader;
use App\Core\Config;
use App\Core\Logger;

Config::load();
Logger::init();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════\n";
echo "║   Module Pricing System Demo\n";
echo "╚═══════════════════════════════════════════════════════════\n\n";

// 1. Load all modules
echo "1️⃣  Loading modules...\n";
$modules = ModuleLoader::load();
echo "   ✓ Loaded " . count($modules) . " modules\n\n";

// 2. Show all module pricing
echo "2️⃣  Module Pricing:\n";
echo "   ────────────────────────────────────────────\n";
$pricing = ModuleLoader::getModulePricing();
foreach ($pricing as $name => $info) {
    $price = $info['isCore'] ? 'FREE (Core)' : "€{$info['price']}/{$info['billingPeriod']}";
    $deps = ModuleLoader::getDependencies($name);
    $depsStr = empty($deps) ? 'None' : implode(', ', $deps);
    
    echo "   📦 {$name}\n";
    echo "      Price: {$price}\n";
    echo "      Description: {$info['description']}\n";
    echo "      Dependencies: {$depsStr}\n";
    echo "\n";
}

// 3. Calculate cost for different scenarios
echo "3️⃣  Cost Calculations:\n";
echo "   ────────────────────────────────────────────\n";

// Scenario 1: User with basic modules
$basicModules = ['users', 'articles'];
$basicCost = ModuleLoader::calculateModuleCost($basicModules);
echo "   📊 Basic Plan (users + articles):\n";
echo "      Monthly Cost: €{$basicCost['total']}\n";
echo "      Active Modules: {$basicCost['count']}\n";
echo "      Paid Modules: {$basicCost['paidModules']}\n\n";

// Scenario 2: User with all modules
$premiumModules = ['users', 'articles', 'comments'];
$premiumCost = ModuleLoader::calculateModuleCost($premiumModules);
echo "   💎 Premium Plan (all modules):\n";
echo "      Monthly Cost: €{$premiumCost['total']}\n";
echo "      Active Modules: {$premiumCost['count']}\n";
echo "      Paid Modules: {$premiumCost['paidModules']}\n\n";

// 4. Show detailed breakdown
echo "4️⃣  Detailed Breakdown (Premium Plan):\n";
echo "   ────────────────────────────────────────────\n";
foreach ($premiumCost['breakdown'] as $module => $info) {
    $icon = $info['isCore'] ? '🆓' : '💰';
    $price = $info['isCore'] ? 'FREE' : "€{$info['price']}";
    echo "   {$icon} {$module}: {$price}\n";
}
echo "\n";

// 5. Core vs Paid modules
echo "5️⃣  Module Categories:\n";
echo "   ────────────────────────────────────────────\n";

$coreModules = ModuleLoader::getCoreModules();
echo "   🆓 Core Modules (Always Free):\n";
foreach ($coreModules as $name => $module) {
    echo "      - {$name}\n";
}
echo "\n";

$paidModules = ModuleLoader::getPaidModules();
echo "   💰 Paid Modules:\n";
foreach ($paidModules as $name => $module) {
    $price = $module['metadata']['price'];
    echo "      - {$name}: €{$price}/month\n";
}
echo "\n";

// 6. Dependency validation
echo "6️⃣  Dependency Validation:\n";
echo "   ────────────────────────────────────────────\n";
foreach ($modules as $name => $module) {
    $missing = ModuleLoader::validateDependencies($name);
    $status = empty($missing) ? '✓ OK' : '✗ Missing: ' . implode(', ', $missing);
    echo "   {$name}: {$status}\n";
}
echo "\n";

// 7. Summary
echo "╔═══════════════════════════════════════════════════════════\n";
echo "║   Summary\n";
echo "╚═══════════════════════════════════════════════════════════\n";
echo "Total Modules: " . count($modules) . "\n";
echo "Core (Free): " . count($coreModules) . "\n";
echo "Paid: " . count($paidModules) . "\n";
echo "\nBasic Plan: €{$basicCost['total']}/month\n";
echo "Premium Plan: €{$premiumCost['total']}/month\n";
echo "\n";
