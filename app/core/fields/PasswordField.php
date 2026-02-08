<?php

namespace App\Core\Fields;

class PasswordField extends BaseField
{
    public function getSqlType(): string
    {
        return 'VARCHAR(255)'; // For hashed passwords
    }

    public function validate(mixed $value): bool
    {
        $minLength = $this->getAttribute('minlength') ?? 6;
        return strlen((string)$value) >= $minLength;
    }

    public function getHtmlType(): string
    {
        return 'password';
    }

    public function transformForStorage(mixed $value): string
    {
        // Hash password before storage
        return password_hash((string)$value, PASSWORD_BCRYPT);
    }

    public function renderHtml(): string
    {
        // Never show password values
        $name = htmlspecialchars($this->name);
        
        $attrs = '';
        foreach ($this->attributes as $key => $val) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
        
        return "<input type=\"password\" name=\"{$name}\"{$attrs}>";
    }
}
