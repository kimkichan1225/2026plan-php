<?php
/**
 * 데이터베이스 연결 설정
 *
 * 사용법:
 * 1. 이 파일을 database.php로 복사
 * 2. 아래 설정값을 본인의 환경에 맞게 수정
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'new_year_goals');
define('DB_USER', 'root');      // 본인의 MySQL 사용자명으로 변경
define('DB_PASS', '');          // 본인의 MySQL 비밀번호로 변경
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO 데이터베이스 연결 객체 반환
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
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
