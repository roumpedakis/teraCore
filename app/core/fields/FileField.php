<?php

namespace App\Core\Fields;

class FileField extends BaseField
{
    protected array $allowedTypes = [];
    protected int $maxSize = 10485760; // 10MB default

    public function getSqlType(): string
    {
        return 'VARCHAR(255)'; // Store file path
    }

    public function validate(mixed $value): bool
    {
        if (empty($value) || !is_array($value)) {
            return true; // Will be $_FILES array structure
        }
        
        // Validate file upload
        if (isset($value['error']) && $value['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Check file size
        if (isset($value['size']) && $value['size'] > $this->maxSize) {
            return false;
        }
        
        // Check file type
        if (!empty($this->allowedTypes) && isset($value['type'])) {
            return in_array($value['type'], $this->allowedTypes);
        }
        
        return true;
    }

    public function getHtmlType(): string
    {
        return 'file';
    }

    public function setAllowedTypes(array $types): void
    {
        $this->allowedTypes = $types;
    }

    public function setMaxSize(int $bytes): void
    {
        $this->maxSize = $bytes;
    }

    public function renderHtml(): string
    {
        $name = htmlspecialchars($this->name);
        
        $accept = '';
        if (!empty($this->allowedTypes)) {
            $accept = ' accept="' . implode(',', $this->allowedTypes) . '"';
        }
        
        $attrs = '';
        foreach ($this->attributes as $key => $val) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
        
        return "<input type=\"file\" name=\"{$name}\"{$accept}{$attrs}>";
    }
}
