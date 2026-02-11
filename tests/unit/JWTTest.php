<?php

use App\Core\JWT;
use App\Core\Config;

class JWTTest extends TestCase {
    
    public function test_generate_token_with_default_config() {
        $userId = 123;
        $token = JWT::generateToken($userId);
        
        assert_true(is_string($token));
        assert_contains($token, '.');
        
        // Token should have 3 parts: header.payload.signature
        $parts = explode('.', $token);
        assert_equal(3, count($parts));
    }

    public function test_validate_token() {
        $userId = 456;
        $token = JWT::generateToken($userId);
        
        $payload = JWT::validateToken($token);
        
        assert_true(is_array($payload));
        assert_array_key_exists('user_id', $payload);
        assert_equal($userId, $payload['user_id']);
    }

    public function test_token_contains_expiry() {
        $token = JWT::generateToken(789);
        $payload = JWT::validateToken($token);
        
        assert_array_key_exists('exp', $payload);
        assert_array_key_exists('iat', $payload);
        
        // Check that expiry is in the future
        assert_true($payload['exp'] > time());
    }

    public function test_token_ttl_from_config() {
        $token = JWT::generateToken(100);
        $payload = JWT::validateToken($token);
        
        $expectedTTL = (int)Config::get('JWT_EXPIRES_IN', 28800);
        $actualTTL = $payload['exp'] - $payload['iat'];
        
        // Allow 2 seconds tolerance for execution time
        assert_true(abs($actualTTL - $expectedTTL) <= 2, 
            "Expected TTL ~$expectedTTL, got $actualTTL");
    }

    public function test_custom_token_expiry() {
        $customTTL = 300; // 5 minutes
        $token = JWT::generateToken(200, $customTTL);
        $payload = JWT::validateToken($token);
        
        $actualTTL = $payload['exp'] - $payload['iat'];
        
        // Allow 2 seconds tolerance
        assert_true(abs($actualTTL - $customTTL) <= 2);
    }

    public function test_expired_token_fails() {
        // Generate token that expires immediately
        $token = JWT::generateToken(300, -1);
        
        try {
            JWT::validateToken($token);
            assert_true(false, 'Should have thrown exception for expired token');
        } catch (Exception $e) {
            assert_contains($e->getMessage(), 'expired');
        }
    }

    public function test_generate_refresh_token() {
        $userId = 400;
        $refreshToken = JWT::generateRefreshToken($userId);
        
        assert_true(is_string($refreshToken));
        
        $payload = JWT::validateToken($refreshToken);
        assert_equal($userId, $payload['user_id']);
        assert_array_key_exists('type', $payload);
        assert_equal('refresh', $payload['type']);
    }

    public function test_refresh_token_ttl_from_config() {
        $refreshToken = JWT::generateRefreshToken(500);
        $payload = JWT::validateToken($refreshToken);
        
        $expectedTTL = (int)Config::get('JWT_REFRESH_EXPIRES_IN', 604800);
        $actualTTL = $payload['exp'] - $payload['iat'];
        
        // Allow 2 seconds tolerance
        assert_true(abs($actualTTL - $expectedTTL) <= 2, 
            "Expected refresh TTL ~$expectedTTL, got $actualTTL");
    }

    public function test_invalid_token_format() {
        try {
            JWT::validateToken('invalid.token');
            assert_true(false, 'Should fail on invalid token');
        } catch (Exception $e) {
            assert_true(true);
        }
    }

    public function test_tampered_token_fails() {
        $token = JWT::generateToken(600);
        
        // Tamper with the token
        $parts = explode('.', $token);
        $parts[1] = base64_encode('{"user_id":999}'); // Change payload
        $tamperedToken = implode('.', $parts);
        
        try {
            JWT::validateToken($tamperedToken);
            assert_true(false, 'Should fail on tampered token');
        } catch (Exception $e) {
            assert_contains($e->getMessage(), 'Invalid');
        }
    }

    public function test_config_values_are_correct() {
        $accessTTL = (int)Config::get('JWT_EXPIRES_IN');
        $refreshTTL = (int)Config::get('JWT_REFRESH_EXPIRES_IN');
        
        // 8 hours = 28800 seconds
        assert_equal(28800, $accessTTL, 'Access token should be 8 hours');
        
        // 7 days = 604800 seconds  
        assert_equal(604800, $refreshTTL, 'Refresh token should be 7 days');
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new JWTTest();
$test->run();
