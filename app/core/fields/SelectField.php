<?php

namespace App\Core\Fields;

class SelectField extends BaseField
{
    protected array $options = [];
    protected bool $multiple = false;

    public function getSqlType(): string
    {
        return $this->multiple ? 'TEXT' : 'VARCHAR(255)';
    }

    public function validate(mixed $value): bool
    {
        if (empty($this->options)) {
            return true; // No options defined, accept any value
        }
        
        if ($this->multiple) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($value as $v) {
                if (!in_array($v, array_keys($this->options))) {
                    return false;
                }
            }
            return true;
        }
        
        return in_array($value, array_keys($this->options));
    }

    public function getHtmlType(): string
    {
        return 'select';
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setMultiple(bool $multiple): void
    {
        $this->multiple = $multiple;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function transformForStorage(mixed $value): mixed
    {
        if ($this->multiple && is_array($value)) {
            return json_encode($value);
        }
        return $value;
    }

    public function transformFromStorage(mixed $value): mixed
    {
        if ($this->multiple && is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value;
    }

    public function renderHtml(): string
    {
        $name = htmlspecialchars($this->name);
        $multiple = $this->multiple ? ' multiple' : '';
        
        $html = "<select name=\"{$name}\"{$multiple}>";
        
        foreach ($this->options as $optValue => $optLabel) {
            $selected = '';
            if ($this->multiple && is_array($this->value)) {
                $selected = in_array($optValue, $this->value) ? ' selected' : '';
            } else {
                $selected = ($this->value == $optValue) ? ' selected' : '';
            }
            
            $optValue = htmlspecialchars($optValue);
            $optLabel = htmlspecialchars($optLabel);
            
            $html .= "<option value=\"{$optValue}\"{$selected}>{$optLabel}</option>";
        }
        
        $html .= "</select>";
        
        return $html;
    }
}
