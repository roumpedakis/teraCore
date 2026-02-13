<?php

namespace App\Core;

/**
 * UserModule Repository
 * Manages user-module associations and permissions
 */
class UserModuleRepository
{
    private Database $db;
    private string $table = 'user_modules';
    private string $purchaseTable = 'user_module_purchases';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all modules for a user with their permissions
     * Returns array: ['module_name' => permission_level]
     */
    public function getUserModules(int $userId): array
    {
                $sql = "SELECT um.module_name, um.permission_level
                                FROM {$this->table} um
                                JOIN {$this->purchaseTable} p
                                    ON p.user_id = um.user_id AND p.module_name = um.module_name
                                WHERE um.user_id = :user_id
                                    AND um.enabled = 1
                                    AND p.status = 'active'";

        $results = $this->db->fetchAll($sql, ['user_id' => $userId]);
        
        $modules = [];
        foreach ($results as $row) {
            $modules[$row['module_name']] = (int)$row['permission_level'];
        }

        return $modules;
    }

    /**
     * Get permission level for specific module
     */
    public function getModulePermission(int $userId, string $moduleName): int
    {
                $sql = "SELECT um.permission_level
                                FROM {$this->table} um
                                JOIN {$this->purchaseTable} p
                                    ON p.user_id = um.user_id AND p.module_name = um.module_name
                                WHERE um.user_id = :user_id
                                    AND um.module_name = :module_name
                                    AND um.enabled = 1
                                    AND p.status = 'active'
                                LIMIT 1";

        $result = $this->db->fetch($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName
        ]);

        return $result ? (int)$result['permission_level'] : 0;
    }

    /**
     * Check if user has access to module
     */
    public function hasModuleAccess(int $userId, string $moduleName): bool
    {
        return $this->getModulePermission($userId, $moduleName) > 0;
    }

    /**
     * Set module permission for user
     */
    public function setModulePermission(int $userId, string $moduleName, int $permissionLevel): bool
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, module_name, permission_level)
                VALUES (:user_id, :module_name, :permission_level)
                ON DUPLICATE KEY UPDATE 
                permission_level = VALUES(permission_level),
                enabled = 1,
                updated_at = CURRENT_TIMESTAMP";

        return $this->db->execute($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName,
            'permission_level' => $permissionLevel
        ]);
    }

    /**
     * Remove module access for user
     */
    public function removeModuleAccess(int $userId, string $moduleName): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id 
                AND module_name = :module_name";

        return $this->db->execute($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName
        ]);
    }

    /**
     * Disable module for user (soft delete)
     */
    public function disableModule(int $userId, string $moduleName): bool
    {
        $sql = "UPDATE {$this->table} 
                SET enabled = 0, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND module_name = :module_name";

        return $this->db->execute($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName
        ]);
    }

    /**
     * Enable module for user
     */
    public function enableModule(int $userId, string $moduleName): bool
    {
        $sql = "UPDATE {$this->table} 
                SET enabled = 1, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND module_name = :module_name";

        return $this->db->execute($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName
        ]);
    }

    /**
     * Get all users with access to a specific module
     */
    public function getUsersByModule(string $moduleName): array
    {
        $sql = "SELECT um.*, u.username, u.email 
                FROM {$this->table} um
                JOIN users u ON um.user_id = u.id
                WHERE um.module_name = :module_name 
                AND um.enabled = 1";

        return $this->db->fetchAll($sql, ['module_name' => $moduleName]);
    }

    /**
     * Bulk set modules for user
     */
    public function setUserModules(int $userId, array $modules): bool
    {
        // Remove all existing modules
        $this->db->execute("DELETE FROM {$this->table} WHERE user_id = :user_id", 
            ['user_id' => $userId]);

        // Add new modules
        foreach ($modules as $moduleName => $permissionLevel) {
            $this->setModulePermission($userId, $moduleName, $permissionLevel);
        }

        return true;
    }

    /**
     * Get module statistics for user
     */
    public function getUserModuleStats(int $userId): array
    {
        $modules = $this->getUserModules($userId);
        
        $stats = [
            'total' => count($modules),
            'enabled' => count($modules),
            'read_only' => 0,
            'full_access' => 0,
        ];

        foreach ($modules as $permission) {
            if ($permission === ModulePermission::READ) {
                $stats['read_only']++;
            } elseif ($permission === ModulePermission::FULL_ACCESS) {
                $stats['full_access']++;
            }
        }

        return $stats;
    }
}
