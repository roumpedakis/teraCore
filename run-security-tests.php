#!/usr/bin/env php
<?php

/**
 * TeraCore OWASP Security Tests
 * 
 * Tests all OWASP API Security Top 10 implementations
 */

class SecurityTest
{
    private string $baseUrl = 'http://localhost:8000';
    private int $passedTests = 0;
    private int $totalTests = 0;
    
    public function __construct()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════\n";
        echo "║   TeraCore OWASP Security Tests\n";
        echo "╚═══════════════════════════════════════\n\n";
        
        // Check if server is running
        $this->checkServer();
    }
    
    private function checkServer(): void
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "❌ Server is not running at {$this->baseUrl}\n";
            echo "Please start the server with: php -S localhost:8000 -t public\n\n";
            exit(1);
        }
        
        echo "✓ Server is running at {$this->baseUrl}\n\n";
    }
    
    private function request(string $path, string $method = 'GET', array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $path;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        if (!empty($data)) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        return [
            'code' => $httpCode,
            'headers' => $responseHeaders,
            'body' => $responseBody,
            'json' => json_decode($responseBody, true)
        ];
    }
    
    private function assertTrue(bool $condition, string $message): void
    {
        $this->totalTests++;
        if ($condition) {
            $this->passedTests++;
            echo "  ✓ $message\n";
        } else {
            echo "  ❌ $message\n";
        }
    }
    
    public function testSecurityHeaders(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: Security Headers (OWASP API8:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        $response = $this->request('/api');
        $headers = $response['headers'];
        
        $this->assertTrue(
            strpos($headers, 'X-Frame-Options: DENY') !== false,
            'X-Frame-Options header is set'
        );
        
        $this->assertTrue(
            strpos($headers, 'X-Content-Type-Options: nosniff') !== false,
            'X-Content-Type-Options header is set'
        );
        
        $this->assertTrue(
            strpos($headers, 'X-XSS-Protection') !== false,
            'X-XSS-Protection header is set'
        );
        
        $this->assertTrue(
            strpos($headers, 'Content-Security-Policy') !== false,
            'Content-Security-Policy header is set'
        );
        
        $this->assertTrue(
            strpos($headers, 'Referrer-Policy') !== false,
            'Referrer-Policy header is set'
        );
        
        $this->assertTrue(
            strpos($headers, 'Permissions-Policy') !== false,
            'Permissions-Policy header is set'
        );
        
        echo "\n";
    }
    
    public function testRateLimitHeaders(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: Rate Limit Headers (OWASP API4:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        $response = $this->request('/api');
        $headers = $response['headers'];
        
        $this->assertTrue(
            strpos($headers, 'X-RateLimit-Limit:') !== false,
            'X-RateLimit-Limit header is present'
        );
        
        $this->assertTrue(
            strpos($headers, 'X-RateLimit-Remaining:') !== false,
            'X-RateLimit-Remaining header is present'
        );
        
        $this->assertTrue(
            strpos($headers, 'X-RateLimit-Reset:') !== false,
            'X-RateLimit-Reset header is present'
        );
        
        echo "\n";
    }
    
    public function testSensitiveDataFiltering(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: Sensitive Data Filtering (OWASP API3:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        // Login first
        $loginResponse = $this->request('/api/auth/login', 'POST', [
            'username_or_email' => 'postman_test',
            'password' => 'PostmanTest123'
        ]);
        
        if ($loginResponse['code'] !== 200 || !isset($loginResponse['json']['tokens']['access_token'])) {
            echo "  ⊗ Skipped - Could not login\n\n";
            return;
        }
        
        $token = $loginResponse['json']['tokens']['access_token'];
        $userId = $loginResponse['json']['data']['user_id'];
        
        // Get user profile
        $userResponse = $this->request("/api/users/user/{$userId}", 'GET', [], [
            "Authorization: Bearer {$token}"
        ]);
        
        $userData = $userResponse['json'];
        
        $this->assertTrue(
            !isset($userData['password']),
            'Password field is not exposed'
        );
        
        $this->assertTrue(
            !isset($userData['password_hash']),
            'Password hash field is not exposed'
        );
        
        $this->assertTrue(
            !isset($userData['refresh_token']),
            'Refresh token field is not exposed'
        );
        
        $this->assertTrue(
            !isset($userData['token_expires_at']),
            'Token expiry field is not exposed'
        );
        
        $this->assertTrue(
            !isset($userData['oauth2_provider']),
            'OAuth2 provider field is not exposed'
        );
        
        echo "\n";
    }
    
    public function testXSSPrevention(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: XSS Pattern Detection (OWASP API7:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        $response = $this->request('/api/articles/<script>alert(1)</script>');
        
        $this->assertTrue(
            $response['code'] === 400,
            'XSS pattern in URL is detected (400 Bad Request)'
        );
        
        $this->assertTrue(
            isset($response['json']['error']) && $response['json']['error'] === 'Invalid request',
            'XSS detection returns proper error message'
        );
        
        echo "\n";
    }
    
    public function testAccessControl(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: Access Control (OWASP API1:2023, API5:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        // Test admin blocked (correct path according to routing)
        $adminResponse = $this->request('/api/core/admin');
        $this->assertTrue(
            $adminResponse['code'] === 403,
            'Admin endpoints are blocked (403 Forbidden)'
        );
        
        // Test user POST blocked
        $userPostResponse = $this->request('/api/users/user', 'POST', [
            'username' => 'test',
            'email' => 'test@test.com',
            'password' => 'test123'
        ]);
        $this->assertTrue(
            $userPostResponse['code'] === 403,
            'User creation via API is blocked (403 Forbidden)'
        );
        
        // Test user DELETE blocked
        $userDeleteResponse = $this->request('/api/users/user/999', 'DELETE');
        $this->assertTrue(
            $userDeleteResponse['code'] === 403,
            'User deletion via API is blocked (403 Forbidden)'
        );
        
        echo "\n";
    }
    
    public function testAuthenticationRequired(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: Authentication (OWASP API2:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        // Test invalid token
        $response = $this->request('/api/auth/verify', 'GET', [], [
            'Authorization: Bearer invalid_token_here'
        ]);
        
        $this->assertTrue(
            $response['code'] === 401,
            'Invalid token is rejected (401 Unauthorized)'
        );
        
        // Test missing token
        $response2 = $this->request('/api/auth/verify', 'GET');
        
        $this->assertTrue(
            $response2['code'] === 401,
            'Missing token is rejected (401 Unauthorized)'
        );
        
        echo "\n";
    }
    
    public function testCORSHeaders(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: CORS Configuration (OWASP API8:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        $response = $this->request('/api');
        $headers = $response['headers'];
        
        $this->assertTrue(
            strpos($headers, 'Access-Control-Allow-Origin') !== false,
            'CORS Allow-Origin header is present'
        );
        
        $this->assertTrue(
            strpos($headers, 'Access-Control-Allow-Methods') !== false,
            'CORS Allow-Methods header is present'
        );
        
        $this->assertTrue(
            strpos($headers, 'Access-Control-Allow-Headers') !== false,
            'CORS Allow-Headers header is present'
        );
        
        echo "\n";
    }
    
    public function testAPIDocumentation(): void
    {
        echo "────────────────────────────────────────\n";
        echo "TEST: API Documentation (OWASP API9:2023)\n";
        echo "────────────────────────────────────────\n\n";
        
        // Test HTML documentation
        $htmlResponse = $this->request('/');
        $this->assertTrue(
            $htmlResponse['code'] === 200,
            'HTML documentation is accessible'
        );
        
        // Test JSON API info
        $jsonResponse = $this->request('/api');
        $this->assertTrue(
            $jsonResponse['code'] === 200 && isset($jsonResponse['json']['version']),
            'JSON API documentation is accessible'
        );
        
        echo "\n";
    }
    
    public function runAll(): void
    {
        echo "RUNNING OWASP SECURITY TESTS\n";
        echo "════════════════════════════════════════\n\n";
        
        $this->testSecurityHeaders();
        $this->testRateLimitHeaders();
        $this->testSensitiveDataFiltering();
        $this->testXSSPrevention();
        $this->testAccessControl();
        $this->testAuthenticationRequired();
        $this->testCORSHeaders();
        $this->testAPIDocumentation();
        
        echo "════════════════════════════════════════\n";
        if ($this->passedTests === $this->totalTests) {
            echo "✅ ALL SECURITY TESTS PASSED ({$this->passedTests}/{$this->totalTests})\n";
        } else {
            echo "⚠️  SOME TESTS FAILED ({$this->passedTests}/{$this->totalTests})\n";
        }
        echo "════════════════════════════════════════\n\n";
    }
}

// Run tests
$test = new SecurityTest();
$test->runAll();
