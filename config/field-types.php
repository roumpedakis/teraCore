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

    'textarea' => [
        'class' => \App\Core\Fields\TextAreaField::class,
        'extends' => null,
        'validators' => [],
        'max_length' => 65535,
        'metadata' => [
            'description' => 'Multi-line text input',
            'ui_type' => 'textarea',
            'sql_type' => 'TEXT',
        ],
    ],

    'email' => [
        'class' => \App\Core\Fields\EmailField::class,
        'extends' => null,
        'validators' => ['email'],
        'max_length' => 255,
        'metadata' => [
            'description' => 'Email address field',
            'ui_type' => 'email',
            'sql_type' => 'VARCHAR(255)',
        ],
    ],

    'url' => [
        'class' => \App\Core\Fields\UrlField::class,
        'extends' => null,
        'validators' => ['url'],
        'max_length' => 2048,
        'metadata' => [
            'description' => 'URL field',
            'ui_type' => 'url',
            'sql_type' => 'VARCHAR(2048)',
        ],
    ],

    'tel' => [
        'class' => \App\Core\Fields\TelField::class,
        'extends' => null,
        'validators' => ['tel'],
        'max_length' => 20,
        'metadata' => [
            'description' => 'Telephone number field',
            'ui_type' => 'tel',
            'sql_type' => 'VARCHAR(20)',
        ],
    ],

    'password' => [
        'class' => \App\Core\Fields\PasswordField::class,
        'extends' => null,
        'validators' => ['required'],
        'min_length' => 6,
        'metadata' => [
            'description' => 'Password field (hashed on storage)',
            'ui_type' => 'password',
            'sql_type' => 'VARCHAR(255)',
        ],
    ],

    'date' => [
        'class' => \App\Core\Fields\DateField::class,
        'extends' => null,
        'validators' => ['date'],
        'metadata' => [
            'description' => 'Date field (YYYY-MM-DD)',
            'ui_type' => 'date',
            'sql_type' => 'DATE',
        ],
    ],

    'time' => [
        'class' => \App\Core\Fields\TimeField::class,
        'extends' => null,
        'validators' => ['time'],
        'metadata' => [
            'description' => 'Time field (HH:MM:SS)',
            'ui_type' => 'time',
            'sql_type' => 'TIME',
        ],
    ],

    'datetime' => [
        'class' => \App\Core\Fields\DateTimeField::class,
        'extends' => null,
        'validators' => ['datetime'],
        'metadata' => [
            'description' => 'Date and time field',
            'ui_type' => 'datetime-local',
            'sql_type' => 'DATETIME',
        ],
    ],

    'color' => [
        'class' => \App\Core\Fields\ColorField::class,
        'extends' => null,
        'validators' => ['color'],
        'metadata' => [
            'description' => 'Color picker field (#RRGGBB)',
            'ui_type' => 'color',
            'sql_type' => 'VARCHAR(7)',
        ],
    ],

    'checkbox' => [
        'class' => \App\Core\Fields\CheckboxField::class,
        'extends' => null,
        'validators' => [],
        'metadata' => [
            'description' => 'Checkbox field (boolean)',
            'ui_type' => 'checkbox',
            'sql_type' => 'TINYINT(1)',
        ],
    ],

    'radio' => [
        'class' => \App\Core\Fields\RadioField::class,
        'extends' => null,
        'validators' => [],
        'options' => [],
        'metadata' => [
            'description' => 'Radio button group',
            'ui_type' => 'radio',
            'sql_type' => 'VARCHAR(255)',
        ],
    ],

    'select' => [
        'class' => \App\Core\Fields\SelectField::class,
        'extends' => null,
        'validators' => [],
        'options' => [],
        'multiple' => false,
        'metadata' => [
            'description' => 'Dropdown select field',
            'ui_type' => 'select',
            'sql_type' => 'VARCHAR(255)',
        ],
    ],

    'file' => [
        'class' => \App\Core\Fields\FileField::class,
        'extends' => null,
        'validators' => ['file'],
        'allowed_types' => [],
        'max_size' => 10485760, // 10MB
        'metadata' => [
            'description' => 'File upload field',
            'ui_type' => 'file',
            'sql_type' => 'VARCHAR(255)',
        ],
    ],

    'hidden' => [
        'class' => \App\Core\Fields\HiddenField::class,
        'extends' => null,
        'validators' => [],
        'metadata' => [
            'description' => 'Hidden input field',
            'ui_type' => 'hidden',
            'sql_type' => 'VARCHAR(255)',
        ],
    ],
];
