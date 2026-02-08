<?php

namespace App\Modules\Articles\Category;

use App\Core\Classes\BaseModel;

class Model extends BaseModel
{
    protected string $table = 'article_categories';

    /**
     * Get all articles in this category
     */
    public function getArticles(): array
    {
        // This would join with articles through pivot table
        return [];
    }
}
