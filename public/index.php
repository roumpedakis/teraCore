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
use App\Core\SecurityMiddleware;
use App\Core\Handlers\SessionHandler;
use App\Modules\Users\User\Repository as UserRepository;
use App\Modules\Core\Admin\Repository as AdminRepository;
use App\Core\Libraries\Sanitizer;

// Load autoloader
require_once __DIR__ . '/../app/Autoloader.php';

// Initialize framework
try {
    // Load environment config
    Config::load();
    
    // Initialize logger
    Logger::init();
    
    // Initialize security middleware (OWASP best practices)
    SecurityMiddleware::init();
    
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

    // Handle favicon
    if ($path === '/favicon.ico') {
        http_response_code(204);
        exit;
    }

    // Handle admin UI routes
    if (strpos($path, '/admin') === 0) {
        SessionHandler::init();

        $adminPath = substr($path, 6);
        $adminPath = $adminPath === '' || $adminPath === '/' ? '/login' : $adminPath;

        $assetPath = __DIR__ . '/admin' . $adminPath;
        if (is_file($assetPath)) {
            $ext = pathinfo($assetPath, PATHINFO_EXTENSION);
            $mimeMap = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'html' => 'text/html; charset=utf-8'
            ];
            $mime = $mimeMap[$ext] ?? 'text/plain';
            header("Content-Type: {$mime}");
            readfile($assetPath);
            exit;
        }

        if ($adminPath === '/logout') {
            SessionHandler::destroy();
            header('Location: /admin/login');
            exit;
        }

        $isLoggedIn = SessionHandler::has('admin_user');

        if ($adminPath === '/login' && $method === 'POST') {
            $input = $request->all();
            $username = Sanitizer::sanitizeString($input['username'] ?? '');
            $password = $input['password'] ?? '';

            $loginError = '';
            if (empty($username) || empty($password)) {
                $loginError = 'Συμπλήρωσε όνομα χρήστη και κωδικό.';
            } else {
                $db = Database::getInstance();
                $adminRepo = new AdminRepository($db);

                $admin = $adminRepo->findByName($username);
                if (!$admin || !password_verify($password, $admin['password'] ?? '')) {
                    $loginError = 'Λάθος στοιχεία.';
                } else {
                    if (($admin['status'] ?? '') !== 'active') {
                        $loginError = 'Δεν έχεις δικαίωμα admin.';
                    } else {
                        SessionHandler::regenerate();
                        SessionHandler::set('admin_user', [
                            'admin_id' => $admin['id'],
                            'username' => $admin['name']
                        ]);
                        header('Location: /admin/dashboard');
                        exit;
                    }
                }
            }

            $loginError = $loginError ?: 'Λάθος στοιχεία.';
            $adminUser = SessionHandler::get('admin_user');
            include __DIR__ . '/admin/login.php';
            exit;
        }

        if ($adminPath === '/login' && $isLoggedIn) {
            header('Location: /admin/dashboard');
            exit;
        }

        if ($adminPath === '/login' && $method === 'GET') {
            $loginError = '';
            $adminUser = SessionHandler::get('admin_user');
            include __DIR__ . '/admin/login.php';
            exit;
        }

        if (!$isLoggedIn) {
            header('Location: /admin/login');
            exit;
        }

        $db = Database::getInstance();
        $userRepo = new UserRepository($db);
        $adminRepo = new AdminRepository($db);

        if ($adminPath === '/users' && $method === 'POST') {
            $input = $request->all();
            $action = $input['action'] ?? '';
            $userId = (int)($input['user_id'] ?? 0);
            $username = Sanitizer::sanitizeString($input['username'] ?? '');
            $email = Sanitizer::sanitizeEmail($input['email'] ?? '');
            $firstName = Sanitizer::sanitizeString($input['first_name'] ?? '');
            $lastName = Sanitizer::sanitizeString($input['last_name'] ?? '');
            $password = $input['password'] ?? '';
            $isActive = isset($input['is_active']) ? 1 : 0;

            if ($userId > 0) {
                if ($action === 'revoke') {
                    $userRepo->update($userId, [
                        'refresh_token' => null,
                        'token_expires_at' => null
                    ]);
                }

                if ($action === 'toggle') {
                    $current = $userRepo->findById($userId);
                    if ($current) {
                        $userRepo->update($userId, [
                            'is_active' => (int)!((int)$current['is_active'])
                        ]);
                    }
                }

                if ($action === 'delete') {
                    $userRepo->delete($userId);
                }
            }

            if ($action === 'create') {
                if (!empty($username) && Sanitizer::validateEmail($email) && !empty($password)) {
                    $userRepo->insert([
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_BCRYPT),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'is_active' => $isActive
                    ]);
                }
            }

            if ($action === 'update' && $userId > 0) {
                $payload = [
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'is_active' => $isActive
                ];
                if (!empty($password)) {
                    $payload['password'] = password_hash($password, PASSWORD_BCRYPT);
                }
                $userRepo->update($userId, $payload);
            }

            header('Location: /admin/users');
            exit;
        }

        if ($adminPath === '/dashboard' && $method === 'POST') {
            $input = $request->all();
            $action = $input['action'] ?? '';
            $adminId = (int)($input['admin_id'] ?? 0);
            $adminName = Sanitizer::sanitizeString($input['admin_name'] ?? '');
            $adminStatus = $input['status'] ?? 'active';
            $adminDescription = Sanitizer::sanitizeString($input['description'] ?? '');
            $adminPassword = $input['password'] ?? '';

            if ($action === 'create' && !empty($adminName)) {
                $payload = [
                    'name' => $adminName,
                    'description' => $adminDescription,
                    'status' => $adminStatus
                ];
                if (!empty($adminPassword)) {
                    $payload['password'] = password_hash($adminPassword, PASSWORD_BCRYPT);
                }
                $adminRepo->insert($payload);
            }

            if ($action === 'update' && $adminId > 0) {
                $payload = [
                    'description' => $adminDescription,
                    'status' => $adminStatus
                ];
                if (!empty($adminPassword)) {
                    $payload['password'] = password_hash($adminPassword, PASSWORD_BCRYPT);
                }
                $adminRepo->update($adminId, $payload);
            }

            if ($action === 'delete' && $adminId > 0) {
                $existing = $adminRepo->findById($adminId);
                $name = $existing['name'] ?? '';
                if (strtolower($name) !== 'power admin') {
                    $adminRepo->delete($adminId);
                }
            }

            header('Location: /admin/dashboard');
            exit;
        }

        $activeUsers = (new UserRepository($db))->where('is_active', '=', 1)->count();
        $activeAdmins = (new AdminRepository($db))->where('status', '=', 'active')->count();
        $revokedTokens = (int)$db->fetchAll("SELECT COUNT(*) AS count FROM users WHERE refresh_token IS NULL")[0]['count'];

        $users = (new UserRepository($db))->orderBy('id', 'DESC')->limit(50)->get();
        foreach ($users as &$user) {
            unset($user['password'], $user['refresh_token']);
        }
        unset($user);

        $admins = (new AdminRepository($db))->orderBy('id', 'DESC')->get();
        $adminUser = SessionHandler::get('admin_user');
        $loginError = '';

        $routeMap = [
            '/login' => '/admin/login.php',
            '/dashboard' => '/admin/dashboard.php',
            '/users' => '/admin/users.php'
        ];

        $target = $routeMap[$adminPath] ?? '/admin/login.php';
        $targetPath = __DIR__ . $target;
        if (is_file($targetPath)) {
            header('Content-Type: text/html; charset=utf-8');
            include $targetPath;
            exit;
        }

        $response->status(404)->json(['error' => 'Admin page not found']);
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
    
    // Handle module management endpoints
    if (count($segments) >= 1 && strtolower($segments[0]) === 'modules') {
        $moduleController = new \App\Core\ModuleController();
        
        // GET /api/modules - List all modules
        if ($method === 'GET' && count($segments) === 1) {
            $result = $moduleController->list();
            $response->json($result);
            exit;
        }
        
        // GET /api/modules/pricing - Get pricing info
        if ($method === 'GET' && count($segments) === 2 && $segments[1] === 'pricing') {
            $result = $moduleController->pricing();
            $response->json($result);
            exit;
        }
        
        $response->status(404)->json(['error' => 'Module endpoint not found']);
        exit;
    }
    
    // Handle user CRUD endpoints
    if (count($segments) >= 1 && strtolower($segments[0]) === 'users') {
        $userController = new \App\Core\UserController();
        
        // Check if it's a module-related endpoint (has /modules in path)
        $isModuleEndpoint = count($segments) >= 3 && isset($segments[2]) && strtolower($segments[2]) === 'modules';
        
        // Skip to module handling if it's a module endpoint
        if (!$isModuleEndpoint) {
            $userId = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
            
            // GET /api/users - List all users
            if ($method === 'GET' && count($segments) === 1) {
                $result = $userController->list($request->all());
                $response->json($result);
                exit;
            }
            
            // GET /api/users/{id} - Get single user
            if ($method === 'GET' && count($segments) === 2 && $userId) {
                $result = $userController->get($userId);
                $response->json($result);
                exit;
            }
            
            // POST /api/users - Create new user
            if ($method === 'POST' && count($segments) === 1) {
                $result = $userController->create($request->all());
                $response->json($result);
                exit;
            }
            
            // PUT /api/users/{id} - Update user
            if ($method === 'PUT' && count($segments) === 2 && $userId) {
                $result = $userController->update($userId, $request->all());
                $response->json($result);
                exit;
            }
            
            // DELETE /api/users/{id} - Delete user
            if ($method === 'DELETE' && count($segments) === 2 && $userId) {
                $result = $userController->delete($userId);
                $response->json($result);
                exit;
            }
            
            // POST /api/users/{id}/toggle-status - Toggle active status
            if ($method === 'POST' && count($segments) === 3 && $userId && strtolower($segments[2]) === 'toggle-status') {
                $result = $userController->toggleStatus($userId);
                $response->json($result);
                exit;
            }
            
            // POST /api/users/{id}/reset-password - Reset password
            if ($method === 'POST' && count($segments) === 3 && $userId && strtolower($segments[2]) === 'reset-password') {
                $result = $userController->resetPassword($userId, $request->all());
                $response->json($result);
                exit;
            }
            
            // GET /api/users/{id}/permissions - Get user permissions
            if ($method === 'GET' && count($segments) === 3 && $userId && strtolower($segments[2]) === 'permissions') {
                $result = $userController->getPermissions($userId);
                $response->json($result);
                exit;
            }
            
            // POST /api/users/{id}/permissions - Save user permissions
            if ($method === 'POST' && count($segments) === 3 && $userId && strtolower($segments[2]) === 'permissions') {
                $result = $userController->savePermissions($userId, $request->all());
                $response->json($result);
                exit;
            }
            
            $response->status(404)->json(['error' => 'User endpoint not found']);
            exit;
        }
    }
    
    // Handle user module endpoints
    if (count($segments) >= 3 && strtolower($segments[0]) === 'users' && strtolower($segments[2]) === 'modules') {
        $moduleController = new \App\Core\ModuleController();
        $userId = (int)$segments[1];
        
        // GET /api/users/{id}/modules - Get user's modules
        if ($method === 'GET' && count($segments) === 3) {
            $result = $moduleController->getUserModules($userId);
            $response->json($result);
            exit;
        }
        
        // POST /api/users/{id}/modules - Set user's modules (bulk)
        if ($method === 'POST' && count($segments) === 3) {
            $result = $moduleController->setUserModules($userId, $request->all());
            $response->json($result);
            exit;
        }
        
        // GET /api/users/{id}/modules/cost - Get cost
        if ($method === 'GET' && count($segments) === 4 && $segments[3] === 'cost') {
            $result = $moduleController->getUserModuleCost($userId);
            $response->json($result);
            exit;
        }
        
        // PUT /api/users/{id}/modules/{moduleName} - Update single module permission
        if ($method === 'PUT' && count($segments) === 4) {
            $moduleName = $segments[3];
            $result = $moduleController->updateModulePermission($userId, $moduleName, $request->all());
            $response->json($result);
            exit;
        }
        
        // DELETE /api/users/{id}/modules/{moduleName} - Remove module access
        if ($method === 'DELETE' && count($segments) === 4) {
            $moduleName = $segments[3];
            $result = $moduleController->removeModuleAccess($userId, $moduleName);
            $response->json($result);
            exit;
        }
        
        $response->status(404)->json(['error' => 'User module endpoint not found']);
        exit;
    }
    
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
                // Return 401 if token is invalid
                if (isset($result['success']) && !$result['success']) {
                    $response->status(401)->json($result);
                    exit;
                }
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
    $id = $segments[2] ?? null;  // ID is the 3rd segment (after module and entity)
    
    Logger::debug("Path segments parsed", [
        'moduleName' => $moduleName,
        'entityName' => $entityName,
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
