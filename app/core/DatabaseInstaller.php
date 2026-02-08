<?php

namespace App\Core;

class DatabaseInstaller
{
    private Database $db;
    private ModuleLoader $moduleLoader;
    private array $moduleVersions = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->moduleLoader = new ModuleLoader();
        $this->loadModuleVersions();
    }

    /**
     * Load current module versions from tracking file
     */
    private function loadModuleVersions(): void
    {
        $versionFile = dirname(__DIR__, 2) . '/config/modules.json';

        if (file_exists($versionFile)) {
            $content = file_get_contents($versionFile);
            $this->moduleVersions = json_decode($content, true) ?? [];
        }
    }

    /**
     * Save module versions
     */
    private function saveModuleVersions(): void
    {
        $versionFile = dirname(__DIR__, 2) . '/config/modules.json';
        file_put_contents($versionFile, json_encode($this->moduleVersions, JSON_PRETTY_PRINT));
   }

    /**
     * Install all modules (create tables)
     */
    public function install(): void
    {
        Logger::info("Starting database installation...");

        $modules = $this->moduleLoader->load();

        foreach ($modules as $moduleName => $moduleData) {
            Logger::info("Installing module: $moduleName");

            foreach ($moduleData['entities'] as $entityName => $entity) {
                $this->createTable($moduleName, $entityName, $entity['schema']);
            }

            // Store module version
            $version = $moduleData['metadata']['version'] ?? '1.0.0';
            $this->moduleVersions[$moduleName] = [
                'version' => $version,
                'installed_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->saveModuleVersions();
        Logger::info("Installation completed successfully!");
    }

    /**
     * Create table from schema
     */
    private function createTable(string $moduleName, string $entityName, array $schema): void
    {
        $tableName = $this->getTableName($schema);

        // Check if table exists
        if ($this->tableExists($tableName)) {
            Logger::info("Table already exists: $tableName");
            return;
        }

        Logger::info("Creating table: $tableName");

        $sql = $this->buildCreateTableSQL($tableName, $schema);

        try {
            $this->db->execute($sql);
            Logger::info("Table created successfully: $tableName");
        } catch (\Exception $e) {
            Logger::error("Failed to create table: $tableName", [
                'error' => $e->getMessage(),
                'sql' => $sql
            ]);
            throw $e;
        }
    }

    /**
     * Build CREATE TABLE SQL from schema
     */
    private function buildCreateTableSQL(string $tableName, array $schema): string
    {
        $columns = [];
        $primaryKey = null;

        foreach ($schema['fields'] as $fieldName => $field) {
            $columnDef = $fieldName . ' ' . $field['type'];

            // Add constraints
            if (!($field['nullable'] ?? true)) {
                $columnDef .= ' NOT NULL';
            }

            if ($field['autoIncrement'] ?? false) {
                $columnDef .= ' AUTO_INCREMENT';
            }

            if (isset($field['default'])) {
                if ($field['default'] === 'CURRENT_TIMESTAMP') {
                    $columnDef .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $columnDef .= " DEFAULT '{$field['default']}'";
                }
            }

            if ($field['unique'] ?? false) {
                $columnDef .= ' UNIQUE';
            }

            if ($field['primaryKey'] ?? false) {
                $primaryKey = $fieldName;
            }

            $columns[] = $columnDef;
        }

        // Add primary key
        if ($primaryKey) {
            $columns[] = "PRIMARY KEY ($primaryKey)";
        }

        // Add indexes
        if (isset($schema['indexes']) && is_array($schema['indexes'])) {
            foreach ($schema['indexes'] as $index) {
                $indexName = $index['name'] ?? '';
                $columns_str = implode(', ', $index['columns']);
                $unique = $index['unique'] ?? false ? 'UNIQUE' : '';
                $columns[] = "$unique INDEX $indexName ($columns_str)";
            }
        }

        $columnsDef = implode(', ', $columns);

        return "CREATE TABLE $tableName ($columnsDef) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $result = $this->db->fetch(
                "SELECT 1 FROM information_schema.tables WHERE table_name = ? AND table_schema = DATABASE()",
                [$tableName]
            );
            return $result !== null;
        } catch (\Exception $e) {
            Logger::warning("Failed to check table existence: $tableName");
            return false;
        }
    }

    /**
     * Get table name from schema (required)
     */
    private function getTableName(array $schema): string
    {
        if (!isset($schema['tableName'])) {
            throw new \RuntimeException("tableName is required in schema definition");
        }

        return $schema['tableName'];
    }

    /**
     * Update/migrate existing tables (check for schema changes)
     */
    public function migrate(): void
    {
        Logger::info("Starting database migration...");

        $modules = $this->moduleLoader->load();

        foreach ($modules as $moduleName => $moduleData) {
            $currentVersion = $this->moduleVersions[$moduleName]['version'] ?? null;
            $newVersion = $moduleData['metadata']['version'] ?? '1.0.0';

            if ($currentVersion === $newVersion) {
                Logger::debug("Module up to date: $moduleName (v$newVersion)");
                continue;
            }

            Logger::info("Migrating module: $moduleName from v$currentVersion to v$newVersion");

            foreach ($moduleData['entities'] as $entityName => $entity) {
                $this->alterTable($moduleName, $entityName, $entity['schema']);
            }

            // Update version
            $this->moduleVersions[$moduleName]['version'] = $newVersion;
            $this->moduleVersions[$moduleName]['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->saveModuleVersions();
        Logger::info("Migration completed successfully!");
    }

    /**
     * Alter existing table based on schema
     */
    private function alterTable(string $moduleName, string $entityName, array $schema): void
    {
        $tableName = $this->getTableName($schema);

        if (!$this->tableExists($tableName)) {
            $this->createTable($moduleName, $entityName, $schema);
            return;
        }

        Logger::info("Checking schema for table: $tableName");

        // Get current columns
        $currentColumns = $this->getTableColumns($tableName);

        // Check for new columns
        foreach ($schema['fields'] as $fieldName => $field) {
            if (!in_array($fieldName, $currentColumns)) {
                Logger::info("Adding column: $tableName.$fieldName");
                $this->addColumn($tableName, $fieldName, $field);
            }
        }

        Logger::info("Table migration completed: $tableName");
    }

    /**
     * Get existing table columns
     */
    private function getTableColumns(string $tableName): array
    {
        try {
            $result = $this->db->fetchAll(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()",
                [$tableName]
            );

            return array_map(fn($row) => $row['COLUMN_NAME'], $result);
        } catch (\Exception $e) {
            Logger::warning("Failed to get table columns: $tableName");
            return [];
        }
    }

    /**
     * Add column to table
     */
    private function addColumn(string $tableName, string $columnName, array $fieldDefinition): void
    {
        $columnDef = $columnName . ' ' . $fieldDefinition['type'];

        if (!($fieldDefinition['nullable'] ?? true)) {
            $columnDef .= ' NOT NULL';
        }

        if (isset($fieldDefinition['default'])) {
            $columnDef .= " DEFAULT '{$fieldDefinition['default']}'";
        }

        $sql = "ALTER TABLE $tableName ADD COLUMN $columnDef";

        try {
            $this->db->execute($sql);
            Logger::info("Column added successfully: $tableName.$columnName");
        } catch (\Exception $e) {
            Logger::error("Failed to add column: $tableName.$columnName", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Drop all tables (for testing)
     */
    public function purge(): void
    {
        Logger::warning("Purging all tables...");

        $modules = $this->moduleLoader->load();

        foreach ($modules as $moduleData) {
            foreach ($moduleData['entities'] as $entity) {
                $tableName = $this->getTableName($entity['schema']);

                if ($this->tableExists($tableName)) {
                    try {
                        $this->db->execute("DROP TABLE IF EXISTS $tableName");
                        Logger::info("Table dropped: $tableName");
                    } catch (\Exception $e) {
                        Logger::error("Failed to drop table: $tableName", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Clear version tracking
        $versionFile = dirname(__DIR__, 2) . '/config/modules.json';
        if (file_exists($versionFile)) {
            unlink($versionFile);
        }

        Logger::warning("Purge completed!");
    }

    /**
     * Show installation status
     */
    public function status(): array
    {
        $modules = $this->moduleLoader->load();
        $status = [];

        foreach ($modules as $moduleName => $moduleData) {
            $currentVersion = $this->moduleVersions[$moduleName]['version'] ?? 'not installed';
            $newVersion = $moduleData['metadata']['version'] ?? '1.0.0';
            $needsUpdate = $currentVersion !== $newVersion;

            $status[$moduleName] = [
                'installed_version' => $currentVersion,
                'available_version' => $newVersion,
                'needs_update' => $needsUpdate,
                'entity_count' => count($moduleData['entities']),
                'entities' => array_keys($moduleData['entities']),
            ];
        }

        return $status;
    }
}
