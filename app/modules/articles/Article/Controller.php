<?php

namespace App\Modules\Articles\Article;

use App\Core\Classes\BaseController;
use App\Core\Libraries\Sanitizer;

class Controller extends BaseController
{
    /**
     * Create new article
     */
    public function create(array $data): array
    {
        $title = Sanitizer::sanitizeString($data['title'] ?? '');
        $content = $data['content'] ?? '';
        $authorId = Sanitizer::sanitizeInt($data['author_id'] ?? 0);

        if (empty($title) || empty($content) || $authorId === 0) {
            return ['error' => 'Title, content, and author are required'];
        }

        // Generate slug
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $this->repository->insert([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'summary' => Sanitizer::sanitizeString($data['summary'] ?? ''),
            'author_id' => $authorId,
            'status' => $data['status'] ?? 'draft',
        ]);

        return ['success' => true, 'message' => 'Article created successfully'];
    }

    /**
     * Read article
     */
    public function read(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $article = $this->repository->findById($id);

        if (!$article) {
            return ['error' => 'Article not found'];
        }

        return $article;
    }

    /**
     * Read all articles
     */
    public function readAll(array $filters = []): array
    {
        $query = $this->repository;

        if (!empty($filters['status'])) {
            $query = $query->where('status', '=', $filters['status']);
        }

        if (!empty($filters['author_id'])) {
            $query = $query->where('author_id', '=', $filters['author_id']);
        }

        $articles = $query->orderBy('created_at', 'DESC')->get();

        return [
            'count' => count($articles),
            'data' => $articles
        ];
    }

    /**
     * Update article
     */
    public function update(string $id, array $data): array
    {
        $id = Sanitizer::sanitizeInt($id);

        $article = $this->repository->findById($id);
        if (!$article) {
            return ['error' => 'Article not found'];
        }

        $updateData = [];
        if (isset($data['title'])) {
            $updateData['title'] = Sanitizer::sanitizeString($data['title']);
        }
        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }
        if (isset($data['summary'])) {
            $updateData['summary'] = Sanitizer::sanitizeString($data['summary']);
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $this->repository->update($id, $updateData);

        return ['success' => true, 'message' => 'Article updated successfully'];
    }

    /**
     * Delete article
     */
    public function delete(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);

        $article = $this->repository->findById($id);
        if (!$article) {
            return ['error' => 'Article not found'];
        }

        $this->repository->delete($id);

        return ['success' => true, 'message' => 'Article deleted successfully'];
    }
}
