<?php

namespace App\Core\Classes;

use App\Core\Database;

abstract class BaseModel
{
    protected string $table = '';
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

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
            return $this->update();
        }
        return $this->insert();
    }

    /**
     * Insert new record
     */
    protected function insert(): bool
    {
        // To be implemented by Repository
        return false;
    }

    /**
     * Update existing record
     */
    protected function update(): bool
    {
        // To be implemented by Repository
        return false;
    }

    /**
     * Delete record
     */
    public function delete(): bool
    {
        // To be implemented by Repository
        return false;
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
}

/**
 * Helper function
 */
function class_basename(string|object $class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}
