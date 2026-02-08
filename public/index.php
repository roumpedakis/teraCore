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
    
    // Debug logging
    Logger::debug("Routing request", [
        'path' => $path,
        'method' => $method
    ]);
    
    // Route format: /module/entity/action or /module/entity/:id
    $segments = array_filter(explode('/', $path));
    $segments = array_values($segments); // Re-index after filter
    
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
