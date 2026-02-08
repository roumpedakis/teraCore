<?php

namespace Tests\Unit;

use TestCase;
use App\Core\Factory;
use App\Core\Fields\BaseElement;
use App\Core\Fields\TextField;
use App\Core\Fields\NumberBox;
use App\Core\Fields\Price;
use App\Core\FieldTypeLoader;

class FieldTypeTest extends TestCase
{
    public function test_create_text_field() {
        $field = Factory::createFieldType('text');
        assert_true($field instanceof TextField);
        assert_equal('TextField', class_basename($field));
    }

    public function test_create_number_field() {
        $field = Factory::createFieldType('number');
        assert_true($field instanceof NumberBox);
        assert_equal('number', $field->getType());
    }

    public function test_create_price_field_with_currency() {
        $field = Factory::createFieldType('price');
        assert_true($field instanceof Price);
        assert_equal('price', $field->getType());
        assert_equal('EUR', $field->getCurrency());
        assert_equal(2, $field->getDecimalPlaces());
    }

    public function test_field_type_inheritance() {
        $config = FieldTypeLoader::resolveFieldType('price');
        assert_equal('number', $config['extends']);
    }

    public function test_set_and_get_field_value() {
        $field = Factory::createFieldType('text');
        $field->setValue('Hello');
        assert_equal('Hello', $field->getValue());
    }

    public function test_field_validation_required_field() {
        $field = Factory::createFieldType('title');
        $field->setValue('');
        assert_false($field->validate());

        $field->setValue('Valid Title');
        assert_true($field->validate());
    }

    public function test_field_validation_max_length() {
        $field = Factory::createFieldType('title');
        $field->setValue(str_repeat('x', 300));
        assert_false($field->validate());

        $field->setValue('Short Title');
        assert_true($field->validate());
    }

    public function test_number_field_with_min_max() {
        $field = Factory::createFieldType('number', '', ['min' => 0, 'max' => 100]);
        $field->setValue(50);
        assert_true($field->validate());

        $field->setValue(150);
        assert_false($field->validate());

        $field->setValue(-10);
        assert_false($field->validate());
    }

    public function test_price_field_formatting() {
        $field = Factory::createFieldType('price');
        $field->setValue(99.99);
        assert_contains($field->getFormatted(), 'EUR');
    }

    public function test_textfield_is_translatable() {
        $field = Factory::createFieldType('description');
        assert_true($field->isTranslatable());
    }

    public function test_field_to_array() {
        $field = Factory::createFieldType('price');
        $field->setValue(49.99);
        $arr = $field->toArray();
        assert_equal('price', $arr['type']);
        assert_equal(49.99, $arr['value']);
        assert_array_key_exists('currency', $arr);
    }

    public function test_get_field_type_metadata() {
        $meta = Factory::getFieldTypeMetadata('price');
        assert_equal('price', $meta['type']);
        assert_array_key_exists('class', $meta);
        assert_array_key_exists('validators', $meta);
    }

    public function test_load_defaults_from_configuration() {
        $defaults = FieldTypeLoader::loadDefaults();
        assert_array_key_exists('text', $defaults);
        assert_array_key_exists('price', $defaults);
        assert_array_key_exists('number', $defaults);
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new FieldTypeTest();
$test->run();


