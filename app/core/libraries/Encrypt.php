<?php

namespace App\Core\Libraries;

use App\Core\Config;

class Encrypt
{
    private static string $algorithm = 'AES-256-CBC';

    /**
     * Encrypt data
     */
    public static function encrypt(mixed $data, ?string $key = null): string
    {
        $key = $key ?? Config::get('ENCRYPTION_KEY', '');
        if (empty($key)) {
            throw new \RuntimeException('ENCRYPTION_KEY not set in config');
        }

        // Convert data to JSON if not string
        $data = is_string($data) ? $data : json_encode($data);

        // Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$algorithm));

        // Encrypt
        $encrypted = openssl_encrypt($data, self::$algorithm, $key, true, $iv);

        // Combine IV + encrypted data and base64 encode
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt(string $encrypted, ?string $key = null): string
    {
        $key = $key ?? Config::get('ENCRYPTION_KEY', '');
        if (empty($key)) {
            throw new \RuntimeException('ENCRYPTION_KEY not set in config');
        }

        // Base64 decode
        $data = base64_decode($encrypted);

        // Extract IV
        $ivLength = openssl_cipher_iv_length(self::$algorithm);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Decrypt
        return openssl_decrypt($encrypted, self::$algorithm, $key, true, $iv);
    }

    /**
     * Hash password (bcrypt)
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
