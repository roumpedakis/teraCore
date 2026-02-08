<?php

namespace App\Core;

use App\Modules\Users\User\Repository as UserRepository;
use App\Modules\Users\User\Model as UserModel;

/**
 * AuthController
 * Handles OAuth2 authentication endpoints:
 * - register (create new user)
 * - login (authenticate and return JWT)
 * - refresh (exchange refresh token for new access token)
 * - logout (invalidate tokens)
 * - verify (validate token and return user info)
 */
class AuthController
{
    private UserRepository $userRepo;
    private Database $db;

    public function __construct(?Database $db = null, ?UserRepository $userRepo = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
    }

    /**
     * Register new user
     * 
     * POST /auth/register
     * Body: { username, email, password, first_name?, last_name? }
     */
    public function register(array $data): array
    {
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'error' => 'Username, email, and password are required'];
        }

        // Check username uniqueness
        $existing = $this->userRepo->where('username', '=', $data['username'])->first();
        if ($existing) {
            return ['success' => false, 'error' => 'Username already exists'];
        }

        // Check email uniqueness
        $existing = $this->userRepo->where('email', '=', $data['email'])->first();
        if ($existing) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Validate password strength
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        try {
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            // Create user
            $this->userRepo->insert([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'is_active' => 1,
            ]);

            $userId = (int)$this->db->lastInsertId();
            $user = $this->userRepo->findById($userId);

            if (!$user) {
                return ['success' => false, 'error' => 'User created but could not be retrieved'];
            }

            return [
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login user and return JWT tokens
     * 
     * POST /auth/login
     * Body: { username_or_email, password }
     */
    public function login(array $data): array
    {
        // Validate input
        if (empty($data['username_or_email']) || empty($data['password'])) {
            return ['success' => false, 'error' => 'Username/email and password required'];
        }

        $credential = $data['username_or_email'];

        // Find user by username or email
        $user = $this->userRepo->where('username', '=', $credential)->first();
        if (!$user) {
            $user = $this->userRepo->where('email', '=', $credential)->first();
        }

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Account is inactive'];
        }

        // Verify password
        if (!password_verify($data['password'], $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Generate tokens
        $accessToken = JWT::generateToken($user['id'], 3600); // 1 hour
        $refreshToken = JWT::generateRefreshToken($user['id'], 2592000); // 30 days

        // Store refresh token in database
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);
        $this->userRepo->update((int)$user['id'], [
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresAt,
        ]);

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
            ],
            'tokens' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]
        ];
    }

    /**
     * Refresh access token using refresh token
     * 
     * POST /auth/refresh
     * Body: { refresh_token }
     */
    public function refresh(array $data): array
    {
        if (empty($data['refresh_token'])) {
            return ['success' => false, 'error' => 'Refresh token required'];
        }

        $refreshResult = JWT::refreshAccessToken($data['refresh_token']);

        if (!$refreshResult) {
            return ['success' => false, 'error' => 'Invalid or expired refresh token'];
        }

        // Get user to verify token matches DB
        $userId = JWT::getUserIdFromToken($data['refresh_token']);
        if ($userId) {
            $user = $this->userRepo->findById($userId);
            if ($user && $user['refresh_token'] === $data['refresh_token']) {
                // Token matches DB, update expiry time
                $expiresAt = $refreshResult['expires_at'];
                $this->userRepo->update($userId, ['token_expires_at' => $expiresAt]);
            } else {
                // Token doesn't match DB (revoked?)
                return ['success' => false, 'error' => 'Refresh token revoked'];
            }
        }

        return [
            'success' => true,
            'message' => 'Token refreshed',
            'tokens' => $refreshResult
        ];
    }

    /**
     * Logout user (invalidate refresh token)
     * 
     * POST /auth/logout
     * Body: { user_id } or Authorization header with token
     */
    public function logout(array $data): array
    {
        $userId = $data['user_id'] ?? null;

        // Try to get user_id from auth header/token
        if (!$userId) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                $userId = JWT::getUserIdFromToken($matches[1]);
            }
        }

        if (!$userId) {
            return ['success' => false, 'error' => 'User ID required or valid token'];
        }

        // Clear refresh token from database
        $this->userRepo->update((int)$userId, [
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);

        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }

    /**
     * Verify JWT token and return user info
     * 
     * GET /auth/verify
     * Headers: Authorization: Bearer {token}
     */
    public function verify(string $token): array
    {
        $payload = JWT::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'error' => 'Invalid or expired token'];
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            return ['success' => false, 'error' => 'Invalid token payload'];
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        return [
            'success' => true,
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'is_active' => $user['is_active'],
            ],
            'token_info' => [
                'issued_at' => date('Y-m-d H:i:s', $payload['iat']),
                'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
            ]
        ];
    }
}
