<?php

namespace App\Core;

use Exception;

/**
 * JWT - JSON Web Token handler
 * Manages OAuth2 token generation, validation, and refresh
 * 
 * Token format: header.payload.signature
 * Payload contains: user_id, exp (expiry timestamp), iat (issued at)
 */
class JWT
{
    private static string $secret = '';
    private static string $algorithm = 'HS256';

    /**
     * Initialize JWT secret from config
     */
    private static function initialize(): void
    {
        if (empty(self::$secret)) {
            self::$secret = Config::get('JWT_SECRET', 'your-secret-key-change-in-production');
        }
    }

    /**
     * Generate JWT access token
     * 
     * @param int $userId User ID to encode in token
     * @param int $expiresIn Token lifetime in seconds (default from config = 8 hours)
     * @param array $additionalData Additional payload data (e.g., modules, permissions)
     * @return string JWT token
     */
    public static function generateToken(int $userId, int $expiresIn = null, array $additionalData = []): string
    {
        self::initialize();
        
        if ($expiresIn === null) {
            $expiresIn = (int)Config::get('JWT_EXPIRES_IN', 28800);
        }

        $now = time();
        $expires = $now + $expiresIn;

        // Create header
        $header = [
            'alg' => self::$algorithm,
            'typ' => 'JWT'
        ];

        // Create payload
        $payload = array_merge([
            'user_id' => $userId,
            'iat' => $now,
            'exp' => $expires,
        ], $additionalData);

        // Encode header and payload
        $headerB64 = self::base64UrlEncode(json_encode($header));
        $payloadB64 = self::base64UrlEncode(json_encode($payload));

        // Create signature
        $dataToSign = "$headerB64.$payloadB64";
        $signature = self::sign($dataToSign);
        $signatureB64 = self::base64UrlEncode($signature);

        return "$dataToSign.$signatureB64";
    }

    /**
     * Generate refresh token (longer-lived, used to get new access tokens)
     * 
     * @param int $userId User ID
     * @param int $expiresIn Lifetime in seconds (default from config = 7 days)
     * @return string Refresh token
     */
    public static function generateRefreshToken(int $userId, int $expiresIn = null): string
    {
        self::initialize();
        
        if ($expiresIn === null) {
            $expiresIn = (int)Config::get('JWT_REFRESH_EXPIRES_IN', 604800);
        }

        $now = time();
        $expires = $now + $expiresIn;

        $payload = [
            'user_id' => $userId,
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $expires,
        ];

        $headerB64 = self::base64UrlEncode(json_encode(['alg' => self::$algorithm, 'typ' => 'JWT']));
        $payloadB64 = self::base64UrlEncode(json_encode($payload));

        $dataToSign = "$headerB64.$payloadB64";
        $signature = self::sign($dataToSign);
        $signatureB64 = self::base64UrlEncode($signature);

        return "$dataToSign.$signatureB64";
    }

    /**
     * Validate and decode JWT token
     * 
     * @param string $token JWT token to validate
     * @return array Decoded payload if valid
     * @throws Exception if token is invalid or expired
     */
    public static function validateToken(string $token): array
    {
        self::initialize();

        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify signature
        $dataToVerify = "$headerB64.$payloadB64";
        $expectedSignature = self::sign($dataToVerify);
        $expectedSignatureB64 = self::base64UrlEncode($expectedSignature);

        if (!hash_equals($signatureB64, $expectedSignatureB64)) {
            throw new Exception('Invalid token signature');
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);

        if (!$payload) {
            throw new Exception('Invalid token payload');
        }

        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken Refresh token to use
     * @param int $expiresIn New token lifetime
     * @return array|null Array with 'access_token' and 'expires_at' if successful, null if invalid
     */
    public static function refreshAccessToken(string $refreshToken, int $expiresIn = 3600): ?array
    {
        $payload = self::validateToken($refreshToken);

        if (!$payload || ($payload['type'] ?? null) !== 'refresh') {
            return null;
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $newAccessToken = self::generateToken($userId, $expiresIn);
        $expiresAt = time() + $expiresIn;

        return [
            'access_token' => $newAccessToken,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'expires_in' => $expiresIn,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Extract user ID from token (without full validation)
     * Useful for quick lookups
     * 
     * @param string $token JWT token
     * @return int|null User ID if token structure is valid
     */
    public static function getUserIdFromToken(string $token): ?int
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            return $payload['user_id'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Sign data with HMAC-SHA256
     */
    private static function sign(string $data): string
    {
        return hash_hmac('sha256', $data, self::$secret, true);
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($padded);
    }
}
