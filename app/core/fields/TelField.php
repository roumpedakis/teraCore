<?php

namespace App\Core\Fields;

class TelField extends BaseField
{
    public function getSqlType(): string
    {
        return 'VARCHAR(20)';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        // Basic phone validation: digits, spaces, +, -, (, )
        return preg_match('/^[0-9+\-\s()]+$/', (string)$value) === 1;
    }

    public function getHtmlType(): string
    {
        return 'tel';
    }

    public function transformForStorage(mixed $value): string
    {
        return trim((string)$value);
    }
}
