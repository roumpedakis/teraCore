<?php

namespace App\Core;

/**
 * ApiDocumentation - Generates API endpoint documentation
 */
class ApiDocumentation
{
    /**
     * Get all available API endpoints
     */
    public static function getEndpoints(): array
    {
        return [
            'version' => '1.0.0',
            'baseUrl' => self::getBaseUrl(),
            'description' => 'TeraCore API - Modular Content Management System',
            'endpoints' => [
                // Auth Endpoints
                [
                    'method' => 'POST',
                    'path' => '/api/auth/register',
                    'description' => 'Register new user account',
                    'params' => [
                        'username' => 'string (required)',
                        'email' => 'string (required, valid email)',
                        'password' => 'string (required, min 6 chars)'
                    ],
                    'auth' => false,
                    'example' => '{
                        "username": "john_doe",
                        "email": "john@example.com",
                        "password": "securepass123"
                    }'
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/auth/login',
                    'description' => 'Authenticate user and get JWT tokens',
                    'params' => [
                        'username_or_email' => 'string (required)',
                        'password' => 'string (required)'
                    ],
                    'auth' => false,
                    'example' => '{
                        "username_or_email": "john_doe",
                        "password": "securepass123"
                    }'
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/auth/refresh',
                    'description' => 'Refresh access token using refresh token',
                    'params' => [
                        'refresh_token' => 'string (required)'
                    ],
                    'auth' => false,
                    'example' => '{
                        "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
                    }'
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/auth/logout',
                    'description' => 'Logout user (invalidate refresh token)',
                    'params' => [
                        'user_id' => 'integer (required)'
                    ],
                    'auth' => true,
                    'example' => '{
                        "user_id": 1
                    }'
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/auth/verify',
                    'description' => 'Verify JWT token and get user info',
                    'params' => [
                        'Authorization' => 'header - Bearer {token} (required)'
                    ],
                    'auth' => true,
                    'example' => 'Header: Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...'
                ],

                // Articles Endpoints
                [
                    'method' => 'GET',
                    'path' => '/api/articles/article',
                    'description' => 'Get all articles with optional filters',
                    'params' => [
                        'status' => 'string (optional) - published|draft|archived',
                        'author_id' => 'integer (optional) - filter by author'
                    ],
                    'auth' => false,
                    'example' => '/api/articles/article?status=published&author_id=1'
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/articles/article/{id}',
                    'description' => 'Get single article by ID',
                    'params' => [
                        'id' => 'integer (required) - article ID'
                    ],
                    'auth' => false,
                    'example' => '/api/articles/article/1'
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/articles/article',
                    'description' => 'Create new article (requires auth)',
                    'params' => [
                        'title' => 'string (required)',
                        'content' => 'string (required)',
                        'status' => 'string (optional) - published|draft',
                        'category_id' => 'integer (optional)'
                    ],
                    'auth' => true,
                    'example' => '{
                        "title": "My Article",
                        "content": "Article content here...",
                        "status": "published"
                    }'
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/articles/article/{id}',
                    'description' => 'Update article (requires auth, own article or admin)',
                    'params' => [
                        'id' => 'integer (required) - article ID',
                        'title' => 'string (optional)',
                        'content' => 'string (optional)',
                        'status' => 'string (optional)'
                    ],
                    'auth' => true,
                    'example' => '{
                        "title": "Updated Title",
                        "status": "draft"
                    }'
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/api/articles/article/{id}',
                    'description' => 'Delete article (requires auth, own article or admin)',
                    'params' => [
                        'id' => 'integer (required) - article ID'
                    ],
                    'auth' => true,
                    'example' => '/api/articles/article/1'
                ],

                // Categories and Tags
                [
                    'method' => 'GET',
                    'path' => '/api/articles/category',
                    'description' => 'Get all article categories',
                    'params' => [],
                    'auth' => false,
                    'example' => '/api/articles/category'
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/articles/tag',
                    'description' => 'Get all tags',
                    'params' => [],
                    'auth' => false,
                    'example' => '/api/articles/tag'
                ],

                // Users Endpoints (Limited)
                [
                    'method' => 'GET',
                    'path' => '/api/users/user/{id}',
                    'description' => 'Get user info by ID (requires auth)',
                    'params' => [
                        'id' => 'integer (required) - user ID'
                    ],
                    'auth' => true,
                    'example' => '/api/users/user/1'
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/users/user/{id}',
                    'description' => 'Update own user info (requires auth)',
                    'params' => [
                        'id' => 'integer (required) - user ID',
                        'username' => 'string (optional)',
                        'email' => 'string (optional)',
                        'password' => 'string (optional, min 6 chars)'
                    ],
                    'auth' => true,
                    'example' => '{
                        "username": "new_username",
                        "email": "newemail@example.com"
                    }'
                ],
            ]
        ];
    }

    /**
     * Get API info for root endpoint
     */
    public static function getApiInfo(): array
    {
        $endpoints = self::getEndpoints();
        return [
            'success' => true,
            'version' => $endpoints['version'],
            'baseUrl' => $endpoints['baseUrl'],
            'description' => $endpoints['description'],
            'totalEndpoints' => count($endpoints['endpoints']),
            'docUrl' => self::getBaseUrl() . '/',
            'endpoints' => array_map(function ($ep) {
                return [
                    'method' => $ep['method'],
                    'path' => $ep['path'],
                    'description' => $ep['description'],
                    'requiresAuth' => $ep['auth']
                ];
            }, $endpoints['endpoints'])
        ];
    }

    /**
     * Get HTML documentation page
     */
    public static function getHtmlDocumentation(): string
    {
        $endpoints = self::getEndpoints();
        $baseUrl = $endpoints['baseUrl'];
        $totalEndpoints = count($endpoints['endpoints']);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeraCore API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 20px 40px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            padding: 10px 0;
        }
        
        .info-item strong {
            color: #667eea;
        }
        
        .content {
            padding: 40px;
        }
        
        .endpoints-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 1.8em;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .endpoint {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 5px;
        }
        
        .endpoint.auth {
            border-left-color: #ff6b6b;
        }
        
        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .method {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9em;
            color: white;
            min-width: 60px;
            text-align: center;
        }
        
        .method.GET {
            background: #4CAF50;
        }
        
        .method.POST {
            background: #2196F3;
        }
        
        .method.PUT {
            background: #FF9800;
        }
        
        .method.DELETE {
            background: #f44336;
        }
        
        .path {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            flex: 1;
            min-width: 300px;
            font-size: 0.95em;
            border: 1px solid #ddd;
        }
        
        .description {
            font-size: 1.1em;
            margin-bottom: 15px;
            color: #555;
        }
        
        .endpoint-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        
        .params, .example {
            background: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        
        .params h4, .example h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1em;
        }
        
        .params ul {
            list-style: none;
            padding: 0;
        }
        
        .params li {
            padding: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #666;
        }
        
        .example pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 0.85em;
            line-height: 1.4;
        }
        
        .auth-badge {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .no-auth-badge {
            display: inline-block;
            background: #51cf66;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        @media (max-width: 900px) {
            .endpoint-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .endpoint-details {
                grid-template-columns: 1fr;
            }
            
            .path {
                min-width: 100%;
            }
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px 40px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TeraCore API</h1>
            <p>Modular Content Management System API Documentation</p>
        </div>
        
        <div class="info-box">
            <div class="info">
                <div class="info-item">
                    <strong>Version:</strong> {$endpoints['version']}
                </div>
                <div class="info-item">
                    <strong>Base URL:</strong> <code>{$baseUrl}</code>
                </div>
                <div class="info-item">
                    <strong>Total Endpoints:</strong> {$totalEndpoints}
                </div>
            </div>
        </div>
        
        <div class="content">
HTML;
        
        // Group endpoints by category
        $categories = [];
        foreach ($endpoints['endpoints'] as $endpoint) {
            $category = explode('/', $endpoint['path'])[2] ?? 'other'; // Extract module from path
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $endpoint;
        }
        
        // Render each category
        foreach ($categories as $category => $categoryEndpoints) {
            $categoryLabel = ucfirst($category);
            $html .= "<div class='endpoints-section'>";
            $html .= "<h2 class='section-title'>{$categoryLabel} Endpoints</h2>";
            
            foreach ($categoryEndpoints as $endpoint) {
                $authClass = $endpoint['auth'] ? 'auth' : '';
                $authBadge = $endpoint['auth'] 
                    ? '<span class="auth-badge">🔒 Auth Required</span>' 
                    : '<span class="no-auth-badge">🔓 Public</span>';
                
                $params = '';
                if (!empty($endpoint['params'])) {
                    $params .= '<div class="params"><h4>Parameters</h4><ul>';
                    foreach ($endpoint['params'] as $paramName => $paramDesc) {
                        $params .= "<li><strong>$paramName:</strong> $paramDesc</li>";
                    }
                    $params .= '</ul></div>';
                }
                
                $example = '<div class="example"><h4>Example</h4><pre>' . htmlspecialchars($endpoint['example']) . '</pre></div>';
                
                $html .= <<<ENDPOINT
            <div class="endpoint {$authClass}">
                <div class="endpoint-header">
                    <span class="method {$endpoint['method']}">{$endpoint['method']}</span>
                    <span class="path">{$endpoint['path']}</span>
                    {$authBadge}
                </div>
                <div class="description">{$endpoint['description']}</div>
                <div class="endpoint-details">
                    {$params}
                    {$example}
                </div>
            </div>
ENDPOINT;
            }
            
            $html .= '</div>';
        }
        
        $html .= <<<HTML
        </div>
        
        <div class="footer">
            <p>TeraCore API - Modular Content Management System | Last Updated: {date('F d, Y H:i:s')}</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }

    /**
     * Get base URL for API
     */
    private static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host";
    }
}
