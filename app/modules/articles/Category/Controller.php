<?php

namespace App\Modules\Articles\Category;

use App\Core\Classes\BaseController;

class Controller extends BaseController
{
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            return ['error' => 'Category name is required'];
        }

        return parent::create($data);
    }

    public function update(string $id, array $data): array
    {
        if (isset($data['name']) && empty($data['name'])) {
            return ['error' => 'Category name cannot be empty'];
        }

        return parent::update($id, $data);
    }
}
