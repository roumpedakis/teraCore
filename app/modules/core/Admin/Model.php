<?php

namespace App\Modules\Core\Admin;

use App\Core\Classes\BaseModel;
use App\Core\Classes\BaseRepository;
use App\Core\Database;

/**
 * Admin Model
 * Represents an administrative role/entity in the system
 */
class Model extends BaseModel
{
    protected string $table = 'admins';

    private function repo(): BaseRepository
    {
        if ($this->repository instanceof BaseRepository) {
            return $this->repository;
        }

        $this->repository = new Repository(Database::getInstance());
        return $this->repository;
    }

    /**
     * Get admin by name
     */
    public function getByName(string $name): ?self
    {
        return $this->repo()->where('name', '=', $name)->firstModel();
    }

    /**
     * Check if admin is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get all active admins
     */
    public static function getActive(?BaseRepository $repo = null): array
    {
        $repo = $repo ?? new Repository(Database::getInstance());
        return $repo->where('status', '=', 'active')->getModels();
    }

    /**
     * Deactivate admin
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Suspend admin (security issue)
     */
    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }
}
