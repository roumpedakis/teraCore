<?php

namespace App\Modules\Users\Role;

use App\Core\Classes\BaseController;

class Controller extends BaseController
{
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            return ['error' => 'Role name is required'];
        }

        return parent::create($data);
    }

    public function update(string $id, array $data): array
    {
        if (isset($data['name']) && empty($data['name'])) {
            return ['error' => 'Role name cannot be empty'];
        }

        return parent::update($id, $data);
    }
}
