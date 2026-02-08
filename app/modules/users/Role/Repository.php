<?php

namespace App\Modules\Users\Role;

use App\Core\Classes\BaseRepository;

class Repository extends BaseRepository
{
    protected string $table = 'roles';

    /**
     * Find role by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }
}
