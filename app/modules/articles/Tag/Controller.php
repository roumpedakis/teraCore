<?php

namespace App\Modules\Articles\Tag;

use App\Core\Classes\BaseController;
use App\Core\Libraries\Sanitizer;

class Controller extends BaseController
{
    public function create(array $data): array
    {
        $name = Sanitizer::sanitizeString($data['name'] ?? '');

        if (empty($name)) {
            return ['error' => 'Tag name is required'];
        }

        $this->repository->insert(['name' => $name]);

        return ['success' => true, 'message' => 'Tag created successfully'];
    }

    public function read(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $tag = $this->repository->findById($id);

        if (!$tag) {
            return ['error' => 'Tag not found'];
        }

        return $tag;
    }

    public function readAll(array $filters = []): array
    {
        $tags = $this->repository->findAll();

        return [
            'count' => count($tags),
            'data' => $tags
        ];
    }

    public function update(string $id, array $data): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $tag = $this->repository->findById($id);

        if (!$tag) {
            return ['error' => 'Tag not found'];
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = Sanitizer::sanitizeString($data['name']);
        }

        $this->repository->update($id, $updateData);

        return ['success' => true, 'message' => 'Tag updated successfully'];
    }

    public function delete(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $tag = $this->repository->findById($id);

        if (!$tag) {
            return ['error' => 'Tag not found'];
        }

        $this->repository->delete($id);

        return ['success' => true, 'message' => 'Tag deleted successfully'];
    }
}
