<?php

namespace App\Core;

class Config
{
    private static array $data = [];
    private static bool $loaded = false;

    /**
     * Load configuration from .env file
     */
    public static function load(string $path = ''): void
    {
        if (self::$loaded) {
            return;
        }

        if (empty($path)) {
            $path = dirname(__DIR__, 2) . '/config/.env';
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            self::$data[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$data[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$data[$key]);
    }

    /**
     * Get all configuration
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$data;
    }
}
