<?php

namespace App\Modules\Core\Role;

use App\Core\Classes\BaseRepository;
use App\Modules\Core\Role\Model;

class Repository extends BaseRepository
{
    protected string $table = 'roles';
    protected ?string $modelClass = Model::class;

    /**
     * Find role by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }
}
