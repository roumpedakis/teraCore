<?php

namespace App\Core\Fields;

class RadioField extends BaseField
{
    protected array $options = [];

    public function getSqlType(): string
    {
        return 'VARCHAR(255)';
    }

    public function validate(mixed $value): bool
    {
        if (empty($this->options)) {
            return true; // No options defined, accept any value
        }
        
        return in_array($value, array_keys($this->options));
    }

    public function getHtmlType(): string
    {
        return 'radio';
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function renderHtml(): string
    {
        $name = htmlspecialchars($this->name);
        $html = '';
        
        foreach ($this->options as $optValue => $optLabel) {
            $checked = ($this->value == $optValue) ? ' checked' : '';
            $optValue = htmlspecialchars($optValue);
            $optLabel = htmlspecialchars($optLabel);
            
            $html .= "<label>";
            $html .= "<input type=\"radio\" name=\"{$name}\" value=\"{$optValue}\"{$checked}> {$optLabel}";
            $html .= "</label>";
        }
        
        return $html;
    }
}
