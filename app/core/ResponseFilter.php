<?php

namespace App\Core;

/**
 * ResponseFilter - Prevent sensitive data exposure
 * 
 * Implements OWASP API3:2023 - Broken Object Property Level Authorization
 * Filters out sensitive fields from API responses
 */
class ResponseFilter
{
    /**
     * Sensitive fields to remove from responses
     */
    private static array $sensitiveFields = [
        'password',
        'password_hash',
        'refresh_token',
        'oauth2_provider',
        'token_expires_at',
        'reset_token',
        'reset_token_expires',
        'verification_token',
        'api_secret',
        'secret_key',
        'private_key',
        'salt'
    ];
    
    /**
     * Filter sensitive data from response
     */
    public static function filter(mixed $data, array $additionalFields = []): mixed
    {
        $fieldsToRemove = array_merge(self::$sensitiveFields, $additionalFields);
        
        if (is_array($data)) {
            // Handle array of objects (like user list)
            if (isset($data[0]) && is_array($data[0])) {
                return array_map(function($item) use ($fieldsToRemove) {
                    return self::filterObject($item, $fieldsToRemove);
                }, $data);
            }
            
            // Handle single object
            return self::filterObject($data, $fieldsToRemove);
        }
        
        if (is_object($data)) {
            $data = (array) $data;
            return self::filterObject($data, $fieldsToRemove);
        }
        
        return $data;
    }
    
    /**
     * Filter object/array
     */
    private static function filterObject(array $data, array $fieldsToRemove): array
    {
        foreach ($fieldsToRemove as $field) {
            unset($data[$field]);
        }
        
        // Recursively filter nested objects
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::filterObject($value, $fieldsToRemove);
            }
        }
        
        return $data;
    }
    
    /**
     * Filter user data specifically
     */
    public static function filterUser(array $user): array
    {
        return self::filter($user);
    }
    
    /**
     * Filter multiple users
     */
    public static function filterUsers(array $users): array
    {
        return array_map([self::class, 'filterUser'], $users);
    }
    
    /**
     * Add custom sensitive field
     */
    public static function addSensitiveField(string $field): void
    {
        if (!in_array($field, self::$sensitiveFields)) {
            self::$sensitiveFields[] = $field;
        }
    }
    
    /**
     * Remove field from sensitive list (use with caution)
     */
    public static function removeSensitiveField(string $field): void
    {
        $key = array_search($field, self::$sensitiveFields);
        if ($key !== false) {
            unset(self::$sensitiveFields[$key]);
            self::$sensitiveFields = array_values(self::$sensitiveFields);
        }
    }
    
    /**
     * Get list of sensitive fields
     */
    public static function getSensitiveFields(): array
    {
        return self::$sensitiveFields;
    }
}
