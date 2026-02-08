<?php

namespace App\Core\Handlers;

use App\Core\Config;
use App\Core\Libraries\Encrypt;

class SessionHandler
{
    private static bool $initialized = false;

    /**
     * Initialize session
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        session_start([
            'cookie_lifetime' => (int)Config::get('SESSION_TIMEOUT', 3600),
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ]);

        self::$initialized = true;
    }

    /**
     * Set session value
     */
    public static function set(string $key, mixed $value, bool $encrypt = false): void
    {
        self::init();

        if ($encrypt && Config::get('SESSION_ENCRYPT') === 'true') {
            $value = Encrypt::encrypt($value);
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();

        $value = $_SESSION[$key] ?? $default;

        if (is_string($value) && $value !== '' && Config::get('SESSION_ENCRYPT') === 'true') {
            try {
                $value = Encrypt::decrypt($value);
            } catch (\Exception $e) {
                // If decryption fails, return original value
                return $value;
            }
        }

        return $value;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        self::init();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     */
    public static function remove(string $key): void
    {
        self::init();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session
     */
    public static function destroy(): void
    {
        self::init();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Get all session data
     */
    public static function all(): array
    {
        self::init();
        return $_SESSION;
    }

    /**
     * Regenerate session ID
     */
    public static function regenerate(): void
    {
        self::init();
        session_regenerate_id(true);
    }
}
