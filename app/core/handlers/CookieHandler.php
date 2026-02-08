<?php

namespace App\Core\Handlers;

class CookieHandler
{
    /**
     * Set cookie
     */
    public static function set(
        string $name,
        mixed $value,
        ?int $expire = null,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): void {
        $expire = $expire ?? time() + (30 * 24 * 60 * 60); // 30 days default

        setcookie($name, (string)$value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Strict',
        ]);
    }

    /**
     * Get cookie
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Check if cookie exists
     */
    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete cookie
     */
    public static function delete(string $name, string $path = '/', string $domain = ''): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain,
            'httponly' => true,
        ]);

        unset($_COOKIE[$name]);
    }

    /**
     * Get all cookies
     */
    public static function all(): array
    {
        return $_COOKIE;
    }
}
