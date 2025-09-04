<?php
// Harden session cookie params
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

// Disable caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// If already logged in, go to dashboard
if (!empty($_SESSION['is_authenticated']) && $_SESSION['is_authenticated'] === true) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Load credentials from config
$config = require dirname(__DIR__) . '/config.php';
$USERNAME = isset($config['ADMIN_USERNAME']) ? (string)$config['ADMIN_USERNAME'] : 'admin';
$PASSWORD = isset($config['ADMIN_PASSWORD']) ? (string)$config['ADMIN_PASSWORD'] : 'changeme123';

$error = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
        if (hash_equals($USERNAME, $username) && hash_equals($PASSWORD, $password)) {
            session_regenerate_id(true);
            $_SESSION['is_authenticated'] = true;
            unset($_SESSION['csrf_token']);
            header('Location: /admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <style>
        body { margin:0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Arial, sans-serif; background:#f4f5f7; color:#111; }
        .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { width:100%; max-width:380px; background:#fff; border-radius:10px; box-shadow:0 6px 24px rgba(0,0,0,0.12); padding:24px; }
        h1 { margin:0 0 16px 0; font-size:22px; }
        .field { margin-bottom:12px; }
        label { display:block; font-size:14px; margin-bottom:6px; color:#333; }
        input[type=text], input[type=password] { width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px; box-sizing: border-box; }
        button { width:100%; padding:10px 12px; background:#111; color:#fff; border:none; border-radius:6px; font-size:15px; cursor:pointer; }
        .error { background:#ffe5e5; color:#b30000; padding:10px 12px; border-radius:6px; margin-bottom:12px; font-size:14px; }
        .hint { font-size:12px; color:#666; margin-top:10px; }
        .footer { margin-top: 16px; text-align:center; font-size:12px; color:#666; }
        .footer a { color: inherit; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Admin Login</h1>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post" action="/admin/login.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button type="submit">Log In</button>
                <!-- <div class="hint">Default: admin / changeme123</div> -->
            </form>
            <div class="footer">Design and developed by <a href="https://zikasha.com" target="_blank" rel="noopener">Zikasha Consultancy LLP</a></div>
        </div>
    </div>
</body>
</html>


