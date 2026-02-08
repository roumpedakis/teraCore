<?php

namespace App\Core;

use App\Core\Fields\BaseElement;

/**
 * Field Type Loader
 * Loads field type definitions from PHP config with JSON overrides per-entity
 */
class FieldTypeLoader
{
    protected static array $defaultConfig = [];
    protected static array $entityConfigs = [];

    /**
     * Get the configuration directory path
     */
    protected static function getConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config';
    }

    /**
     * Load default field type configurations
     */
    public static function loadDefaults(): array
    {
        if (empty(self::$defaultConfig)) {
            self::$defaultConfig = require_once self::getConfigPath() . '/field-types.php';
        }
        return self::$defaultConfig;
    }

    /**
     * Load entity-specific field type overrides from JSON
     */
    public static function loadEntityConfig(string $entityType): array
    {
        if (isset(self::$entityConfigs[$entityType])) {
            return self::$entityConfigs[$entityType];
        }

        $configFile = self::getConfigPath() . '/' . strtolower($entityType) . '/field-types.json';
        $config = [];

        if (file_exists($configFile)) {
            $json = file_get_contents($configFile);
            $config = json_decode($json, true) ?? [];
        }

        self::$entityConfigs[$entityType] = $config;
        return $config;
    }

    /**
     * Get merged configuration for a field type (defaults + entity overrides)
     */
    public static function getFieldTypeConfig(string $fieldType, string $entityType = ''): array
    {
        $defaults = self::loadDefaults();
        $merged = $defaults[$fieldType] ?? [];

        if (!empty($entityType)) {
            $entityOverrides = self::loadEntityConfig($entityType);
            if (isset($entityOverrides[$fieldType])) {
                $merged = array_merge($merged, $entityOverrides[$fieldType]);
            }
        }

        return $merged;
    }

    /**
     * Get all field types for an entity
     */
    public static function getEntityFieldTypes(string $entityType): array
    {
        $defaults = self::loadDefaults();
        $entityOverrides = self::loadEntityConfig($entityType);

        // Merge all field types
        $allTypes = array_merge($defaults, $entityOverrides);

        return array_keys($allTypes);
    }

    /**
     * Resolve field type inheritance chain
     */
    public static function resolveFieldType(string $fieldType, string $entityType = ''): array
    {
        $config = self::getFieldTypeConfig($fieldType, $entityType);
        
        if (empty($config)) {
            throw new \RuntimeException("Field type not found: $fieldType");
        }

        // Resolve inheritance
        if (!empty($config['extends'])) {
            $parentConfig = self::resolveFieldType($config['extends'], $entityType);
            $config = array_merge($parentConfig, $config);
        }

        return $config;
    }
}
