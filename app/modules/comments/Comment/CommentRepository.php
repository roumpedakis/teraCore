<?php

namespace App\Modules\Comments;

use App\Core\Database;

class CommentRepository
{
    private Database $db;
    private string $table = 'comments';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find all comments for an entity
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        $sql = "SELECT c.*, u.username, u.email 
                FROM {$this->table} c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.entity_type = :entity_type 
                AND c.entity_id = :entity_id
                AND c.status = 'approved'
                ORDER BY c.created_at DESC";

        return $this->db->fetchAll($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
    }

    /**
     * Find comment by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Create new comment
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (entity_type, entity_id, user_id, content, parent_id, status)
                VALUES (:entity_type, :entity_id, :user_id, :content, :parent_id, :status)";

        $this->db->execute($sql, [
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
            'status' => $data['status'] ?? 'pending'
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update comment
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
                SET content = :content, status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        return $this->db->execute($sql, [
            'id' => $id,
            'content' => $data['content'] ?? '',
            'status' => $data['status'] ?? 'pending'
        ]);
    }

    /**
     * Delete comment
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]);
    }

    /**
     * Get comments by user
     */
    public function findByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    /**
     * Count comments for entity
     */
    public function countByEntity(string $entityType, int $entityId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE entity_type = :entity_type 
                AND entity_id = :entity_id
                AND status = 'approved'";

        $result = $this->db->fetch($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);

        return (int)($result['count'] ?? 0);
    }
}
