<?php

namespace App\Core\Fields;

class DateTimeField extends BaseField
{
    public function getSqlType(): string
    {
        return 'DATETIME';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true; // Empty is valid unless required
        }
        
        // Validate datetime format YYYY-MM-DD HH:MM:SS or YYYY-MM-DDTHH:MM
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', (string)$value);
        if ($datetime && $datetime->format('Y-m-d H:i:s') === $value) {
            return true;
        }
        
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i', (string)$value);
        return $datetime && $datetime->format('Y-m-d\TH:i') === $value;
    }

    public function getHtmlType(): string
    {
        return 'datetime-local';
    }

    public function transformForStorage(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        // Try to parse and convert to MySQL datetime format
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i', (string)$value);
        if ($datetime) {
            return $datetime->format('Y-m-d H:i:s');
        }
        
        return (string)$value;
    }
}
