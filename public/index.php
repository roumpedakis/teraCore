<?php

use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\ModuleLoader;
use App\Core\Factory;
use App\Core\AuthController;
use App\Core\AuthMiddleware;
use App\Core\ApiDocumentation;

// Load autoloader
require_once __DIR__ . '/../app/Autoloader.php';

// Initialize framework
try {
    // Load environment config
    Config::load();
    
    // Initialize logger
    Logger::init();
    
    // Load all modules
    ModuleLoader::load();
    
    Logger::info("Application started", [
        'app' => Config::get('APP_NAME'),
        'env' => Config::get('APP_ENV'),
    ]);
    
    // Create request and response objects
    $request = new Request();
    $response = new Response();
    
    // Route format: /api/module/entity/action or /api/module/entity/:id
    $path = $request->path();
    $method = $request->method();
    
    // Debug logging
    Logger::debug("Routing request", [
        'path' => $path,
        'method' => $method
    ]);
    
    // Handle root path - serve HTML documentation
    if ($path === '/' || $path === '') {
        header('Content-Type: text/html; charset=utf-8');
        echo ApiDocumentation::getHtmlDocumentation();
        exit;
    }
    
    // Handle /api root endpoint - JSON API info
    if ($path === '/api' || $path === '/api/') {
        $response->json(ApiDocumentation::getApiInfo());
        exit;
    }
    
    // Remove /api prefix if present and parse segments
    $isApiPath = strpos($path, '/api/') === 0 || strpos($path, '/api') === 0;
    $pathToParse = $isApiPath ? substr($path, 4) : $path; // Remove '/api' prefix
    
    $segments = array_filter(explode('/', $pathToParse));
    $segments = array_values($segments); // Re-index after filter
    
    // Handle auth endpoints (/api/auth/register, /api/auth/login, etc.)
    if (count($segments) >= 1 && strtolower($segments[0]) === 'auth') {
        $authAction = strtolower($segments[1] ?? '');
        $authController = new AuthController(Database::getInstance());
        
        // Route to appropriate auth action
        switch ($authAction) {
            case 'register':
                if ($method !== 'POST') {
                    $response->status(405)->json(['error' => 'Method not allowed']);
                    exit;
                }
                $result = $authController->register($request->all());
                break;
                
            case 'login':
                if ($method !== 'POST') {
                    $response->status(405)->json(['error' => 'Method not allowed']);
                    exit;
                }
                $result = $authController->login($request->all());
                break;
                
            case 'refresh':
                if ($method !== 'POST') {
                    $response->status(405)->json(['error' => 'Method not allowed']);
                    exit;
                }
                $result = $authController->refresh($request->all());
                break;
                
            case 'logout':
                if ($method !== 'POST') {
                    $response->status(405)->json(['error' => 'Method not allowed']);
                    exit;
                }
                $result = $authController->logout($request->all());
                break;
                
            case 'verify':
                if ($method !== 'GET') {
                    $response->status(405)->json(['error' => 'Method not allowed']);
                    exit;
                }
                // Extract token from Authorization header
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                $token = null;
                if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                    $token = $matches[1];
                }
                if (!$token) {
                    $response->status(401)->json(['success' => false, 'error' => 'No token provided']);
                    exit;
                }
                $result = $authController->verify($token);
                break;
                
            default:
                $response->status(404)->json(['error' => 'Auth endpoint not found']);
                exit;
        }
        
        $response->json($result);
        exit;
    }
    
    if (count($segments) < 2) {
        Logger::warning("Not enough segments in path", ['segments' => $segments]);
        $response->status(404)->json(['error' => 'Not found']);
        exit;
    }
    
    $moduleName = strtolower($segments[0] ?? '');
    $entityName = ucfirst($segments[1] ?? '');
    $action = $segments[2] ?? 'index';
    $id = $segments[3] ?? null;
    
    Logger::debug("Path segments parsed", [
        'moduleName' => $moduleName,
        'entityName' => $entityName,
        'action' => $action,
        'id' => $id
    ]);
    
    // ============================================
    // API ACCESS CONTROL LAYER
    // ============================================
    
    // Admin entity - NO API access at all
    if (strtolower($moduleName) === 'core' && strtolower($entityName) === 'admin') {
        $response->status(403)->json([
            'success' => false,
            'error' => 'Admin entity is not accessible via API'
        ]);
        exit;
    }
    
    // Users entity - Limited endpoints
    if (strtolower($moduleName) === 'users' && strtolower($entityName) === 'user') {
        // Only allow: GET (read) and PUT (update own info)
        // Block: POST (create) and DELETE (delete)
        $allowed_methods = ['GET'];
        
        if ($method === 'POST' || $method === 'DELETE') {
            $response->status(403)->json([
                'success' => false,
                'error' => "Method {$method} not allowed for User entity via API. Only GET and PUT are supported."
            ]);
            exit;
        }
        
        // Only GET and PUT allowed for Users
        if (!in_array($method, ['GET', 'PUT'])) {
            $response->status(405)->json([
                'success' => false,
                'error' => "Method not allowed"
            ]);
            exit;
        }
    }
    
    // Articles - Full CRUD allowed (GET, POST, PUT, DELETE)
    // Categories and Tags under articles - Full access
    
    // Check if module and entity exist
    $module = ModuleLoader::getModule($moduleName);
    if (!$module) {
        Logger::warning("Module not found", ['moduleName' => $moduleName]);
        $response->status(404)->json(['error' => 'Module not found']);
        exit;
    }
    
    $entity = ModuleLoader::getEntity($moduleName, $entityName);
    if (!$entity) {
        $response->status(404)->json(['error' => 'Entity not found']);
        exit;
    }
    
    // Create controller
    $controller = Factory::createController($moduleName, $entityName);
    
    // Determine action and call
    $actionMap = [
        'GET' => $id ? 'read' : 'readAll',
        'POST' => 'create',
        'PUT' => 'update',
        'DELETE' => 'delete',
    ];
    
    $actionName = $actionMap[$method] ?? null;
    
    if (!$actionName || !method_exists($controller, $actionName)) {
        $response->status(405)->json(['error' => 'Method not allowed']);
        exit;
    }
    
    // Execute action
    if ($id) {
        $result = $controller->$actionName($id, $request->all());
    } else {
        $result = $controller->$actionName($request->all());
    }
    
    $response->json($result);
    
} catch (\Exception $e) {
    Logger::error("Application error", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => Config::get('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
    ]);
}
