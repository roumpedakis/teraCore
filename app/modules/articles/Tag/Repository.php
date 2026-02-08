<?php

namespace App\Modules\Articles\Tag;

use App\Core\Classes\BaseRepository;

class Repository extends BaseRepository
{
    protected string $table = 'article_tags';

    /**
     * Find tag by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }
}
