<?php

use App\Core\ModuleLoader;

class ModuleLoaderTest extends TestCase {
    
    public function test_load_all_modules() {
        $modules = ModuleLoader::load();
        
        assert_true(is_array($modules));
        assert_true(count($modules) > 0);
        
        // Should have at least users, articles, and comments
        assert_array_key_exists('users', $modules);
        assert_array_key_exists('articles', $modules);
        assert_array_key_exists('comments', $modules);
    }

    public function test_get_module() {
        $usersModule = ModuleLoader::getModule('users');
        
        assert_true(is_array($usersModule));
        assert_array_key_exists('name', $usersModule);
        assert_array_key_exists('metadata', $usersModule);
        assert_equal('users', $usersModule['name']);
    }

    public function test_users_module_is_core() {
        $usersModule = ModuleLoader::getModule('users');
        $metadata = $usersModule['metadata'];
        
        assert_array_key_exists('isCore', $metadata);
        assert_true($metadata['isCore']);
        assert_equal(0, $metadata['price']);
    }

    public function test_articles_module_has_price() {
        $articlesModule = ModuleLoader::getModule('articles');
        $metadata = $articlesModule['metadata'];
        
        assert_array_key_exists('price', $metadata);
        assert_true($metadata['price'] > 0);
        assert_equal('EUR', $metadata['priceCurrency']);
        assert_false($metadata['isCore']);
    }

    public function test_comments_module_has_dependencies() {
        $commentsModule = ModuleLoader::getModule('comments');
        $metadata = $commentsModule['metadata'];
        
        assert_array_key_exists('dependencies', $metadata);
        assert_true(count($metadata['dependencies']) > 0);
        assert_contains(json_encode($metadata['dependencies']), 'users');
        assert_contains(json_encode($metadata['dependencies']), 'articles');
    }

    public function test_get_module_pricing() {
        $pricing = ModuleLoader::getModulePricing();
        
        assert_true(is_array($pricing));
        assert_array_key_exists('users', $pricing);
        assert_array_key_exists('articles', $pricing);
        assert_array_key_exists('comments', $pricing);
        
        // Check structure
        assert_array_key_exists('price', $pricing['users']);
        assert_array_key_exists('currency', $pricing['users']);
        assert_array_key_exists('isCore', $pricing['users']);
    }

    public function test_calculate_module_cost_single() {
        $cost = ModuleLoader::calculateModuleCost(['articles']);
        
        assert_array_key_exists('total', $cost);
        assert_array_key_exists('breakdown', $cost);
        assert_array_key_exists('currency', $cost);
        
        assert_true($cost['total'] > 0);
        assert_equal(1, $cost['count']);
    }

    public function test_calculate_module_cost_multiple() {
        $cost = ModuleLoader::calculateModuleCost(['articles', 'comments']);
        
        assert_true($cost['total'] > 0);
        assert_equal(2, $cost['count']);
        assert_equal(2, $cost['paidModules']);
        
        // Articles + Comments = 9.99 + 4.99 = 14.98
        assert_true(abs($cost['total'] - 14.98) < 0.01);
    }

    public function test_calculate_module_cost_with_core_module() {
        $cost = ModuleLoader::calculateModuleCost(['users', 'articles']);
        
        // Users is core (free), so only articles price should count
        $breakdown = $cost['breakdown'];
        assert_equal(0, $breakdown['users']['price']);
        assert_true($breakdown['articles']['price'] > 0);
        
        // Total should equal only paid modules
        assert_equal(1, $cost['paidModules']);
    }

    public function test_get_core_modules() {
        $coreModules = ModuleLoader::getCoreModules();
        
        assert_true(is_array($coreModules));
        assert_array_key_exists('users', $coreModules);
        
        // Comments and Articles should not be core
        assert_false(isset($coreModules['comments']));
        assert_false(isset($coreModules['articles']));
    }

    public function test_get_paid_modules() {
        $paidModules = ModuleLoader::getPaidModules();
        
        assert_true(is_array($paidModules));
        assert_array_key_exists('articles', $paidModules);
        assert_array_key_exists('comments', $paidModules);
        
        // Users should not be in paid modules
        assert_false(isset($paidModules['users']));
    }

    public function test_get_module_dependencies() {
        $deps = ModuleLoader::getDependencies('comments');
        
        assert_true(is_array($deps));
        assert_true(in_array('users', $deps));
        assert_true(in_array('articles', $deps));
    }

    public function test_validate_dependencies_success() {
        // Comments depends on users and articles which exist
        $missing = ModuleLoader::validateDependencies('comments');
        
        assert_true(is_array($missing));
        assert_equal(0, count($missing));
    }

    public function test_get_nonexistent_module() {
        $module = ModuleLoader::getModule('nonexistent');
        assert_true($module === null);
    }

    public function test_pricing_breakdown_structure() {
        $cost = ModuleLoader::calculateModuleCost(['articles', 'comments']);
        $breakdown = $cost['breakdown'];
        
        // Check each module has proper structure
        foreach ($breakdown as $moduleName => $info) {
            assert_array_key_exists('name', $info);
            assert_array_key_exists('price', $info);
            assert_array_key_exists('currency', $info);
            assert_array_key_exists('billingPeriod', $info);
            assert_array_key_exists('isCore', $info);
        }
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new ModuleLoaderTest();
$test->run();
