<?php

namespace App\Core\Fields;

class ColorField extends BaseField
{
    public function getSqlType(): string
    {
        return 'VARCHAR(7)';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        // Validate hex color format #RRGGBB
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$value) === 1;
    }

    public function getHtmlType(): string
    {
        return 'color';
    }

    public function transformForStorage(mixed $value): string
    {
        return strtoupper(trim((string)$value));
    }
}
