<?php

namespace App\Core\Classes;

use App\Core\Database;
use App\Core\Translator;

abstract class BaseModel
{
    protected string $table = '';
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    protected ?BaseRepository $repository = null;
    protected ?Translator $translator = null;
    protected array $translatableFields = [];
    protected array $translations = [];

    /**
     * Get table name
     */
    public function getTable(): string
    {
        if (empty($this->table)) {
            $this->table = strtolower(class_basename($this)) . 's';
        }
        return $this->table;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Magic method: set attribute
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic method: get attribute
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Check if attribute exists
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Save model (insert or update)
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->update($this->getDirty());
        }
        return $this->insert();
    }

    /**
     * Insert new record
     */
    protected function insert(): bool
    {
        if (!$this->repository) {
            return false;
        }

        $result = $this->repository->insert($this->attributes);
        if ($result > 0) {
            $this->exists = true;
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Update existing record
     */
    public function update(array $data): bool
    {
        if (!$this->repository) {
            return false;
        }

        if (!empty($data)) {
            $this->fill($data);
        }

        $dirty = $this->getDirty();
        if (empty($dirty)) {
            return true;
        }

        $id = $this->attributes['id'] ?? null;
        if (!$id) {
            return false;
        }

        $result = $this->repository->update($id, $dirty);
        if ($result > 0) {
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Delete record
     */
    public function delete(): bool
    {
        if (!$this->repository) {
            return false;
        }

        $id = $this->attributes['id'] ?? null;
        if (!$id) {
            return false;
        }

        $result = $this->repository->delete($id);
        return $result > 0;
    }

    /**
     * Hydrate model from array
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Set repository
     */
    public function setRepository(BaseRepository $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Get repository
     */
    public function getRepository(): ?BaseRepository
    {
        return $this->repository;
    }

    /**
     * Mark as existing (loaded from database)
     */
    public function markAsExisting(): void
    {
        $this->exists = true;
        $this->original = $this->attributes;
    }

    /**
     * Get dirty attributes (changed)
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Initialize translator instance
     */
    public function initializeTranslator(): void
    {
        if (is_null($this->translator)) {
            $this->translator = new Translator(Database::getInstance());
        }
    }

    /**
     * Get translator instance
     */
    public function getTranslator(): Translator
    {
        $this->initializeTranslator();
        return $this->translator;
    }

    /**
     * Set translatable fields
     */
    public function setTranslatableFields(array $fields): void
    {
        $this->translatableFields = $fields;
    }

    /**
     * Get translatable fields
     */
    public function getTranslatableFields(): array
    {
        return $this->translatableFields;
    }

    /**
     * Save translation for a field
     */
    public function saveTranslation(
        int $entityId,
        string $fieldName,
        string $languageCode,
        mixed $value
    ): bool {
        $this->initializeTranslator();
        
        $entityType = strtolower(class_basename($this));
        return $this->translator->saveTranslation(
            $entityType,
            $entityId,
            $fieldName,
            $languageCode,
            $value
        );
    }

    /**
     * Get translation for a field
     */
    public function getTranslation(
        int $entityId,
        string $fieldName,
        string $languageCode
    ): ?string {
        $this->initializeTranslator();
        
        $entityType = strtolower(class_basename($this));
        return $this->translator->getTranslation(
            $entityType,
            $entityId,
            $fieldName,
            $languageCode
        );
    }

    /**
     * Get all translations for a field
     */
    public function getAllTranslations(
        int $entityId,
        string $fieldName
    ): array {
        $this->initializeTranslator();
        
        $entityType = strtolower(class_basename($this));
        return $this->translator->getAllTranslations(
            $entityType,
            $entityId,
            $fieldName
        );
    }

    /**
     * Store translation data temporarily
     */
    public function storeTranslation(string $fieldName, string $languageCode, mixed $value): void
    {
        if (!isset($this->translations[$fieldName])) {
            $this->translations[$fieldName] = [];
        }
        $this->translations[$fieldName][$languageCode] = $value;
    }

    /**
     * Get stored translations
     */
    public function getStoredTranslations(): array
    {
        return $this->translations;
    }

}

/**
 * Helper function
 */
if (!function_exists(__NAMESPACE__ . '\\class_basename')) {
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
