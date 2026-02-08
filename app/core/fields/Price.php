<?php

namespace App\Core\Fields;

/**
 * Price field type
 * Extends NumberBox with currency support
 */
class Price extends NumberBox
{
    protected string $currency = 'EUR';

    protected function applyConfig(array $config): void
    {
        parent::applyConfig($config);
        
        if (isset($config['currency'])) {
            $this->currency = (string)$config['currency'];
        }
        
        // Prices default to 2 decimal places
        if (!isset($config['decimals'])) {
            $this->decimalPlaces = 2;
        }
    }

    /**
     * Get currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get formatted price string
     */
    public function getFormatted(): string
    {
        $value = $this->getValue();
        return $value ? number_format((float)$value, $this->decimalPlaces, ',', '.') . ' ' . $this->currency : '0,00 ' . $this->currency;
    }

    /**
     * Convert to array with formatted value
     */
    public function toArray(): array
    {
        $arr = parent::toArray();
        $arr['currency'] = $this->currency;
        $arr['formatted'] = $this->getFormatted();
        return $arr;
    }
}
