<?php

use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\ModuleLoader;
use App\Core\Factory;

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
    
    // Simple routing example (to be expanded)
    $path = $request->path();
    $method = $request->method();
    
    // Route format: /module/entity/action or /module/entity/:id
    $segments = array_filter(explode('/', $path));
    
    if (count($segments) < 2) {
        $response->status(404)->json(['error' => 'Not found']);
    }
    
    $moduleName = $segments[0] ?? '';
    $entityName = $segments[1] ?? '';
    $action = $segments[2] ?? 'index';
    $id = $segments[3] ?? null;
    
    // Check if module and entity exist
    $module = ModuleLoader::getModule($moduleName);
    if (!$module) {
        $response->status(404)->json(['error' => 'Module not found']);
    }
    
    $entity = ModuleLoader::getEntity($moduleName, $entityName);
    if (!$entity) {
        $response->status(404)->json(['error' => 'Entity not found']);
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
