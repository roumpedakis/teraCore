<?php

namespace App\Modules\Articles\Tag;

use App\Core\Classes\BaseRepository;
use App\Modules\Articles\Tag\Model;

class Repository extends BaseRepository
{
    protected string $table = 'article_tags';
    protected ?string $modelClass = Model::class;

    /**
     * Find tag by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }
}
