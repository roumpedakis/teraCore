<?php

namespace App\Modules\Core\Admin;

use App\Core\Classes\BaseController;

/**
 * Admin Controller
 * Handles HTTP requests for admin management
 */
class Controller extends BaseController
{
    /**
     * Create new admin
     */
    public function create(array $data): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Admin name is required'];
        }

        // Check if name already exists
        $existing = $this->repository->findByName($data['name']);
        if ($existing) {
            return ['success' => false, 'error' => 'Admin name already exists'];
        }

        // Ensure status is valid
        $status = $data['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive', 'suspended'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        $admin = $this->repository->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Admin created successfully',
            'data' => $admin
        ];
    }

    /**
     * Read admin by ID
     */
    public function read(int $id): array
    {
        $admin = $this->repository->find($id);
        
        if (!$admin) {
            return ['success' => false, 'error' => 'Admin not found'];
        }

        return [
            'success' => true,
            'data' => $admin
        ];
    }

    /**
     * Read all admins
     */
    public function readAll(array $filters = []): array
    {
        $status = $filters['status'] ?? null;

        if ($status) {
            $admins = $this->repository->where('status', '=', $status)->get();
        } else {
            $admins = $this->repository->orderBy('created_at', 'DESC')->get();
        }

        return [
            'success' => true,
            'count' => count($admins),
            'data' => $admins
        ];
    }

    /**
     * Update admin
     */
    public function update(int $id, array $data): array
    {
        $admin = $this->repository->find($id);
        
        if (!$admin) {
            return ['success' => false, 'error' => 'Admin not found'];
        }

        // Validate status if provided
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'inactive', 'suspended'])) {
                return ['success' => false, 'error' => 'Invalid status'];
            }
        }

        $updated = $this->repository->update($id, $data);

        if ($updated) {
            return [
                'success' => true,
                'message' => 'Admin updated successfully',
                'data' => $this->repository->find($id)
            ];
        }

        return ['success' => false, 'error' => 'Update failed'];
    }

    /**
     * Delete admin
     */
    public function delete(int $id): array
    {
        $admin = $this->repository->find($id);
        
        if (!$admin) {
            return ['success' => false, 'error' => 'Admin not found'];
        }

        if ($this->repository->delete($id)) {
            return ['success' => true, 'message' => 'Admin deleted successfully'];
        }

        return ['success' => false, 'error' => 'Delete failed'];
    }
}
