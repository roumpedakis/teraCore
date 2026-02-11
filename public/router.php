<?php

// Router for PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    
    // Handle admin clean URLs - map to .html files
    if ($path === '/admin/login') {
        $file = __DIR__ . '/admin/login.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($file);
            exit;
        }
    }
    
    if ($path === '/admin/dashboard') {
        $file = __DIR__ . '/admin/dashboard.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($file);
            exit;
        }
    }
    
    if ($path === '/admin/users') {
        $file = __DIR__ . '/admin/users.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($file);
            exit;
        }
    }
    
    if ($path === '/admin/modules') {
        $file = __DIR__ . '/admin/modules.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($file);
            exit;
        }
    }
    
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
