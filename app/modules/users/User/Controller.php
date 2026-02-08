<?php

namespace App\Modules\Users\User;

use App\Core\Classes\BaseController;
use App\Core\Libraries\Encrypt;
use App\Core\Libraries\Sanitizer;

class Controller extends BaseController
{
    /**
     * Create new user
     */
    public function create(array $data): array
    {
        // Sanitize input
        $username = Sanitizer::sanitizeString($data['username'] ?? '');
        $email = Sanitizer::sanitizeEmail($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Validate
        if (!Sanitizer::validateEmail($email)) {
            return ['error' => 'Invalid email'];
        }

        if (!Sanitizer::validateMinLength($password, 6)) {
            return ['error' => 'Password must be at least 6 characters'];
        }

        // Hash password
        $passwordHash = Encrypt::hashPassword($password);

        // Insert
        $insertData = [
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'first_name' => Sanitizer::sanitizeString($data['first_name'] ?? ''),
            'last_name' => Sanitizer::sanitizeString($data['last_name'] ?? ''),
            'is_active' => $data['is_active'] ?? 1,
        ];

        $this->repository->insert($insertData);

        return ['success' => true, 'message' => 'User created successfully'];
    }

    /**
     * Read user by ID
     */
    public function read(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);
        $user = $this->repository->findById($id);

        if (!$user) {
            return ['error' => 'User not found'];
        }

        // Remove password from response
        unset($user['password']);

        return $user;
    }

    /**
     * Read all users
     */
    public function readAll(array $filters = []): array
    {
        $users = $this->repository->findAll();

        // Remove passwords
        foreach ($users as &$user) {
            unset($user['password']);
        }

        return [
            'count' => count($users),
            'data' => $users
        ];
    }

    /**
     * Update user
     */
    public function update(string $id, array $data): array
    {
        $id = Sanitizer::sanitizeInt($id);

        // Check if user exists
        $user = $this->repository->findById($id);
        if (!$user) {
            return ['error' => 'User not found'];
        }

        // Sanitize input
        $updateData = [];
        if (isset($data['username'])) {
            $updateData['username'] = Sanitizer::sanitizeString($data['username']);
        }
        if (isset($data['email'])) {
            $updateData['email'] = Sanitizer::sanitizeEmail($data['email']);
        }
        if (isset($data['password'])) {
            $updateData['password'] = Encrypt::hashPassword($data['password']);
        }
        if (isset($data['first_name'])) {
            $updateData['first_name'] = Sanitizer::sanitizeString($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $updateData['last_name'] = Sanitizer::sanitizeString($data['last_name']);
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        $this->repository->update($id, $updateData);

        return ['success' => true, 'message' => 'User updated successfully'];
    }

    /**
     * Delete user
     */
    public function delete(string $id): array
    {
        $id = Sanitizer::sanitizeInt($id);

        // Check if user exists
        $user = $this->repository->findById($id);
        if (!$user) {
            return ['error' => 'User not found'];
        }

        $this->repository->delete($id);

        return ['success' => true, 'message' => 'User deleted successfully'];
    }
}
