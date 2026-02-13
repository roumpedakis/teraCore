<?php

namespace App\Core;

/**
 * Module Access Middleware
 * Controls access to modules based on JWT permissions
 */
class ModuleAccessMiddleware
{
    /**
     * Check if user has access to module
     * 
     * @param string $moduleName Module to check
     * @param int $requiredPermission Required permission level (default: READ)
     * @return array Success/error response
     */
    public static function checkAccess(string $moduleName, int $requiredPermission = ModulePermission::READ): array
    {
        // Get JWT payload from current request
        $payload = AuthMiddleware::authenticate();

        if (!$payload) {
            return [
                'success' => false,
                'error' => 'Authentication required',
                'code' => 401,
                'error_code' => ErrorCodes::AUTH_REQUIRED,
            ];
        }

        // Check if modules exist in JWT
        if (!isset($payload['modules'])) {
            return [
                'success' => false,
                'error' => 'No module permissions found',
                'code' => 403,
                'error_code' => ErrorCodes::MODULE_PERMISSIONS_MISSING,
            ];
        }

        $userModules = $payload['modules'];

        // Check if user has access to this module
        if (!isset($userModules[$moduleName])) {
            return [
                'success' => false,
                'error' => "No access to module: {$moduleName}",
                'code' => 403,
                'error_code' => ErrorCodes::MODULE_NO_ACCESS,
            ];
        }

        $userPermission = $userModules[$moduleName];

        // Check if user has required permission level
        if (!ModulePermission::has($userPermission, $requiredPermission)) {
            $permissionName = ModulePermission::getName($requiredPermission);
            return [
                'success' => false,
                'error' => "Insufficient permissions for module: {$moduleName}. Required: {$permissionName}",
                'code' => 403,
                'error_code' => ErrorCodes::MODULE_INSUFFICIENT,
            ];
        }

        return [
            'success' => true,
            'user_id' => $payload['user_id'],
            'username' => $payload['username'] ?? null,
            'permission' => $userPermission,
        ];
    }

    /**
     * Require CRUD permissions for module
     */
    public static function requireRead(string $moduleName): array
    {
        return self::checkAccess($moduleName, ModulePermission::READ);
    }

    public static function requireCreate(string $moduleName): array
    {
        return self::checkAccess($moduleName, ModulePermission::CREATE);
    }

    public static function requireUpdate(string $moduleName): array
    {
        return self::checkAccess($moduleName, ModulePermission::UPDATE);
    }

    public static function requireDelete(string $moduleName): array
    {
        return self::checkAccess($moduleName, ModulePermission::DELETE);
    }

    /**
     * Get all accessible modules for current user
     */
    public static function getUserModules(): array
    {
        $payload = AuthMiddleware::authenticate();
        
        if (!$payload || !isset($payload['modules'])) {
            return [];
        }

        return $payload['modules'];
    }

    /**
     * Get user permission for specific module
     */
    public static function getUserModulePermission(string $moduleName): int
    {
        $modules = self::getUserModules();
        return $modules[$moduleName] ?? ModulePermission::NONE;
    }

    /**
     * Check permission from HTTP method
     */
    public static function checkAccessByMethod(string $moduleName, string $httpMethod): array
    {
        $permission = match(strtoupper($httpMethod)) {
            'GET', 'HEAD', 'OPTIONS' => ModulePermission::READ,
            'POST' => ModulePermission::CREATE,
            'PUT', 'PATCH' => ModulePermission::UPDATE,
            'DELETE' => ModulePermission::DELETE,
            default => ModulePermission::NONE,
        };

        return self::checkAccess($moduleName, $permission);
    }
}
