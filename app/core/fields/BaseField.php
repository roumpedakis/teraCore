<?php

namespace App\Core\Fields;

/**
 * BaseField - Abstract base class for all field types
 * 
 * Provides common functionality for field validation, SQL mapping, and HTML rendering
 */
abstract class BaseField
{
    protected string $name;
    protected mixed $value;
    protected array $attributes = [];
    protected array $validators = [];
    protected array $metadata = [];

    /**
     * Get SQL column type for this field
     */
    abstract public function getSqlType(): string;

    /**
     * Validate field value
     */
    abstract public function validate(mixed $value): bool;

    /**
     * Get HTML input type
     */
    abstract public function getHtmlType(): string;

    /**
     * Constructor
     */
    public function __construct(string $name, mixed $value = null, array $attributes = [])
    {
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes;
    }

    /**
     * Set field value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Get field value
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get field name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Add validator
     */
    public function addValidator(string $validator): void
    {
        $this->validators[] = $validator;
    }

    /**
     * Get validators
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Set metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Render HTML input
     */
    public function renderHtml(): string
    {
        $type = $this->getHtmlType();
        $name = htmlspecialchars($this->name);
        $value = htmlspecialchars((string)$this->value);
        
        $attrs = '';
        foreach ($this->attributes as $key => $val) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
        
        return "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\"{$attrs}>";
    }

    /**
     * Transform value for storage
     */
    public function transformForStorage(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Transform value from storage
     */
    public function transformFromStorage(mixed $value): mixed
    {
        return $value;
    }
}
