<?php
/**
 * DATABASE_URL 환경 변수 확인
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking DATABASE_URL ===\n\n";

$databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null));

if ($databaseUrl) {
    echo "DATABASE_URL found!\n";
    echo "Value: " . substr($databaseUrl, 0, 20) . "...\n\n";

    // Parse DATABASE_URL
    $parts = parse_url($databaseUrl);

    echo "Parsed components:\n";
    echo "Host: " . ($parts['host'] ?? 'N/A') . "\n";
    echo "Port: " . ($parts['port'] ?? 'N/A') . "\n";
    echo "User: " . ($parts['user'] ?? 'N/A') . "\n";
    echo "Pass: " . (isset($parts['pass']) ? str_repeat('*', strlen($parts['pass'])) : 'N/A') . "\n";
    echo "DB: " . (isset($parts['path']) ? ltrim($parts['path'], '/') : 'N/A') . "\n";
} else {
    echo "DATABASE_URL NOT FOUND\n\n";

    echo "All environment variables:\n";
    foreach ($_SERVER as $key => $value) {
        if (is_string($value) && strlen($value) < 200) {
            echo "$key\n";
        }
    }
}
