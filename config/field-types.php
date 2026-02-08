<?php

/**
 * Field Type Definitions - Default Configuration
 * 
 * Defines field types with inheritance and configuration.
 * Can be overridden per-entity by JSON files in config/{entity}/field-types.json
 */

return [
    'text' => [
        'class' => \App\Core\Fields\TextField::class,
        'extends' => null,
        'validators' => [],
        'metadata' => [
            'description' => 'Simple text input',
            'ui_type' => 'text',
        ],
    ],

    'number' => [
        'class' => \App\Core\Fields\NumberBox::class,
        'extends' => null,
        'validators' => ['numeric'],
        'decimals' => 0,
        'metadata' => [
            'description' => 'Numeric input',
            'ui_type' => 'number',
        ],
    ],

    'price' => [
        'class' => \App\Core\Fields\Price::class,
        'extends' => 'number',
        'validators' => ['numeric', 'positive'],
        'decimals' => 2,
        'currency' => 'EUR',
        'metadata' => [
            'description' => 'Price field with currency',
            'ui_type' => 'price',
        ],
    ],

    'decimal' => [
        'class' => \App\Core\Fields\NumberBox::class,
        'extends' => null,
        'validators' => ['numeric'],
        'decimals' => 2,
        'metadata' => [
            'description' => 'Decimal number input',
            'ui_type' => 'decimal',
        ],
    ],

    'title' => [
        'class' => \App\Core\Fields\TextField::class,
        'extends' => 'text',
        'validators' => ['required'],
        'max_length' => 255,
        'translatable' => true,
        'metadata' => [
            'description' => 'Translatable title field',
            'ui_type' => 'text',
        ],
    ],

    'description' => [
        'class' => \App\Core\Fields\TextField::class,
        'extends' => 'text',
        'validators' => [],
        'translatable' => true,
        'metadata' => [
            'description' => 'Translatable description field',
            'ui_type' => 'textarea',
        ],
    ],
];
