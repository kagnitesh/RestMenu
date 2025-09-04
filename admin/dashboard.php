<?php
// Secure session cookie
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

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Auth check
if (empty($_SESSION['is_authenticated'])) {
    header('Location: /admin/login.php');
    exit;
}

$uploadsDir = dirname(__DIR__) . '/uploads';
$menuFile = $uploadsDir . '/menu.jpg';
$lastUpdated = file_exists($menuFile) ? filemtime($menuFile) : 0;
$menuUrl = '/uploads/menu.jpg?v=' . ($lastUpdated ?: time());

$message = '';
$error = '';

// CSRF setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
        if ($action === 'upload') {
            if (!isset($_FILES['menu_image']) || $_FILES['menu_image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Upload failed. Please choose a valid image file.';
            } else {
                $tmpPath = $_FILES['menu_image']['tmp_name'];
                $fileInfo = getimagesize($tmpPath);
                if ($fileInfo === false) {
                    $error = 'Uploaded file is not a valid image.';
                } else {
                    $mime = $fileInfo['mime'];
                    if ($mime !== 'image/jpeg' && $mime !== 'image/png') {
                        $error = 'Only JPG or PNG files are allowed.';
                    } else {
                        if ($mime === 'image/png') {
                            $image = imagecreatefrompng($tmpPath);
                            if ($image === false) {
                                $error = 'Could not process PNG image.';
                            } else {
                                imageinterlace($image, true);
                                if (!imagejpeg($image, $menuFile, 90)) {
                                    $error = 'Failed to save image.';
                                }
                                imagedestroy($image);
                            }
                        } else if ($mime === 'image/jpeg') {
                            $image = imagecreatefromjpeg($tmpPath);
                            if ($image === false) {
                                $error = 'Could not process JPEG image.';
                            } else {
                                imageinterlace($image, true);
                                if (!imagejpeg($image, $menuFile, 90)) {
                                    $error = 'Failed to save image.';
                                }
                                imagedestroy($image);
                            }
                        }

                        if ($error === '') {
                            @chmod($menuFile, 0644);
                            clearstatcache(true, $menuFile);
                            $lastUpdated = filemtime($menuFile);
                            $menuUrl = '/uploads/menu.jpg?v=' . $lastUpdated;
                            $message = 'Menu image updated successfully.';
                        }
                    }
                }
            }
        } else if ($action === 'change_credentials') {
            $currentPassword = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
            $newUsername = isset($_POST['new_username']) ? trim((string)$_POST['new_username']) : '';
            $newPassword = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
            $confirmPassword = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

            // Load current credentials
            $configPath = dirname(__DIR__) . '/config.php';
            $config = require $configPath;
            $currentUsername = isset($config['ADMIN_USERNAME']) ? (string)$config['ADMIN_USERNAME'] : '';
            $currentPlain = isset($config['ADMIN_PASSWORD']) ? (string)$config['ADMIN_PASSWORD'] : '';

            if (!hash_equals($currentPlain, $currentPassword)) {
                $error = 'Current password is incorrect.';
            } else if ($newUsername === '' || $newPassword === '') {
                $error = 'Username and new password are required.';
            } else if (!hash_equals($newPassword, $confirmPassword)) {
                $error = 'New password and confirmation do not match.';
            } else {
                // Write new config atomically
                $newConfig = "<?php\nreturn [\n    'ADMIN_USERNAME' => '" . str_replace("'", "\\'", $newUsername) . "',\n    'ADMIN_PASSWORD' => '" . str_replace("'", "\\'", $newPassword) . "',\n];\n";
                $tmpFile = $configPath . '.tmp';
                if (file_put_contents($tmpFile, $newConfig, LOCK_EX) === false) {
                    $error = 'Failed to write configuration.';
                } else if (!rename($tmpFile, $configPath)) {
                    @unlink($tmpFile);
                    $error = 'Failed to update configuration.';
                } else {
                    clearstatcache(true, $configPath);
                    $message = 'Credentials updated. Please use the new login next time.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <style>
        body { margin:0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Arial, sans-serif; background:#f4f5f7; color:#111; }
        .container { max-width: 980px; margin: 0 auto; padding: 24px; }
        .topbar { display:flex; justify-content: space-between; align-items:center; margin-bottom: 16px; }
        .card { background:#fff; border-radius:10px; box-shadow:0 6px 24px rgba(0,0,0,0.12); padding:20px; }
        h1 { margin:0; font-size:22px; }
        .msg { margin-bottom:12px; padding:10px 12px; border-radius:6px; font-size:14px; }
        .msg.success { background:#e6ffed; color:#046307; }
        .msg.error { background:#ffe5e5; color:#b30000; }
        .preview { margin-top:16px; }
        .preview img { max-width:100%; height:auto; border-radius:8px; box-shadow:0 6px 24px rgba(0,0,0,0.12); background:#fff; }
        .actions { margin-top:16px; }
        .row { display:flex; flex-wrap:wrap; gap:16px; }
        .col { flex:1 1 320px; }
        label { display:block; margin-bottom:6px; font-size:14px; color:#333; }
        input[type=file] { display:block; margin-bottom:12px; }
        button { padding:10px 12px; background:#111; color:#fff; border:none; border-radius:6px; font-size:15px; cursor:pointer; }
        .meta { margin-top:8px; color:#555; font-size:13px; }
        .logout { text-decoration:none; color:#111; font-weight:600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <h1>Admin Dashboard</h1>
            <a class="logout" href="/admin/logout.php">Logout</a>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <h2 style="margin-top:0; font-size:18px;">Current Menu Preview</h2>
                    <div class="preview">
                        <?php if (file_exists($menuFile)): ?>
                            <img src="<?php echo htmlspecialchars($menuUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Menu Preview">
                            <div class="meta">Last updated: <?php echo $lastUpdated ? date('Y-m-d H:i:s', $lastUpdated) : 'N/A'; ?></div>
                        <?php else: ?>
                            <div class="meta">No menu uploaded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <h2 style="margin-top:0; font-size:18px;">Upload New Menu</h2>
                    <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <form method="post" enctype="multipart/form-data" action="/admin/dashboard.php" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="upload">
                        <label for="menu_image">Choose JPG or PNG</label>
                        <input id="menu_image" name="menu_image" type="file" accept="image/jpeg,image/png" required>
                        <button type="submit">Upload & Replace</button>
                        <div class="meta">The file will be saved as /uploads/menu.jpg</div>
                    </form>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <h2 style="margin-top:0; font-size:18px;">Change Credentials</h2>
                    <form method="post" action="/admin/dashboard.php" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="change_credentials">
                        <label for="current_password">Current Password</label>
                        <input id="current_password" name="current_password" type="password" required>

                        <label for="new_username" style="margin-top:10px;">New Username</label>
                        <input id="new_username" name="new_username" type="text" required>

                        <label for="new_password" style="margin-top:10px;">New Password</label>
                        <input id="new_password" name="new_password" type="password" required>

                        <label for="confirm_password" style="margin-top:10px;">Confirm New Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required>

                        <button type="submit" style="margin-top:12px;">Update Credentials</button>
                        <div class="meta">This updates values in config.php</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


