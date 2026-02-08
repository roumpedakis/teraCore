<?php

namespace App\Core\Fields;

class EmailField extends BaseField
{
    public function getSqlType(): string
    {
        return 'VARCHAR(255)';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getHtmlType(): string
    {
        return 'email';
    }

    public function transformForStorage(mixed $value): string
    {
        return strtolower(trim((string)$value));
    }
}
