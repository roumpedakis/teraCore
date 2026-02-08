<?php

namespace App\Modules\Articles\Tag;

use App\Core\Classes\BaseController;

class Controller extends BaseController
{
    public function create(array $data): mixed
    {
        if (empty($data['name'])) {
            return ['error' => 'Tag name is required'];
        }

        return parent::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        if (isset($data['name']) && empty($data['name'])) {
            return ['error' => 'Tag name cannot be empty'];
        }

        return parent::update($id, $data);
    }
}
