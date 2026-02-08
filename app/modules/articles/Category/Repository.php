<?php

namespace App\Modules\Articles\Category;

use App\Core\Classes\BaseRepository;

class Repository extends BaseRepository
{
    protected string $table = 'article_categories';

    /**
     * Find category by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }
}
