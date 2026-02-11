<?php

namespace App\Modules\Core\User;

use App\Core\Classes\BaseRepository;
use App\Modules\Core\User\Model;

class Repository extends BaseRepository
{
    protected string $table = 'users';
    protected ?string $modelClass = Model::class;

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', '=', $email)->first();
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', '=', $username)->first();
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): array
    {
        return $this->where('is_active', '=', 1)->get();
    }

    /**
     * Search users by name
     */
    public function searchByName(string $term): array
    {
        $term = "%$term%";
        $query = "
            SELECT * FROM {$this->getTable()} 
            WHERE CONCAT(first_name, ' ', last_name) LIKE ? 
            OR username LIKE ? 
            OR email LIKE ?
        ";
        return $this->db->fetchAll($query, [$term, $term, $term]);
    }
}
