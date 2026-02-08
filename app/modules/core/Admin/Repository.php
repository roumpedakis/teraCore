<?php

namespace App\Modules\Core\Admin;

use App\Core\Classes\BaseRepository;

/**
 * Admin Repository
 * Data access layer for admin operations
 */
class Repository extends BaseRepository
{
    protected string $table = 'admins';

    /**
     * Find admin by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }

    /**
     * Get all active admins
     */
    public function getActive(): array
    {
        return $this->where('status', '=', 'active')->orderBy('name')->get();
    }

    /**
     * Get all inactive admins
     */
    public function getInactive(): array
    {
        return $this->where('status', '=', 'inactive')->orderBy('name')->get();
    }

    /**
     * Get suspended admins
     */
    public function getSuspended(): array
    {
        return $this->where('status', '=', 'suspended')->get();
    }

    /**
     * Count admins by status
     */
    public function countByStatus(string $status): int
    {
        return $this->where('status', '=', $status)->count();
    }
}
