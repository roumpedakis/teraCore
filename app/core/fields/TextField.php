<?php

namespace App\Core\Fields;

/**
 * TextField field type
 * Text input with optional multilingual support
 */
class TextField extends BaseElement
{
    protected int $minLength = 0;
    protected ?int $maxLength = null;
    protected string $pattern = '';
    protected bool $translatable = false;

    protected function applyConfig(array $config): void
    {
        parent::applyConfig($config);
        
        if (isset($config['min_length'])) {
            $this->minLength = (int)$config['min_length'];
        }
        if (isset($config['max_length'])) {
            $this->maxLength = (int)$config['max_length'];
        }
        if (isset($config['pattern'])) {
            $this->pattern = (string)$config['pattern'];
        }
        if (isset($config['translatable'])) {
            $this->translatable = (bool)$config['translatable'];
            // Set translation table if translatable
            if ($this->translatable && isset($config['translation_table'])) {
                $this->translationTable = $config['translation_table'];
            }
        }
    }

    /**
     * Validate text value
     */
    public function validate(): bool
    {
        if (!parent::validate()) {
            return false;
        }

        if ($this->value === null) {
            return true;
        }

        $value = (string)$this->value;

        if (strlen($value) < $this->minLength) {
            return false;
        }

        if ($this->maxLength !== null && strlen($value) > $this->maxLength) {
            return false;
        }

        if ($this->pattern && !preg_match($this->pattern, $value)) {
            return false;
        }

        return true;
    }

    /**
     * Check if field is translatable
     */
    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    /**
     * Get min length
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }

    /**
     * Get max length
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * Get pattern
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}
