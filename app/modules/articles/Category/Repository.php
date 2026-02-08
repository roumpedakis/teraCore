<?php

namespace App\Modules\Articles\Category;

use App\Core\Classes\BaseRepository;
use App\Modules\Articles\Category\Model;

class Repository extends BaseRepository
{
    protected string $table = 'article_categories';
    protected ?string $modelClass = Model::class;

    /**
     * Find category by name
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', '=', $name)->first();
    }
}
