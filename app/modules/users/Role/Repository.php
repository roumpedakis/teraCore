<?php

namespace App\Modules\Users\Role;

use App\Core\Classes\BaseRepository;
use App\Modules\Users\Role\Model;

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
