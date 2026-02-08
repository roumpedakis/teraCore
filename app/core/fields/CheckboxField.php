<?php

namespace App\Core\Fields;

class CheckboxField extends BaseField
{
    public function getSqlType(): string
    {
        return 'TINYINT(1)';
    }

    public function validate(mixed $value): bool
    {
        // Checkbox is always valid (checked or unchecked)
        return true;
    }

    public function getHtmlType(): string
    {
        return 'checkbox';
    }

    public function transformForStorage(mixed $value): int
    {
        // Convert to 1 or 0
        return $value ? 1 : 0;
    }

    public function transformFromStorage(mixed $value): bool
    {
        return (bool)$value;
    }

    public function renderHtml(): string
    {
        $name = htmlspecialchars($this->name);
        $checked = $this->value ? ' checked' : '';
        
        $attrs = '';
        foreach ($this->attributes as $key => $val) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
        
        return "<input type=\"checkbox\" name=\"{$name}\" value=\"1\"{$checked}{$attrs}>";
    }
}
