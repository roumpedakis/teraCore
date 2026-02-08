<?php

namespace App\Core\Fields;

class HiddenField extends BaseField
{
    public function getSqlType(): string
    {
        return 'VARCHAR(255)';
    }

    public function validate(mixed $value): bool
    {
        // Hidden fields accept any value
        return true;
    }

    public function getHtmlType(): string
    {
        return 'hidden';
    }
}
