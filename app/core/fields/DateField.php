<?php

namespace App\Core\Fields;

class DateField extends BaseField
{
    public function getSqlType(): string
    {
        return 'DATE';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        // Validate date format YYYY-MM-DD
        $date = \DateTime::createFromFormat('Y-m-d', (string)$value);
        return $date && $date->format('Y-m-d') === $value;
    }

    public function getHtmlType(): string
    {
        return 'date';
    }

    public function transformForStorage(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        $date = \DateTime::createFromFormat('Y-m-d', (string)$value);
        return $date ? $date->format('Y-m-d') : null;
    }
}
