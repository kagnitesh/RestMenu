<?php
// Disable caching for the page and image to ensure instant updates
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$uploadDir = __DIR__ . '/uploads';
$menuPath = $uploadDir . '/menu.jpg';
$mealMetaPath = $uploadDir . '/meal.txt';

// Determine last updated time (fallback to current time if file missing)
$menuMtime = file_exists($menuPath) ? filemtime($menuPath) : 0;
$metaMtime = file_exists($mealMetaPath) ? filemtime($mealMetaPath) : 0;
$lastUpdated = max($menuMtime, $metaMtime, time());
// Add cache-busting query param using last modified time
$menuUrl = '/uploads/menu.jpg?v=' . $lastUpdated;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Arial, sans-serif; background:#fafafa; color:#111; }
        .container { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; }
        .menu { max-width: 900px; width: 100%; position: relative; }
        .menu img { width: 100%; height: auto; display: block; box-shadow: 0 6px 24px rgba(0,0,0,0.12); border-radius: 8px; background: #fff; }
        .badge { position: absolute; left: 50%; top: 8px; transform: translateX(-50%); background: rgba(0,0,0,0.7); color:#fff; padding: 6px 12px; border-radius: 20px; font-size: 14px; letter-spacing: 0.3px; }
        .updated { margin-top: 12px; text-align: center; font-size: 14px; color: #555; }
        .footer { margin-top: 24px; text-align: center; font-size: 13px; color:#666; }
        .footer a { color: inherit; text-decoration: underline; }
    </style>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
</head>
<body>
    <div class="container">
        <div class="menu">
            <?php if (file_exists($menuPath)): ?>
                <?php $mealType = file_exists($mealMetaPath) ? trim((string)file_get_contents($mealMetaPath)) : ''; ?>
                <?php if ($mealType !== ''): ?>
                    <div class="badge"><?php echo htmlspecialchars($mealType, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <img src="<?php echo htmlspecialchars($menuUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Restaurant Menu">
            <?php else: ?>
                <div style="text-align:center; padding: 48px; border: 2px dashed #ccc; border-radius: 8px; background:#fff;">
                    Menu not uploaded yet.
                </div>
            <?php endif; ?>
            <div class="updated">Last updated: <?php echo date('Y-m-d H:i:s', $lastUpdated); ?></div>
        </div>
        <div class="footer">Design and developed by <a href="https://zikasha.com" target="_blank" rel="noopener">Zikasha Consultancy LLP</a></div>
    </div>
</body>
</html>


