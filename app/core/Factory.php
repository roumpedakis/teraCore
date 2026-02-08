<?php

namespace App\Core;

use App\Core\Fields\BaseElement;

class Factory
{
    /**
     * Create field type instance
     * Supports type inheritance and configuration merging
     */
    public static function createFieldType(string $type, string $entityType = '', array $overrideConfig = []): BaseElement
    {
        // Resolve field type configuration with inheritance
        $config = FieldTypeLoader::resolveFieldType($type, $entityType);
        
        // Merge any override configuration
        $config = array_merge($config, $overrideConfig);
        
        // Get the field class
        $className = $config['class'] ?? null;
        if (!$className || !class_exists($className)) {
            throw new \RuntimeException("Field type class not found for type: $type");
        }

        // Instantiate the field and apply configuration
        $field = new $className($type, $config);
        
        return $field;
    }

    /**
     * Get field type metadata
     */
    public static function getFieldTypeMetadata(string $type, string $entityType = ''): array
    {
        $config = FieldTypeLoader::resolveFieldType($type, $entityType);
        return [
            'type' => $type,
            'class' => $config['class'] ?? null,
            'extends' => $config['extends'] ?? null,
            'metadata' => $config['metadata'] ?? [],
            'validators' => $config['validators'] ?? [],
        ];
    }

    /**
     * Get all field types for an entity
     */
    public static function getEntityFieldTypes(string $entityType): array
    {
        return FieldTypeLoader::getEntityFieldTypes($entityType);
    }

    /**
     * Create model instance
     */
    public static function createModel(string $moduleName, string $entityName)
    {
        $className = "App\\Modules\\{$moduleName}\\{$entityName}\\Model";

        if (!class_exists($className)) {
            throw new \RuntimeException("Model not found: $className");
        }

        return new $className();
    }

    /**
     * Create controller instance
     */
    public static function createController(string $moduleName, string $entityName): object
    {
        $className = "App\\Modules\\{$moduleName}\\{$entityName}\\Controller";

        if (!class_exists($className)) {
            throw new \RuntimeException("Controller not found: $className");
        }

        $controller = new $className();

        // Set view and repository
        $view = self::createView($moduleName, $entityName);
        $repository = self::createRepository($moduleName, $entityName);

        $controller->setView($view);
        $controller->setRepository($repository);

        return $controller;
    }

    /**
     * Create view instance
     */
    public static function createView(string $moduleName, string $entityName): object
    {
        $className = "App\\Modules\\{$moduleName}\\{$entityName}\\View";

        if (!class_exists($className)) {
            throw new \RuntimeException("View not found: $className");
        }

        return new $className();
    }

    /**
     * Create repository instance
     */
    public static function createRepository(string $moduleName, string $entityName): object
    {
        $className = "App\\Modules\\{$moduleName}\\{$entityName}\\Repository";

        if (!class_exists($className)) {
            throw new \RuntimeException("Repository not found: $className");
        }

        $db = Database::getInstance();
        return new $className($db);
    }
}
