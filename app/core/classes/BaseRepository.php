<?php

namespace App\Core\Classes;

use App\Core\Database;

abstract class BaseRepository
{
    protected string $table = '';
    protected Database $db;
    protected array $wheres = [];
    protected array $orderBy = [];
    protected int $limit = 0;
    protected int $offset = 0;

    /**
     * Constructor
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        if (empty($this->table)) {
            $this->table = strtolower(str_replace('Repository', '', class_basename($this))) . 's';
        }
        return $this->table;
    }

    /**
     * Add where clause
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = compact('column', 'operator', 'value');
        return $this;
    }

    /**
     * Add order by clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * Add limit clause
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Execute query and return results
     */
    public function get(): array
    {
        $query = "SELECT * FROM {$this->getTable()}";

        if (!empty($this->wheres)) {
            $conditions = [];
            $params = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        if (!empty($this->orderBy)) {
            $query .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit > 0) {
            $query .= " LIMIT {$this->limit} OFFSET {$this->offset}";
        }

        return $this->db->fetchAll($query, $params ?? []);
    }

    /**
     * Get first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find by ID
     */
    public function findById(mixed $id): ?array
    {
        return $this->where('id', '=', $id)->first();
    }

    /**
     * Find all records
     */
    public function findAll(): array
    {
        $this->resetQuery();
        return $this->db->fetchAll("SELECT * FROM {$this->getTable()}");
    }

    /**
     * Insert record
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$this->getTable()} ($columns) VALUES ($placeholders)";

        return $this->db->execute($query, array_values($data));
    }

    /**
     * Update record
     */
    public function update(mixed $id, array $data): int
    {
        $sets = [];
        $values = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $query = "UPDATE {$this->getTable()} SET " . implode(', ', $sets) . " WHERE id = ?";
        return $this->db->execute($query, $values);
    }

    /**
     * Delete record
     */
    public function delete(mixed $id): int
    {
        $query = "DELETE FROM {$this->getTable()} WHERE id = ?";
        return $this->db->execute($query, [$id]);
    }

    /**
     * Reset query parameters
     */
    private function resetQuery(): void
    {
        $this->wheres = [];
        $this->orderBy = [];
        $this->limit = 0;
        $this->offset = 0;
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
