<?php

namespace App\Core\Fields;

/**
 * NumberBox field type
 * Numeric input with min/max constraints
 */
class NumberBox extends BaseElement
{
    protected ?float $minValue = null;
    protected ?float $maxValue = null;
    protected int $decimalPlaces = 0;

    protected function applyConfig(array $config): void
    {
        parent::applyConfig($config);
        
        if (isset($config['min'])) {
            $this->minValue = (float)$config['min'];
        }
        if (isset($config['max'])) {
            $this->maxValue = (float)$config['max'];
        }
        if (isset($config['decimals'])) {
            $this->decimalPlaces = (int)$config['decimals'];
        }
    }

    /**
     * Validate numeric value
     */
    public function validate(): bool
    {
        if (!parent::validate()) {
            return false;
        }

        if ($this->value === null) {
            return true;
        }

        if (!is_numeric($this->value)) {
            return false;
        }

        $numValue = (float)$this->value;

        if ($this->minValue !== null && $numValue < $this->minValue) {
            return false;
        }

        if ($this->maxValue !== null && $numValue > $this->maxValue) {
            return false;
        }

        return true;
    }

    /**
     * Format numeric value
     */
    public function getValue(): mixed
    {
        if ($this->value === null) {
            return $this->defaultValue;
        }
        
        return round((float)$this->value, $this->decimalPlaces);
    }

    /**
     * Get min value
     */
    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    /**
     * Get max value
     */
    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    /**
     * Get decimal places
     */
    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces;
    }
}
