<?php

use App\Core\Libraries\Parser;

class ParserTest extends TestCase {
    
    public function test_parse_json() {
        $json = '{"name":"John","age":30}';
        $result = Parser::parseJson($json);
        
        assert_equal("John", $result['name']);
        assert_equal(30, $result['age']);
    }

    public function test_parse_json_array() {
        $json = '[{"id":1},{"id":2}]';
        $result = Parser::parseJson($json);
        
        assert_equal(2, count($result));
        assert_equal(1, $result[0]['id']);
    }

    public function test_parse_invalid_json() {
        $invalid = '{"invalid": json}';
        
        try {
            Parser::parseJson($invalid);
            assert_false(true, "Should throw exception");
        } catch (\InvalidArgumentException $e) {
            assert_contains($e->getMessage(), "Invalid JSON");
        }
    }

    public function test_parse_form_data() {
        $formData = "name=John&age=30&email=john@example.com";
        $result = Parser::parseFormData($formData);
        
        assert_equal("John", $result['name']);
        assert_equal("30", $result['age']);
        assert_equal("john@example.com", $result['email']);
    }

    public function test_parse_xml() {
        $xml = '<?xml version="1.0"?><root><name>John</name><age>30</age></root>';
        $result = Parser::parseXml($xml);
        
        assert_equal("John", $result['name']);
        assert_equal("30", $result['age']);
    }

    public function test_parse_invalid_xml() {
        $invalid = '<?xml version="1.0"?><root><unclosed>';
        
        try {
            Parser::parseXml($invalid);
            assert_false(true, "Should throw exception");
        } catch (\InvalidArgumentException $e) {
            assert_contains($e->getMessage(), "XML parsing error");
        }
    }

    public function test_parse_by_content_type_json() {
        $json = '{"test":"value"}';
        $result = Parser::parseByContentType($json, 'application/json');
        
        assert_equal("value", $result['test']);
    }

    public function test_parse_by_content_type_form() {
        $form = "field=value&number=123";
        $result = Parser::parseByContentType($form, 'application/x-www-form-urlencoded');
        
        assert_equal("value", $result['field']);
        assert_equal("123", $result['number']);
    }

    public function test_parse_complex_json() {
        $json = '{"user":{"name":"John","roles":["admin","user"]},"active":true}';
        $result = Parser::parseJson($json);
        
        assert_equal("John", $result['user']['name']);
        assert_equal(2, count($result['user']['roles']));
        assert_true($result['active']);
    }

    public function test_parse_empty_json_object() {
        $json = '{}';
        $result = Parser::parseJson($json);
        
        assert_equal(0, count($result));
    }

    public function test_parse_null_json() {
        $json = 'null';
        $result = Parser::parseJson($json);
        
        assert_equal([], $result);
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new ParserTest();
$test->run();
