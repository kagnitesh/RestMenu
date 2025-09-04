<?php
// Redirect /admin to login or dashboard
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
} else {
    session_set_cookie_params(0, '/; samesite=Strict', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
}
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!empty($_SESSION['is_authenticated'])) {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /admin/login.php');
}
exit;


