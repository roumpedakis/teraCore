<?php

namespace App\Modules\Users\Role;

use App\Core\Classes\BaseController;
use App\Core\Libraries\Sanitizer;

class Controller extends BaseController
{
    /**
     * Create new role
     */
    public function create(array $data): array
    {
        $name = Sanitizer::sanitizeString($data['name'] ?? '');

        if (empty($name)) {
            return ['error' => 'Role name is required'];
        }

        $this->repository->insert([
            'name' => $name,
            'description' => Sanitizer::sanitizeString($data['description'] ?? ''),
        ]);

        return ['success' => true, 'message' => 'Role created successfully'];
    }

    /**
     * Read role
     */
    public function read(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $role = $this->repository->findById($id);

        if (!$role) {
            return ['error' => 'Role not found'];
        }

        return $role;
    }

    /**
     * Read all roles
     */
    public function readAll(array $filters = []): array
    {
        $roles = $this->repository->findAll();

        return [
            'count' => count($roles),
            'data' => $roles
        ];
    }

    /**
     * Update role
     */
    public function update(string $id, array $data): array
    {
        $id = Sanitizer::sanitizeInt($id);

        $role = $this->repository->findById($id);
        if (!$role) {
            return ['error' => 'Role not found'];
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = Sanitizer::sanitizeString($data['name']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = Sanitizer::sanitizeString($data['description']);
        }

        $this->repository->update($id, $updateData);

        return ['success' => true, 'message' => 'Role updated successfully'];
    }

    /**
     * Delete role
     */
    public function delete(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);

        $role = $this->repository->findById($id);
        if (!$role) {
            return ['error' => 'Role not found'];
        }

        $this->repository->delete($id);

        return ['success' => true, 'message' => 'Role deleted successfully'];
    }
}
