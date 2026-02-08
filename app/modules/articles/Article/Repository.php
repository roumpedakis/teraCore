<?php

namespace App\Modules\Articles\Article;

use App\Core\Classes\BaseRepository;

class Repository extends BaseRepository
{
    protected string $table = 'articles';

    /**
     * Find article by slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', '=', $slug)->first();
    }

    /**
     * Get published articles
     */
    public function getPublished(): array
    {
        return $this->where('status', '=', 'published')
            ->orderBy('published_at', 'DESC')
            ->get();
    }

    /**
     * Get articles by author
     */
    public function getByAuthor(int $authorId): array
    {
        return $this->where('author_id', '=', $authorId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Search articles
     */
    public function search(string $term): array
    {
        $term = "%$term%";
        $query = "
            SELECT * FROM {$this->getTable()} 
            WHERE title LIKE ? 
            OR content LIKE ? 
            OR summary LIKE ?
        ";
        return $this->db->fetchAll($query, [$term, $term, $term]);
    }
}
