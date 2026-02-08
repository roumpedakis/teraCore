<?php

namespace App\Modules\Articles\Article;

use App\Core\Classes\BaseController;

class Controller extends BaseController
{
    public function create(array $data): mixed 
    {
        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            return ['error' => 'Title and content are required'];
        }

        // Generate slug
        $slug = strtolower($data['title']);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $data['slug'] = $slug;

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'draft';
        }

        // Validate status
        $validStatuses = ['draft', 'published', 'archived'];
        if (!in_array($data['status'], $validStatuses)) {
            return ['error' => 'Invalid status. Must be: draft, published, or archived'];
        }

        return parent::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        // Generate slug if title is being updated
        if (isset($data['title'])) {
            $slug = strtolower($data['title']);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            $data['slug'] = $slug;
        }

        // Validate status if provided
        if (isset($data['status'])) {
            $validStatuses = ['draft', 'published', 'archived'];
            if (!in_array($data['status'], $validStatuses)) {
                return ['error' => 'Invalid status. Must be: draft, published, or archived'];
            }
        }

        return parent::update($id, $data);
    }
}
