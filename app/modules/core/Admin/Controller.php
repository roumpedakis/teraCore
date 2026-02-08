<?php

namespace App\Modules\Core\Admin;

use App\Core\Classes\BaseController;

class Controller extends BaseController
{
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            return ['error' => 'Admin name is required'];
        }

        $existing = $this->repository->findByName($data['name']);
        if ($existing) {
            return ['error' => 'Admin name already exists'];
        }

        if (empty($data['status'])) {
            $data['status'] = 'active';
        }

        $validStatuses = ['active', 'inactive', 'suspended'];
        if (!in_array($data['status'], $validStatuses)) {
            return ['error' => 'Invalid status. Must be: active, inactive, or suspended'];
        }

        return parent::create($data);
    }

    public function update(string $id, array $data): array
    {
        if (isset($data['name']) && empty($data['name'])) {
            return ['error' => 'Admin name cannot be empty'];
        }

        if (isset($data['status'])) {
            $validStatuses = ['active', 'inactive', 'suspended'];
            if (!in_array($data['status'], $validStatuses)) {
                return ['error' => 'Invalid status. Must be: active, inactive, or suspended'];
            }
        }

        return parent::update($id, $data);
    }
}
