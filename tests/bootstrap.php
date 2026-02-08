<?php

// Load autoloader
require_once __DIR__ . '/../app/Autoloader.php';

// Initialize configuration
use App\Core\Config;
use App\Core\Logger;

Config::load();
Logger::init();

// Simple assertion helpers
function assert_true($condition, $message = '') {
    if (!$condition) {
        throw new AssertionError($message ?: 'Assertion failed');
    }
}

function assert_false($condition, $message = '') {
    if ($condition) {
        throw new AssertionError($message ?: 'Assertion failed');
    }
}

function assert_equal($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new AssertionError($message ?: "Expected: $expected, Got: $actual");
    }
}

function assert_not_equal($expected, $actual, $message = '') {
    if ($expected === $actual) {
        throw new AssertionError($message ?: "Values should not be equal: $expected");
    }
}

function assert_contains($haystack, $needle, $message = '') {
    if (strpos($haystack, $needle) === false) {
        throw new AssertionError($message ?: "$needle not found in $haystack");
    }
}

function assert_array_key_exists($key, $array, $message = '') {
    if (!isset($array[$key])) {
        throw new AssertionError($message ?: "Key '$key' not found in array");
    }
}

class TestCase {
    protected $testName = '';
    protected $testsPassed = 0;
    protected $testsFailed = 0;

    protected function test($name, callable $callback) {
        $this->testName = $name;
        try {
            $callback();
            $this->testsPassed++;
            echo "✓ $name\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "✗ $name\n";
            echo "  Error: " . $e->getMessage() . "\n";
        }
    }

    public function run() {
        echo "\n=== Running " . class_basename($this) . " ===\n";

        foreach (get_class_methods($this) as $method) {
            if (str_starts_with($method, 'test')) {
                $testName = str_replace('test_', '', $method);
                $testName = str_replace('_', ' ', $testName);
                $this->test(ucwords($testName), fn() => $this->$method());
            }
        }

        echo "\n";
        return $this->summarize();
    }

    private function summarize(): bool {
        $total = $this->testsPassed + $this->testsFailed;
        echo "Results: {$this->testsPassed}/{$total} passed\n";
        return $this->testsFailed === 0;
    }
}

function class_basename($class) {
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}
