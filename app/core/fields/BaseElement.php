<?php

namespace App\Core\Fields;

/**
 * Base field type class
 * All field types inherit from this and can override behavior
 */
class BaseElement
{
    protected string $name;
    protected string $type = '';
    protected mixed $value;
    protected array $validators = [];
    protected array $metadata = [];
    protected bool $required = false;
    protected mixed $defaultValue = null;
    protected ?string $translationTable = null;

    public function __construct(string $name = '', array $config = [])
    {
        $this->name = $name;
        $this->type = $name;  // Store the field type name passed in
        $this->applyConfig($config);
    }

    /**
     * Apply configuration array to field
     */
    protected function applyConfig(array $config): void
    {
        if (isset($config['validators']) && is_array($config['validators'])) {
            $this->validators = $config['validators'];
            // Check if 'required' is in validators
            if (in_array('required', $this->validators)) {
                $this->required = true;
            }
        }
        if (isset($config['required'])) {
            $this->required = (bool)$config['required'];
        }
        if (isset($config['default'])) {
            $this->defaultValue = $config['default'];
        }
        if (isset($config['metadata']) && is_array($config['metadata'])) {
            $this->metadata = $config['metadata'];
        }
        if (isset($config['translatable']) && $config['translatable']) {
            $this->translationTable = $config['translation_table'] ?? null;
        }
    }

    /**
     * Get field name
     */
    public function getName(): string
    {
        return $this->name;
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
        return $this->value ?? $this->defaultValue;
    }

    /**
     * Get validators
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if field is required
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Check if field is translatable
     */
    public function isTranslatable(): bool
    {
        return !is_null($this->translationTable);
    }

    /**
     * Get translation table name
     */
    public function getTranslationTable(): ?string
    {
        return $this->translationTable;
    }

    /**
     * Validate field value
     */
    public function validate(): bool
    {
        if ($this->required && empty($this->value)) {
            return false;
        }
        // Additional validators can be added here
        return true;
    }

    /**
     * Convert field to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->getValue(),
            'type' => $this->getType(),
            'required' => $this->required,
            'translatable' => $this->isTranslatable(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get field type name
     */
    public function getType(): string
    {
        return $this->type ?: substr(strrchr(static::class, '\\'), 1);
    }
}
