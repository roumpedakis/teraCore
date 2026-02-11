<?php

namespace App\Core;

use App\Modules\Core\User\Repository as UserRepository;

/**
 * UserController
 * Handles user CRUD operations for admin panel
 */
class UserController
{
    private UserRepository $userRepo;
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userRepo = new UserRepository($this->db);
    }

    /**
     * List all users
     * GET /api/users
     */
    public function list(array $params = []): array
    {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
            $search = $params['search'] ?? '';

            $users = $this->userRepo->findAll();

            // Apply search filter
            if (!empty($search)) {
                $users = array_filter($users, function($user) use ($search) {
                    return stripos($user['username'], $search) !== false ||
                           stripos($user['email'], $search) !== false ||
                           stripos($user['first_name'] ?? '', $search) !== false ||
                           stripos($user['last_name'] ?? '', $search) !== false;
                });
            }

            // Remove password from response
            $users = array_map(function($user) {
                unset($user['password'], $user['refresh_token'], $user['token_expires_at']);
                return $user;
            }, $users);

            return [
                'success' => true,
                'data' => array_values($users),
                'total' => count($users),
                'page' => $page,
                'limit' => $limit
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to list users', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to retrieve users'];
        }
    }

    /**
     * Get single user
     * GET /api/users/{id}
     */
    public function get(int $id): array
    {
        try {
            $user = $this->userRepo->findById($id);

            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Remove sensitive data
            unset($user['password'], $user['refresh_token'], $user['token_expires_at']);

            return [
                'success' => true,
                'data' => $user
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get user', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to retrieve user'];
        }
    }

    /**
     * Create new user
     * POST /api/users
     */
    public function create(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['username']) || empty($data['email'])) {
                return ['success' => false, 'error' => 'Username and email are required'];
            }

            // Check username uniqueness
            $existing = $this->userRepo->where('username', '=', $data['username'])->first();
            if ($existing) {
                return ['success' => false, 'error' => 'Username already exists'];
            }

            // Check email uniqueness
            $existing = $this->userRepo->where('email', '=', $data['email'])->first();
            if ($existing) {
                return ['success' => false, 'error' => 'Email already exists'];
            }

            // Hash password (default if not provided)
            $password = $data['password'] ?? 'password123';
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert user
            $this->userRepo->insert([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ]);

            $userId = (int)$this->db->lastInsertId();
            $user = $this->userRepo->findById($userId);

            // Remove sensitive data
            unset($user['password'], $user['refresh_token'], $user['token_expires_at']);

            Logger::info('User created', ['user_id' => $userId, 'username' => $data['username']]);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to create user', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to create user'];
        }
    }

    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update(int $id, array $data): array
    {
        try {
            // Check if user exists
            $user = $this->userRepo->findById($id);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            $updateData = [];

            // Update allowed fields
            if (isset($data['email'])) {
                // Check email uniqueness
                $existing = $this->userRepo->where('email', '=', $data['email'])->first();
                if ($existing && $existing['id'] != $id) {
                    return ['success' => false, 'error' => 'Email already exists'];
                }
                $updateData['email'] = $data['email'];
            }

            if (isset($data['first_name'])) {
                $updateData['first_name'] = $data['first_name'];
            }

            if (isset($data['last_name'])) {
                $updateData['last_name'] = $data['last_name'];
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = (int)$data['is_active'];
            }

            // Update password if provided
            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            if (empty($updateData)) {
                return ['success' => false, 'error' => 'No fields to update'];
            }

            $this->userRepo->update($id, $updateData);

            $updatedUser = $this->userRepo->findById($id);
            unset($updatedUser['password'], $updatedUser['refresh_token'], $updatedUser['token_expires_at']);

            Logger::info('User updated', ['user_id' => $id]);

            return [
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to update user', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to update user'];
        }
    }

    /**
     * Delete user
     * DELETE /api/users/{id}
     */
    public function delete(int $id): array
    {
        try {
            // Check if user exists
            $user = $this->userRepo->findById($id);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Prevent deleting yourself (TODO: get current user from JWT)
            // For now, just delete

            $sql = "DELETE FROM users WHERE id = ?";
            $this->db->execute($sql, [$id]);

            Logger::info('User deleted', ['user_id' => $id, 'username' => $user['username']]);

            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to delete user', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to delete user'];
        }
    }

    /**
     * Toggle user active status
     * POST /api/users/{id}/toggle-status
     */
    public function toggleStatus(int $id): array
    {
        try {
            $user = $this->userRepo->findById($id);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            $newStatus = $user['is_active'] ? 0 : 1;
            $this->userRepo->update($id, ['is_active' => $newStatus]);

            Logger::info('User status toggled', ['user_id' => $id, 'new_status' => $newStatus]);

            return [
                'success' => true,
                'message' => $newStatus ? 'User activated' : 'User deactivated',
                'is_active' => $newStatus
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to toggle user status', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to toggle status'];
        }
    }

    /**
     * Reset user password
     * POST /api/users/{id}/reset-password
     */
    public function resetPassword(int $id, array $data): array
    {
        try {
            $user = $this->userRepo->findById($id);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            $newPassword = $data['password'] ?? 'password123';
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $this->userRepo->update($id, ['password' => $hashedPassword]);

            Logger::info('User password reset', ['user_id' => $id]);

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to reset password', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to reset password'];
        }
    }

    /**
     * Get user permissions
     * GET /api/users/{id}/permissions
     */
    public function getPermissions(int $id): array
    {
        try {
            $user = $this->userRepo->findById($id);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Get available modules from config (exclude core modules)
            $modulesFile = __DIR__ . '/../../config/modules.json';
            $modules = [];
            if (file_exists($modulesFile)) {
                $modulesData = json_decode(file_get_contents($modulesFile), true);
                if ($modulesData) {
                    foreach ($modulesData as $name => $info) {
                        // Check if module has init.json to determine if it's core
                        $initFile = __DIR__ . '/../modules/' . $name . '/init.json';
                        if (file_exists($initFile)) {
                            $initData = json_decode(file_get_contents($initFile), true);
                            // Only include non-core modules
                            if (empty($initData['isCore']) || $initData['isCore'] !== true) {
                                $modules[$name] = $info['version'] ?? '1.0.0';
                            }
                        } else {
                            // If no init.json, include it (backward compatibility)
                            $modules[$name] = $info['version'] ?? '1.0.0';
                        }
                    }
                }
            }

            // Get user's current permissions (only for non-core modules)
            $userModuleRepo = new UserModuleRepository();
            $allUserModules = $userModuleRepo->getUserModules($id);
            
            // Filter out core modules and non-existent modules
            $userModules = [];
            foreach ($allUserModules as $moduleName => $level) {
                if (isset($modules[$moduleName])) {
                    $userModules[$moduleName] = $level;
                }
            }

            Logger::info('User permissions retrieved', ['user_id' => $id]);

            return [
                'success' => true,
                'data' => [
                    'modules' => $modules,
                    'user_modules' => $userModules
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get user permissions', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to get permissions'];
        }
    }

    /**
     * Save user permissions
     * POST /api/users/{id}/permissions
     */
    public function savePermissions(int $id, array $data): array
    {
        try {
            $user = $this->userRepo->findById($id);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            $permissions = $data['permissions'] ?? [];
            $userModuleRepo = new UserModuleRepository();

            // Update each permission
            foreach ($permissions as $moduleName => $level) {
                $level = (int)$level;
                if ($level === 0) {
                    // Remove access if level is 0
                    $userModuleRepo->removeModuleAccess($id, $moduleName);
                } else {
                    // Set permission level
                    $userModuleRepo->setModulePermission($id, $moduleName, $level);
                }
            }

            Logger::info('User permissions updated', ['user_id' => $id, 'permissions' => $permissions]);

            return [
                'success' => true,
                'message' => 'Permissions updated successfully'
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to save user permissions', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to save permissions'];
        }
    }
}
