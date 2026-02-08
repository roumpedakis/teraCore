<?php

namespace App\Core\Fields;

class UrlField extends BaseField
{
    public function getSqlType(): string
    {
        return 'VARCHAR(2048)';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function getHtmlType(): string
    {
        return 'url';
    }

    public function transformForStorage(mixed $value): string
    {
        return trim((string)$value);
    }
}
