<?php
// Router for PHP built-in server to redirect unknown paths to root
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $fullPath = __DIR__ . $path;
    if ($path !== '/' && file_exists($fullPath) && is_file($fullPath)) {
        return false; // serve the requested file
    }
    if ($path === '/admin') {
        require __DIR__ . '/admin/index.php';
        return true;
    }
    // For any non-existent path, redirect to root
    header('Location: /');
    exit;
}


