<?php

use App\Core\Libraries\Sanitizer;
use Tests\TestCase;

class SanitizerTest extends TestCase {
    
    public function test_sanitize_string() {
        $input = "<script>alert('xss')</script>";
        $result = Sanitizer::sanitizeString($input);
        
        assert_contains($result, "&lt;script&gt;");
        assert_false(strpos($result, "<script>") !== false);
    }

    public function test_sanitize_email() {
        $validEmail = "john@example.com";
        $result = Sanitizer::sanitizeEmail($validEmail);
        
        assert_equal($validEmail, $result);
    }

    public function test_sanitize_email_invalid() {
        $invalidEmail = "not-an-email";
        $result = Sanitizer::sanitizeEmail($invalidEmail);
        
        assert_equal("", $result);
    }

    public function test_sanitize_int() {
        $input = "123abc";
        $result = Sanitizer::sanitizeInt($input);
        
        assert_equal(123, $result);
    }

    public function test_sanitize_float() {
        $input = "123.45abc";
        $result = Sanitizer::sanitizeFloat($input);
        
        assert_equal(123.45, $result);
    }

    public function test_validate_email() {
        assert_true(Sanitizer::validateEmail("john@example.com"));
        assert_false(Sanitizer::validateEmail("not-email"));
    }

    public function test_validate_url() {
        assert_true(Sanitizer::validateUrl("https://example.com"));
        assert_false(Sanitizer::validateUrl("not a url"));
    }

    public function test_validate_int() {
        assert_true(Sanitizer::validateInt(123));
        assert_true(Sanitizer::validateInt("456"));
        assert_false(Sanitizer::validateInt("abc"));
    }

    public function test_validate_min_length() {
        assert_true(Sanitizer::validateMinLength("password123", 8));
        assert_false(Sanitizer::validateMinLength("pass", 8));
    }

    public function test_validate_max_length() {
        assert_true(Sanitizer::validateMaxLength("password", 10));
        assert_false(Sanitizer::validateMaxLength("verylongpassword", 10));
    }

    public function test_validate_required() {
        assert_true(Sanitizer::validateRequired("value"));
        assert_false(Sanitizer::validateRequired(""));
        assert_false(Sanitizer::validateRequired(null));
    }

    public function test_strip_tags() {
        $input = "<p>Hello <b>World</b></p>";
        $result = Sanitizer::stripTags($input);
        
        assert_equal("Hello World", $result);
    }

    public function test_escape_html() {
        $input = '<script>alert("xss")</script>';
        $result = Sanitizer::escapeHtml($input);
        
        assert_contains($result, "&lt;");
        assert_contains($result, "&gt;");
    }

    public function test_sanitize_url() {
        $url = "https://example.com/path?query=value";
        $result = Sanitizer::sanitizeUrl($url);
        
        assert_equal($url, $result);
    }

    public function test_validate_regex_pattern() {
        $pattern = '/^[a-z0-9]+@[a-z0-9]+\.[a-z]+$/i';
        assert_true(Sanitizer::validatePattern("test@example.com", $pattern));
        assert_false(Sanitizer::validatePattern("invalid", $pattern));
    }
}

require_once __DIR__ . '/bootstrap.php';
$test = new SanitizerTest();
$test->run();
