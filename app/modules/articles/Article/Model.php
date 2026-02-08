<?php

namespace App\Modules\Articles\Article;

use App\Core\Classes\BaseModel;

class Model extends BaseModel
{
    protected string $table = 'articles';

    /**
     * Get author information
     */
    public function getAuthor(): ?array
    {
        // This would join with users table
        return null;
    }

    /**
     * Get article categories
     */
    public function getCategories(): array
    {
        // This would join with categories through pivot table
        return [];
    }

    /**
     * Get article tags
     */
    public function getTags(): array
    {
        // This would join with tags through pivot table
        return [];
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->getAttribute('status') === 'published';
    }

    /**
     * Generate slug from title if not set
     */
    public function generateSlug(): void
    {
        if (empty($this->getAttribute('slug'))) {
            $slug = strtolower($this->getAttribute('title') ?? '');
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            $this->setAttribute('slug', $slug);
        }
    }
}
