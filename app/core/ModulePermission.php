<?php

namespace App\Core;

/**
 * Module Permission Constants
 * Bitwise permissions for module access control
 */
class ModulePermission
{
    // Permission bits
    const NONE   = 0;  // 0000
    const READ   = 1;  // 0001
    const CREATE = 2;  // 0010
    const UPDATE = 4;  // 0100
    const DELETE = 8;  // 1000
    
    // Combined permissions
    const READ_ONLY = self::READ;
    const READ_WRITE = self::READ | self::CREATE | self::UPDATE;
    const FULL_ACCESS = self::READ | self::CREATE | self::UPDATE | self::DELETE;

    /**
     * Check if permission includes specific right
     */
    public static function has(int $permission, int $right): bool
    {
        return ($permission & $right) === $right;
    }

    /**
     * Check if has read permission
     */
    public static function canRead(int $permission): bool
    {
        return self::has($permission, self::READ);
    }

    /**
     * Check if has create permission
     */
    public static function canCreate(int $permission): bool
    {
        return self::has($permission, self::CREATE);
    }

    /**
     * Check if has update permission
     */
    public static function canUpdate(int $permission): bool
    {
        return self::has($permission, self::UPDATE);
    }

    /**
     * Check if has delete permission
     */
    public static function canDelete(int $permission): bool
    {
        return self::has($permission, self::DELETE);
    }

    /**
     * Add permission to existing
     */
    public static function add(int $permission, int $right): int
    {
        return $permission | $right;
    }

    /**
     * Remove permission from existing
     */
    public static function remove(int $permission, int $right): int
    {
        return $permission & ~$right;
    }

    /**
     * Get permission name
     */
    public static function getName(int $permission): string
    {
        if ($permission === self::FULL_ACCESS) {
            return 'Full Access';
        }
        if ($permission === self::READ_WRITE) {
            return 'Read/Write';
        }
        if ($permission === self::READ_ONLY) {
            return 'Read Only';
        }
        if ($permission === self::NONE) {
            return 'No Access';
        }

        $parts = [];
        if (self::has($permission, self::READ)) $parts[] = 'Read';
        if (self::has($permission, self::CREATE)) $parts[] = 'Create';
        if (self::has($permission, self::UPDATE)) $parts[] = 'Update';
        if (self::has($permission, self::DELETE)) $parts[] = 'Delete';

        return implode(', ', $parts) ?: 'No Access';
    }

    /**
     * Get all permission levels
     */
    public static function getAll(): array
    {
        return [
            'none' => self::NONE,
            'read' => self::READ,
            'read_write' => self::READ_WRITE,
            'full_access' => self::FULL_ACCESS,
        ];
    }

    /**
     * Parse permission from string
     */
    public static function fromString(string $permission): int
    {
        return match(strtolower($permission)) {
            'none' => self::NONE,
            'read', 'read_only' => self::READ,
            'read_write', 'write' => self::READ_WRITE,
            'full', 'full_access', 'admin' => self::FULL_ACCESS,
            default => self::NONE,
        };
    }
}
