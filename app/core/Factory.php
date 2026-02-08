<?php

namespace App\Core;

class Factory
{
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
