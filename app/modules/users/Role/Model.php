<?php

namespace App\Modules\Users\Role;

use App\Core\Classes\BaseModel;

class Model extends BaseModel
{
    protected string $table = 'roles';

    /**
     * Get all permissions for this role
     */
    public function getPermissions(): array
    {
        // To be implemented with permissions system
        return [];
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        // To be implemented
        return false;
    }
}
