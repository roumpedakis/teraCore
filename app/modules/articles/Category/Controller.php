<?php

namespace App\Modules\Articles\Category;

use App\Core\Classes\BaseController;
use App\Core\Libraries\Sanitizer;

class Controller extends BaseController
{
    public function create(array $data): array
    {
        $name = Sanitizer::sanitizeString($data['name'] ?? '');

        if (empty($name)) {
            return ['error' => 'Category name is required'];
        }

        $this->repository->insert([
            'name' => $name,
            'description' => Sanitizer::sanitizeString($data['description'] ?? ''),
        ]);

        return ['success' => true, 'message' => 'Category created successfully'];
    }

    public function read(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $category = $this->repository->findById($id);

        if (!$category) {
            return ['error' => 'Category not found'];
        }

        return $category;
    }

    public function readAll(array $filters = []): array
    {
        $categories = $this->repository->findAll();

        return [
            'count' => count($categories),
            'data' => $categories
        ];
    }

    public function update(string $id, array $data): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $category = $this->repository->findById($id);

        if (!$category) {
            return ['error' => 'Category not found'];
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = Sanitizer::sanitizeString($data['name']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = Sanitizer::sanitizeString($data['description']);
        }

        $this->repository->update($id, $updateData);

        return ['success' => true, 'message' => 'Category updated successfully'];
    }

    public function delete(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $category = $this->repository->findById($id);

        if (!$category) {
            return ['error' => 'Category not found'];
        }

        $this->repository->delete($id);

        return ['success' => true, 'message' => 'Category deleted successfully'];
    }
}
