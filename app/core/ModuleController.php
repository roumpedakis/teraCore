<?php

namespace App\Core;

/**
 * Module Controller
 * API endpoints for module management
 */
class ModuleController
{
    private UserModuleRepository $userModuleRepo;

    public function __construct()
    {
        $this->userModuleRepo = new UserModuleRepository();
    }

    /**
     * GET /api/modules
     * List all available modules
     */
    public function list(): array
    {
        $modules = ModuleLoader::getModules();
        $pricing = ModuleLoader::getModulePricing();

        $result = [];
        foreach ($modules as $name => $module) {
            $result[] = [
                'name' => $name,
                'version' => $module['metadata']['version'] ?? '1.0.0',
                'description' => $module['metadata']['description'] ?? '',
                'dependencies' => $module['metadata']['dependencies'] ?? [],
                'isCore' => $module['metadata']['isCore'] ?? false,
                'price' => $pricing[$name]['price'] ?? 0,
                'currency' => $pricing[$name]['currency'] ?? 'EUR',
                'billingPeriod' => $pricing[$name]['billingPeriod'] ?? 'monthly',
                'entities' => count($module['entities'] ?? []),
            ];
        }

        return ['success' => true, 'data' => $result];
    }

    /**
     * GET /api/modules/pricing
     * Get pricing information for all modules
     */
    public function pricing(): array
    {
        $pricing = ModuleLoader::getModulePricing();
        $coreModules = count(ModuleLoader::getCoreModules());
        $paidModules = count(ModuleLoader::getPaidModules());

        return [
            'success' => true,
            'data' => [
                'modules' => $pricing,
                'summary' => [
                    'total' => count($pricing),
                    'core' => $coreModules,
                    'paid' => $paidModules,
                ]
            ]
        ];
    }

    /**
     * GET /api/users/{userId}/modules
     * Get user's assigned modules
     */
    public function getUserModules(int $userId): array
    {
        $userModules = $this->userModuleRepo->getUserModules($userId);
        $cost = ModuleLoader::calculateModuleCost(array_keys($userModules));

        $modules = [];
        foreach ($userModules as $moduleName => $permission) {
            $moduleInfo = ModuleLoader::getModule($moduleName);
            $modules[] = [
                'name' => $moduleName,
                'permission' => $permission,
                'permissionName' => ModulePermission::getName($permission),
                'canRead' => ModulePermission::canRead($permission),
                'canCreate' => ModulePermission::canCreate($permission),
                'canUpdate' => ModulePermission::canUpdate($permission),
                'canDelete' => ModulePermission::canDelete($permission),
                'price' => $moduleInfo['metadata']['price'] ?? 0,
                'isCore' => $moduleInfo['metadata']['isCore'] ?? false,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'userId' => $userId,
                'modules' => $modules,
                'billing' => $cost,
            ]
        ];
    }

    /**
     * POST /api/users/{userId}/modules
     * Set user's modules (bulk assign)
     * Body: { modules: { articles: 15, comments: 7 } }
     */
    public function setUserModules(int $userId, array $data): array
    {
        if (!isset($data['modules']) || !is_array($data['modules'])) {
            return ['success' => false, 'error' => 'modules array required'];
        }

        // Validate module names
        $availableModules = ModuleLoader::getModules();
        foreach ($data['modules'] as $moduleName => $permission) {
            if (!isset($availableModules[$moduleName])) {
                return ['success' => false, 'error' => "Invalid module: {$moduleName}"];
            }
            
            if (!is_numeric($permission) || $permission < 0 || $permission > 15) {
                return ['success' => false, 'error' => "Invalid permission for {$moduleName}"];
            }
        }

        // Set modules
        $this->userModuleRepo->setUserModules($userId, $data['modules']);

        // Calculate new cost
        $cost = ModuleLoader::calculateModuleCost(array_keys($data['modules']));

        return [
            'success' => true,
            'message' => 'Modules updated successfully',
            'data' => [
                'userId' => $userId,
                'modulesCount' => count($data['modules']),
                'billing' => $cost,
            ]
        ];
    }

    /**
     * PUT /api/users/{userId}/modules/{moduleName}
     * Update single module permission
     * Body: { permission: 15 }
     */
    public function updateModulePermission(int $userId, string $moduleName, array $data): array
    {
        if (!isset($data['permission'])) {
            return ['success' => false, 'error' => 'permission required'];
        }

        $permission = (int)$data['permission'];
        
        if ($permission < 0 || $permission > 15) {
            return ['success' => false, 'error' => 'Invalid permission value (0-15)'];
        }

        // Validate module exists
        $module = ModuleLoader::getModule($moduleName);
        if (!$module) {
            return ['success' => false, 'error' => "Module not found: {$moduleName}"];
        }

        $this->userModuleRepo->setModulePermission($userId, $moduleName, $permission);

        return [
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => [
                'userId' => $userId,
                'module' => $moduleName,
                'permission' => $permission,
                'permissionName' => ModulePermission::getName($permission),
            ]
        ];
    }

    /**
     * DELETE /api/users/{userId}/modules/{moduleName}
     * Remove module access from user
     */
    public function removeModuleAccess(int $userId, string $moduleName): array
    {
        $this->userModuleRepo->removeModuleAccess($userId, $moduleName);

        return [
            'success' => true,
            'message' => 'Module access removed successfully',
            'data' => [
                'userId' => $userId,
                'module' => $moduleName,
            ]
        ];
    }

    /**
     * GET /api/users/{userId}/modules/cost
     * Calculate user's monthly cost
     */
    public function getUserModuleCost(int $userId): array
    {
        $userModules = $this->userModuleRepo->getUserModules($userId);
        $cost = ModuleLoader::calculateModuleCost(array_keys($userModules));

        return [
            'success' => true,
            'data' => $cost
        ];
    }
}
