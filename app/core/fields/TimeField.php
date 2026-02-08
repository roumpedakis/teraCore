<?php

namespace App\Core\Fields;

class TimeField extends BaseField
{
    public function getSqlType(): string
    {
        return 'TIME';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        // Validate time format HH:MM or HH:MM:SS
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', (string)$value) === 1;
    }

    public function getHtmlType(): string
    {
        return 'time';
    }

    public function transformForStorage(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        return (string)$value;
    }
}
