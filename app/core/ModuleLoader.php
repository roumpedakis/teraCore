<?php

namespace App\Core;

class ModuleLoader
{
    private static array $modules = [];
    private static bool $loaded = false;

    /**
     * Load all modules from modules folder
     */
    public static function load(string $modulesPath = ''): array
    {
        if (self::$loaded) {
            return self::$modules;
        }

        if (empty($modulesPath)) {
            $modulesPath = dirname(__DIR__) . '/modules';
        }

        if (!is_dir($modulesPath)) {
            Logger::warning("Modules folder not found: $modulesPath");
            return [];
        }

        // Scan modules folder
        $moduleFolders = scandir($modulesPath);

        foreach ($moduleFolders as $folder) {
            if ($folder === '.' || $folder === '..') {
                continue;
            }

            $modulePath = "$modulesPath/$folder";
            if (!is_dir($modulePath)) {
                continue;
            }

            $initFile = "$modulePath/init.json";
            if (!file_exists($initFile)) {
                Logger::warning("Module init.json not found: $initFile");
                continue;
            }

            try {
                $initData = json_decode(file_get_contents($initFile), true);
                if ($initData === null) {
                    Logger::warning("Failed to parse module init.json: $initFile");
                    continue;
                }

                self::$modules[$folder] = [
                    'name' => $folder,
                    'path' => $modulePath,
                    'metadata' => $initData,
                    'entities' => self::loadEntities($modulePath),
                ];

                Logger::debug("Module loaded: $folder", [
                    'version' => $initData['version'] ?? 'unknown',
                    'entities' => count(self::$modules[$folder]['entities'])
                ]);
            } catch (\Exception $e) {
                Logger::error("Failed to load module: $folder", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        self::$loaded = true;
        return self::$modules;
    }

    /**
     * Load entities from module
     */
    private static function loadEntities(string $modulePath): array
    {
        $entities = [];
        $folders = scandir($modulePath);

        foreach ($folders as $folder) {
            if ($folder === '.' || $folder === '..' || $folder === 'init.json') {
                continue;
            }

            $entityPath = "$modulePath/$folder";
            if (!is_dir($entityPath)) {
                continue;
            }

            $schemaFile = "$entityPath/schema.json";
            if (!file_exists($schemaFile)) {
                continue;
            }

            try {
                $schema = json_decode(file_get_contents($schemaFile), true);
                if ($schema === null) {
                    continue;
                }

                $entities[$folder] = [
                    'name' => $folder,
                    'path' => $entityPath,
                    'schema' => $schema,
                ];
            } catch (\Exception $e) {
                Logger::error("Failed to load entity schema: $folder", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $entities;
    }

    /**
     * Get loaded modules
     */
    public static function getModules(): array
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$modules;
    }

    /**
     * Get single module
     */
    public static function getModule(string $name): ?array
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$modules[$name] ?? null;
    }

    /**
     * Get module entities
     */
    public static function getEntities(string $moduleName): array
    {
        $module = self::getModule($moduleName);
        return $module['entities'] ?? [];
    }

    /**
     * Get single entity
     */
    public static function getEntity(string $moduleName, string $entityName): ?array
    {
        $module = self::getModule($moduleName);
        return $module['entities'][$entityName] ?? null;
    }

    /**
     * Get pricing information for all modules
     */
    public static function getModulePricing(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        $pricing = [];
        foreach (self::$modules as $moduleName => $module) {
            $metadata = $module['metadata'] ?? [];
            $pricing[$moduleName] = [
                'name' => $moduleName,
                'description' => $metadata['description'] ?? '',
                'price' => $metadata['price'] ?? 0,
                'currency' => $metadata['priceCurrency'] ?? 'EUR',
                'billingPeriod' => $metadata['billingPeriod'] ?? 'monthly',
                'isCore' => $metadata['isCore'] ?? false,
            ];
        }

        return $pricing;
    }

    /**
     * Calculate total cost for specific modules
     * 
     * @param array $moduleNames Array of module names
     * @param string $currency Currency to return (default EUR)
     * @return array ['total' => float, 'breakdown' => array, 'currency' => string]
     */
    public static function calculateModuleCost(array $moduleNames, string $currency = 'EUR'): array
    {
        if (!self::$loaded) {
            self::load();
        }

        $total = 0;
        $breakdown = [];

        foreach ($moduleNames as $moduleName) {
            $module = self::$modules[$moduleName] ?? null;
            if (!$module) {
                continue;
            }

            $metadata = $module['metadata'] ?? [];
            $price = $metadata['price'] ?? 0;
            $isCore = $metadata['isCore'] ?? false;

            // Core modules are always free
            if ($isCore) {
                $price = 0;
            }

            $breakdown[$moduleName] = [
                'name' => $moduleName,
                'price' => $price,
                'currency' => $metadata['priceCurrency'] ?? 'EUR',
                'billingPeriod' => $metadata['billingPeriod'] ?? 'monthly',
                'isCore' => $isCore,
            ];

            $total += $price;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
            'currency' => $currency,
            'count' => count($breakdown),
            'paidModules' => count(array_filter($breakdown, fn($m) => $m['price'] > 0)),
        ];
    }

    /**
     * Get all core modules
     */
    public static function getCoreModules(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return array_filter(self::$modules, function ($module) {
            return ($module['metadata']['isCore'] ?? false) === true;
        });
    }

    /**
     * Get all paid (non-core) modules
     */
    public static function getPaidModules(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return array_filter(self::$modules, function ($module) {
            $isCore = $module['metadata']['isCore'] ?? false;
            $price = $module['metadata']['price'] ?? 0;
            return !$isCore && $price > 0;
        });
    }

    /**
     * Get module dependencies
     */
    public static function getDependencies(string $moduleName): array
    {
        $module = self::getModule($moduleName);
        return $module['metadata']['dependencies'] ?? [];
    }

    /**
     * Validate dependencies for a module
     * Returns array of missing dependencies
     */
    public static function validateDependencies(string $moduleName): array
    {
        $dependencies = self::getDependencies($moduleName);
        $missing = [];

        foreach ($dependencies as $dependency) {
            if (!isset(self::$modules[$dependency])) {
                $missing[] = $dependency;
            }
        }

        return $missing;
    }
}

