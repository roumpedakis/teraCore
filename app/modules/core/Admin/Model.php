<?php

namespace App\Modules\Core\Admin;

use App\Core\Classes\BaseModel;

/**
 * Admin Model
 * Represents an administrative role/entity in the system
 */
class Model extends BaseModel
{
    protected string $table = 'admins';

    /**
     * Get admin by name
     */
    public function getByName(string $name): ?self
    {
        $data = $this->repository->where('name', '=', $name)->first();
        if ($data) {
            return $this->fill($data);
        }
        return null;
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
    public static function getActive(): array
    {
        $repo = new Repository();
        return $repo->where('status', '=', 'active')->get();
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
