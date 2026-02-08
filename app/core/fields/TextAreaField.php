<?php

namespace App\Core\Fields;

class TextAreaField extends BaseField
{
    public function getSqlType(): string
    {
        return 'TEXT';
    }

    public function validate(mixed $value): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        
        $maxLength = $this->getAttribute('maxlength') ?? 65535;
        return strlen((string)$value) <= $maxLength;
    }

    public function getHtmlType(): string
    {
        return 'textarea';
    }

    public function renderHtml(): string
    {
        $name = htmlspecialchars($this->name);
        $value = htmlspecialchars((string)$this->value);
        
        $attrs = '';
        foreach ($this->attributes as $key => $val) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
        
        return "<textarea name=\"{$name}\"{$attrs}>{$value}</textarea>";
    }
}
