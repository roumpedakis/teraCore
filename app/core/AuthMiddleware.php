<?php

namespace App\Core;

/**
 * AuthMiddleware
 * Validates JWT tokens in Authorization header for protected routes
 * 
 * Usage in routing:
 *   - Apply to routes that require authentication
 *   - Will set $_REQUEST['auth_user_id'] if token is valid
 *   - Returns 401 Unauthorized if token is invalid/missing
 */
class AuthMiddleware
{
    /**
     * Check if request has valid authentication
     * 
     * @return array User info if authenticated, null if not
     */
    public static function authenticate(): ?array
    {
        $token = self::getTokenFromHeader();

        if (!$token) {
            return null;
        }

        try {
            $payload = JWT::validateToken($token);
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Require authentication - returns error if no valid token
     * 
     * @return array User payload if valid, or error array
     */
    public static function require(): array
    {
        $token = self::getTokenFromHeader();

        if (!$token) {
            return [
                'success' => false,
                'error' => 'Authorization required',
                'code' => 401,
            ];
        }

        try {
            $payload = JWT::validateToken($token);
            return [
                'success' => true,
                'payload' => $payload,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid or expired token',
                'code' => 401,
            ];
        }
    }

    /**
     * Get JWT token from Authorization header
     * Format: Authorization: Bearer {token}
     * 
     * @return string|null Token if found, null otherwise
     */
    private static function getTokenFromHeader(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Set response as unauthorized (401)
     */
    public static function unauthorized(): array
    {
        http_response_code(401);
        return [
            'success' => false,
            'error' => 'Unauthorized',
            'code' => 401,
        ];
    }

    /**
     * Set response as forbidden (403)
     */
    public static function forbidden(): array
    {
        http_response_code(403);
        return [
            'success' => false,
            'error' => 'Forbidden',
            'code' => 403,
        ];
    }
}
