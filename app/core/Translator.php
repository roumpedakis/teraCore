<?php

namespace App\Core;

/**
 * Translator
 * Manages multilingual content by storing translations in separate tables
 * Convention: {entity}_{field_name}s table (e.g., article_descriptions, article_titles)
 */
class Translator
{
    protected Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Save translated value for a field
     * Convention: Creates/updates {entity}_{field_name}s table
     * 
     * @param string $entityType e.g., 'article'
     * @param int $entityId Entity primary key
     * @param string $fieldName Field name  
     * @param string $languageCode e.g., 'el', 'en'
     * @param mixed $value The translated content
     */
    public function saveTranslation(
        string $entityType,
        int $entityId,
        string $fieldName,
        string $languageCode,
        mixed $value
    ): bool {
        // Build table name: article_descriptions, article_titles, etc.
        $tableName = $this->getTranslationTableName($entityType, $fieldName);
        
        // Ensure translation table exists
        $this->ensureTranslationTable($tableName, $entityType);

        // Check if record exists
        $sql = "SELECT id FROM {$tableName} WHERE {$entityType}_id = ? AND language_code = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$entityId, $languageCode]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $sql = "UPDATE {$tableName} SET content = ?, updated_at = NOW() WHERE {$entityType}_id = ? AND language_code = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([$value, $entityId, $languageCode]);
        } else {
            // Insert
            $sql = "INSERT INTO {$tableName} ({$entityType}_id, language_code, content, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([$entityId, $languageCode, $value]);
        }
    }

    /**
     * Get translation for a field
     */
    public function getTranslation(
        string $entityType,
        int $entityId,
        string $fieldName,
        string $languageCode
    ): ?string {
        $tableName = $this->getTranslationTableName($entityType, $fieldName);

        if (!$this->tableExists($tableName)) {
            return null;
        }

        $sql = "SELECT content FROM {$tableName} WHERE {$entityType}_id = ? AND language_code = ? LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$entityId, $languageCode]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['content'] ?? null;
    }

    /**
     * Get all translations for a field across languages
     */
    public function getAllTranslations(
        string $entityType,
        int $entityId,
        string $fieldName
    ): array {
        $tableName = $this->getTranslationTableName($entityType, $fieldName);

        if (!$this->tableExists($tableName)) {
            return [];
        }

        $sql = "SELECT language_code, content FROM {$tableName} WHERE {$entityType}_id = ? ORDER BY language_code";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$entityId]);
        
        $translations = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $translations[$row['language_code']] = $row['content'];
        }

        return $translations;
    }

    /**
     * Delete all translations for an entity
     */
    public function deleteEntityTranslations(string $entityType, int $entityId, string $fieldName = ''): bool
    {
        if (!empty($fieldName)) {
            // Delete specific field translations
            $tableName = $this->getTranslationTableName($entityType, $fieldName);
            if (!$this->tableExists($tableName)) {
                return true;
            }
            $sql = "DELETE FROM {$tableName} WHERE {$entityType}_id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([$entityId]);
        }

        return true;
    }

    /**
     * Get translation table name
     * Convention: {entity}_{field_name}s
     */
    protected function getTranslationTableName(string $entityType, string $fieldName): string
    {
        $entityType = strtolower($entityType);
        $fieldName = strtolower($fieldName);
        return "{$entityType}_{$fieldName}s";
    }

    /**
     * Check if translation table exists
     */
    protected function tableExists(string $tableName): bool
    {
        // Use information_schema instead of SHOW TABLES LIKE which doesn't support parameterized queries
        $dbName = Config::get('DB_NAME', 'myapi');
        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$dbName, $tableName]);
        return (bool)$stmt->fetch();
    }

    /**
     * Ensure translation table exists, create if not
     */
    protected function ensureTranslationTable(string $tableName, string $entityType): void
    {
        if ($this->tableExists($tableName)) {
            return;
        }

        // Create translation table
        $entityForeignKey = strtolower($entityType) . '_id';
        $sql = "
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                {$entityForeignKey} BIGINT UNSIGNED NOT NULL,
                language_code VARCHAR(5) NOT NULL,
                content LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_translation (language_code, {$entityForeignKey}),
                KEY idx_entity ({$entityForeignKey}),
                FOREIGN KEY ({$entityForeignKey}) REFERENCES " . strtolower($entityType) . "s(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->db->execute($sql);
    }
}
