<?php

namespace App\Core\Classes;

use App\Core\Database;

abstract class BaseRepository
{
    protected string $table = '';
    protected Database $db;
    protected ?string $modelClass = null;
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
     * Set model class for hydration
     */
    public function setModelClass(string $modelClass): void
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Get model class for hydration
     */
    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    /**
     * Apply filters from query parameters
     * Supports: limit, offset, orderBy, order, and dynamic field filters
     */
    public function applyFilters(array $params): self
    {
        // Handle pagination
        if (isset($params['limit'])) {
            $limit = (int)$params['limit'];
            $offset = (int)($params['offset'] ?? 0);
            $this->limit($limit, $offset);
        }

        // Handle sorting
        if (isset($params['orderBy'])) {
            $direction = strtoupper($params['order'] ?? 'ASC');
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'ASC';
            }
            $this->orderBy($params['orderBy'], $direction);
        }

        // Handle dynamic field filters (e.g., name=takis, status=active)
        $reservedParams = ['limit', 'offset', 'orderBy', 'order'];
        foreach ($params as $key => $value) {
            if (!in_array($key, $reservedParams) && !empty($value)) {
                $this->where($key, '=', $value);
            }
        }

        return $this;
    }

    /**
     * Get paginated results with metadata
     */
    public function getPaginated(array $params = []): array
    {
        // Apply filters
        $this->applyFilters($params);

        // Get total count (before pagination)
        $total = $this->count();

        // Get paginated data
        $data = $this->get();

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'limit' => $this->limit > 0 ? $this->limit : $total,
                'offset' => $this->offset,
                'count' => count($data)
            ]
        ];
    }

    /**
     * Count records matching current where clauses
     */
    public function count(): int
    {
        $query = "SELECT COUNT(*) as count FROM {$this->getTable()}";

        if (!empty($this->wheres)) {
            $conditions = [];
            $params = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->db->fetchAll($query, $params ?? []);
        return (int)($result[0]['count'] ?? 0);
    }

    /**
     * Hydrate model from array
     */
    protected function hydrateModel(array $data): ?BaseModel
    {
        if (empty($this->modelClass) || !class_exists($this->modelClass)) {
            return null;
        }

        $model = new $this->modelClass();
        if ($model instanceof BaseModel) {
            $model->fill($data);
            $model->markAsExisting();
            $model->setRepository($this);
            return $model;
        }

        return null;
    }

    /**
     * Get first result as model
     */
    public function firstModel(): ?BaseModel
    {
        $data = $this->first();
        return $data ? $this->hydrateModel($data) : null;
    }

    /**
     * Get results as models
     */
    public function getModels(): array
    {
        $rows = $this->get();
        $models = [];
        foreach ($rows as $row) {
            $model = $this->hydrateModel($row);
            if ($model) {
                $models[] = $model;
            }
        }
        return $models;
    }

    /**
     * Find by ID as model
     */
    public function findByIdModel(mixed $id): ?BaseModel
    {
        $data = $this->findById($id);
        return $data ? $this->hydrateModel($data) : null;
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
if (!function_exists(__NAMESPACE__ . '\\class_basename')) {
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
