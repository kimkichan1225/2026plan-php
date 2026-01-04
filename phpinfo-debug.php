<?php
/**
 * PHP 환경 정보 및 환경 변수 전체 확인
 * 보안을 위해 배포 후 반드시 삭제할 것!
 */

// 환경 변수만 필터링해서 출력
echo "=== Environment Variables ===\n\n";

// $_ENV 확인
echo "--- \$_ENV ---\n";
if (empty($_ENV)) {
    echo "(empty)\n";
} else {
    foreach ($_ENV as $key => $value) {
        if (stripos($key, 'pass') !== false || stripos($key, 'secret') !== false) {
            $value = str_repeat('*', min(strlen($value), 20));
        }
        echo "$key = $value\n";
    }
}
echo "\n";

// $_SERVER에서 Railway 관련만
echo "--- \$_SERVER (Railway/DB related) ---\n";
$found = false;
foreach ($_SERVER as $key => $value) {
    if (stripos($key, 'railway') !== false ||
        stripos($key, 'mysql') !== false ||
        stripos($key, 'db_') !== false) {
        $found = true;
        if (stripos($key, 'pass') !== false || stripos($key, 'secret') !== false) {
            $value = str_repeat('*', min(strlen($value), 20));
        }
        echo "$key = $value\n";
    }
}
if (!$found) {
    echo "(no Railway/DB variables found)\n";
}
echo "\n";

// getenv() 확인
echo "--- getenv() test ---\n";
$testVars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
             'MYSQLHOST', 'MYSQLPORT', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD'];
foreach ($testVars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        if (stripos($var, 'pass') !== false) {
            $value = str_repeat('*', min(strlen($value), 20));
        }
        echo "$var = $value\n";
    }
}
echo "\n";

echo "=== Full phpinfo() ===\n";
echo "(Check browser for detailed output)\n\n";

// HTML 출력으로 전환
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Environment Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>PHP Environment Information</h1>
    <h2>Quick Check - Environment Variables</h2>
    <pre><?php
        echo "DB_HOST: " . (getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'NOT SET') . "\n";
        echo "DB_PORT: " . (getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? 'NOT SET') . "\n";
        echo "DB_NAME: " . (getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'NOT SET') . "\n";
        echo "DB_USER: " . (getenv('DB_USER') ?: $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'NOT SET') . "\n";
        echo "DB_PASS: " . (($pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '') ? str_repeat('*', strlen($pass)) : 'NOT SET') . "\n";
    ?></pre>

    <h2>Full PHP Info</h2>
    <?php phpinfo(); ?>
</body>
</html>
