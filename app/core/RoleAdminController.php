<?php

namespace App\Core;

use App\Core\Libraries\Sanitizer;
use App\Modules\Core\Role\Repository as RoleRepository;

class RoleAdminController
{
    private RoleRepository $roleRepo;

    public function __construct()
    {
        $this->roleRepo = new RoleRepository(Database::getInstance());
    }

    public function list(array $params = []): array
    {
        try {
            $search = trim((string)($params['search'] ?? ''));
            $roles = $this->roleRepo->findAll();

            if ($search !== '') {
                $roles = array_filter($roles, function (array $role) use ($search): bool {
                    return stripos($role['name'] ?? '', $search) !== false ||
                        stripos($role['description'] ?? '', $search) !== false;
                });
            }

            return [
                'success' => true,
                'data' => array_values($roles),
                'total' => count($roles)
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to list roles', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to retrieve roles'];
        }
    }

    public function get(int $id): array
    {
        try {
            $role = $this->roleRepo->findById($id);
            if (!$role) {
                return ['success' => false, 'error' => 'Role not found'];
            }

            return ['success' => true, 'data' => $role];
        } catch (\Exception $e) {
            Logger::error('Failed to get role', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to retrieve role'];
        }
    }

    public function create(array $data): array
    {
        try {
            $name = Sanitizer::sanitizeString($data['name'] ?? '');
            $description = Sanitizer::sanitizeString($data['description'] ?? '');

            if ($name === '') {
                return ['success' => false, 'error' => 'Role name is required'];
            }

            $existing = $this->roleRepo->where('name', '=', $name)->first();
            if ($existing) {
                return ['success' => false, 'error' => 'Role name already exists'];
            }

            $this->roleRepo->insert([
                'name' => $name,
                'description' => $description
            ]);

            return ['success' => true, 'message' => 'Role created'];
        } catch (\Exception $e) {
            Logger::error('Failed to create role', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to create role'];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $role = $this->roleRepo->findById($id);
            if (!$role) {
                return ['success' => false, 'error' => 'Role not found'];
            }

            $payload = [];
            if (isset($data['name'])) {
                $name = Sanitizer::sanitizeString($data['name']);
                if ($name === '') {
                    return ['success' => false, 'error' => 'Role name cannot be empty'];
                }

                $existing = $this->roleRepo->where('name', '=', $name)->first();
                if ($existing && (int)$existing['id'] !== $id) {
                    return ['success' => false, 'error' => 'Role name already exists'];
                }

                $payload['name'] = $name;
            }

            if (isset($data['description'])) {
                $payload['description'] = Sanitizer::sanitizeString($data['description']);
            }

            if (!empty($payload)) {
                $this->roleRepo->update($id, $payload);
            }

            return ['success' => true, 'message' => 'Role updated'];
        } catch (\Exception $e) {
            Logger::error('Failed to update role', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to update role'];
        }
    }

    public function delete(int $id): array
    {
        try {
            $role = $this->roleRepo->findById($id);
            if (!$role) {
                return ['success' => false, 'error' => 'Role not found'];
            }

            $this->roleRepo->delete($id);
            return ['success' => true, 'message' => 'Role deleted'];
        } catch (\Exception $e) {
            Logger::error('Failed to delete role', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to delete role'];
        }
    }
}
