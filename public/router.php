<?php

// Router for PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    
    // Handle /admin redirect
    if ($path === '/admin' || $path === '/admin/') {
        header('Location: /admin/login');
        exit;
    }
    
    // Check if static file exists
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false; // Let PHP serve it
    }
}

// Forward to index.php for API routes
require __DIR__ . '/index.php';
