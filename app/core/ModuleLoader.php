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
}
