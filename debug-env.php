<?php
/**
 * Railway 환경 변수 디버깅 페이지
 * 배포 후 이 파일은 삭제할 것
 * Updated: 2026-01-04
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== Railway Environment Variables Debug ===\n\n";

// Railway MySQL 환경 변수들
$envVars = [
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLDATABASE',
    'MYSQLUSER',
    'MYSQLPASSWORD',
    'RAILWAY_TCP_PROXY_DOMAIN',
    'RAILWAY_TCP_PROXY_PORT',
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'DB_PASS'
];

foreach ($envVars as $var) {
    $value = getenv($var) ?: ($_ENV[$var] ?? ($_SERVER[$var] ?? 'NOT SET'));

    // 비밀번호는 마스킹
    if (strpos(strtolower($var), 'pass') !== false && $value !== 'NOT SET') {
        $value = str_repeat('*', strlen($value));
    }

    echo sprintf("%-30s = %s\n", $var, $value);
}

echo "\n=== Current Database Config ===\n\n";

require_once __DIR__ . '/config/database.php';

echo sprintf("%-30s = %s\n", 'DB_HOST', DB_HOST);
echo sprintf("%-30s = %s\n", 'DB_PORT', DB_PORT);
echo sprintf("%-30s = %s\n", 'DB_NAME', DB_NAME);
echo sprintf("%-30s = %s\n", 'DB_USER', DB_USER);
echo sprintf("%-30s = %s\n", 'DB_PASS', str_repeat('*', strlen(DB_PASS)));
echo sprintf("%-30s = %s\n", 'DB_CHARSET', DB_CHARSET);

echo "\n=== Connection Test ===\n\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );
    echo "DSN: $dsn\n\n";

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✓ Database connection successful!\n";

    $stmt = $pdo->query('SELECT VERSION() as version');
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $version['version'] . "\n";

} catch (PDOException $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
