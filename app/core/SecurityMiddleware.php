<?php

namespace App\Core;

/**
 * SecurityMiddleware - OWASP API Security Best Practices
 * 
 * Implements:
 * - OWASP API Security Top 10
 * - Rate Limiting
 * - CORS Headers
 * - Security Headers
 * - Input Validation
 * - XSS Prevention
 * - SQL Injection Prevention (via parameterized queries)
 */
class SecurityMiddleware
{
    private static array $rateLimitStore = [];
    private static array $bannedIPs = [];
    
    /**
     * Initialize security middleware
     */
    public static function init(): void
    {
        // Set security headers
        self::setSecurityHeaders();
        
        // Handle CORS
        self::handleCORS();
        
        // Check if IP is banned
        self::checkBannedIP();
        
        // Rate limiting
        self::checkRateLimit();
        
        // Validate request
        self::validateRequest();
    }
    
    /**
     * Set security headers (OWASP recommended)
     */
    private static function setSecurityHeaders(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy (formerly Feature Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Remove server info
        header_remove('X-Powered-By');
        
        // HSTS (HTTP Strict Transport Security) - only for HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Handle CORS (Cross-Origin Resource Sharing)
     */
    private static function handleCORS(): void
    {
        $allowedOrigins = Config::get('CORS_ALLOWED_ORIGINS', '*');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // For development, allow all. For production, restrict to specific origins
        if ($allowedOrigins === '*' || in_array($origin, explode(',', $allowedOrigins))) {
            header('Access-Control-Allow-Origin: ' . ($allowedOrigins === '*' ? '*' : $origin));
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400'); // 24 hours
        }
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Check if IP is banned
     */
    private static function checkBannedIP(): void
    {
        $ip = self::getClientIP();
        
        if (in_array($ip, self::$bannedIPs)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }
    
    /**
     * Rate limiting (OWASP API4:2023 - Unrestricted Resource Consumption)
     */
    private static function checkRateLimit(): void
    {
        $ip = self::getClientIP();
        $now = time();
        
        // Clean old entries (older than 1 minute)
        self::$rateLimitStore = array_filter(self::$rateLimitStore, function($data) use ($now) {
            return ($now - $data['time']) < 60;
        });
        
        // Get rate limit config
        $maxRequests = (int) Config::get('RATE_LIMIT_MAX_REQUESTS', 100); // 100 requests
        $timeWindow = (int) Config::get('RATE_LIMIT_TIME_WINDOW', 60);   // per 60 seconds
        
        // Count requests from this IP in time window
        if (!isset(self::$rateLimitStore[$ip])) {
            self::$rateLimitStore[$ip] = ['count' => 0, 'time' => $now];
        }
        
        $ipData = self::$rateLimitStore[$ip];
        
        // Reset if time window expired
        if (($now - $ipData['time']) >= $timeWindow) {
            self::$rateLimitStore[$ip] = ['count' => 1, 'time' => $now];
            return;
        }
        
        // Increment counter
        self::$rateLimitStore[$ip]['count']++;
        
        // Check if limit exceeded
        if (self::$rateLimitStore[$ip]['count'] > $maxRequests) {
            Logger::warning("Rate limit exceeded", [
                'ip' => $ip,
                'requests' => self::$rateLimitStore[$ip]['count'],
                'limit' => $maxRequests
            ]);
            
            http_response_code(429); // Too Many Requests
            header('Content-Type: application/json');
            header('Retry-After: ' . $timeWindow);
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => "Too many requests. Please try again in {$timeWindow} seconds.",
                'limit' => $maxRequests,
                'window' => $timeWindow
            ]);
            exit;
        }
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . ($maxRequests - self::$rateLimitStore[$ip]['count']));
        header('X-RateLimit-Reset: ' . ($ipData['time'] + $timeWindow));
    }
    
    /**
     * Validate request (OWASP API3:2023 - Broken Object Property Level Authorization)
     */
    private static function validateRequest(): void
    {
        // Check request size (prevent DOS)
        $maxSize = (int) Config::get('MAX_REQUEST_SIZE', 10485760); // 10MB default
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($contentLength > $maxSize) {
            http_response_code(413); // Payload Too Large
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Request too large',
                'maxSize' => $maxSize,
                'received' => $contentLength
            ]);
            exit;
        }
        
        // Validate HTTP method
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        // Check for suspicious patterns in URL
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $suspiciousPatterns = [
            '/\.\./i',           // Directory traversal
            '/union.*select/i',  // SQL injection
            '/<script/i',        // XSS
            '/eval\(/i',         // Code injection
            '/system\(/i',       // Command injection
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                Logger::warning("Suspicious request detected", [
                    'ip' => self::getClientIP(),
                    'uri' => $uri,
                    'pattern' => $pattern
                ]);
                
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid request']);
                exit;
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP(): string
    {
        // Check for proxy headers (be careful with these in production)
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Sanitize input (OWASP API8:2023 - Security Misconfiguration)
     */
    public static function sanitizeInput(mixed $input): mixed
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        if (is_string($input)) {
            // Remove null bytes
            $input = str_replace("\0", '', $input);
            
            // Strip HTML tags (unless specifically needed)
            $input = strip_tags($input);
            
            // Trim whitespace
            $input = trim($input);
        }
        
        return $input;
    }
    
    /**
     * Validate JWT token for protected routes
     */
    public static function requireAuth(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required',
                'message' => 'Please provide a valid JWT token in Authorization header'
            ]);
            exit;
        }
        
        $token = $matches[1];
        
        try {
            $payload = JWT::validateToken($token);
            
            if (!$payload) {
                throw new \Exception('Invalid token');
            }
            
            return $payload;
        } catch (\Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid token',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        Logger::warning("SECURITY: $event", array_merge([
            'ip' => self::getClientIP(),
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ], $context));
    }
}
