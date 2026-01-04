<?php
/**
 * 데이터베이스 연결 설정
 *
 * 환경 변수가 있으면 사용 (Railway, 프로덕션)
 * 없으면 로컬 설정 사용 (개발 환경)
 */

// 환경 변수 가져오기 (getenv, $_ENV, $_SERVER 순서로 시도)
$getEnv = function($key, $default = '') {
    return getenv($key) ?: ($_ENV[$key] ?? ($_SERVER[$key] ?? $default));
};

define('DB_HOST', $getEnv('DB_HOST', 'localhost'));
define('DB_PORT', $getEnv('DB_PORT', '3306'));
define('DB_NAME', $getEnv('DB_NAME', 'new_year_goals'));
define('DB_USER', $getEnv('DB_USER', 'root'));
define('DB_PASS', $getEnv('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO 데이터베이스 연결 객체 반환
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
