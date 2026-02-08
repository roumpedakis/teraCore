<?php

namespace App\Modules\Articles\Tag;

use App\Core\Classes\BaseModel;

class Model extends BaseModel
{
    protected string $table = 'article_tags';

    /**
     * Get all articles with this tag
     */
    public function getArticles(): array
    {
        // This would join with articles through pivot table
        return [];
    }
}
