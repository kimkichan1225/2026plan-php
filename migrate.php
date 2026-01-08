<?php
/**
 * 데이터베이스 마이그레이션 스크립트
 * URL: /migrate.php
 *
 * 보안: 실행 후 이 파일을 삭제하세요!
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #ffaa00; }
        pre { background: #000; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Database Migration</h1>
    <pre>';

try {
    $db = getDBConnection();

    echo "<span class='info'>📋 Step 1: Checking if priority column exists...</span>\n";

    // priority 컬럼이 이미 있는지 확인
    $stmt = $db->query("SHOW COLUMNS FROM goals LIKE 'priority'");
    $columnExists = $stmt->fetch();

    if ($columnExists) {
        echo "<span class='success'>✅ Priority column already exists!</span>\n\n";
    } else {
        echo "<span class='info'>📋 Step 2: Adding priority column...</span>\n";

        // priority 컬럼 추가
        $db->exec("ALTER TABLE goals
                   ADD COLUMN priority ENUM('high', 'medium', 'low') DEFAULT 'medium'
                   AFTER status");

        echo "<span class='success'>✅ Priority column added successfully!</span>\n\n";

        echo "<span class='info'>📋 Step 3: Creating index...</span>\n";

        // 인덱스 추가
        $db->exec("CREATE INDEX idx_priority ON goals(priority)");

        echo "<span class='success'>✅ Index created successfully!</span>\n\n";

        echo "<span class='info'>📋 Step 4: Updating existing records...</span>\n";

        // 기존 데이터 업데이트
        $stmt = $db->exec("UPDATE goals SET priority = 'medium' WHERE priority IS NULL");

        echo "<span class='success'>✅ Updated {$stmt} records with default priority!</span>\n\n";
    }

    // 최종 확인
    echo "<span class='info'>📋 Final Check: Current table structure</span>\n";
    $stmt = $db->query("DESCRIBE goals");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n<span class='success'>goals 테이블 구조:</span>\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']}\n";
    }

    echo "\n<span class='success'>🎉 Migration completed successfully!</span>\n";
    echo "\n<span class='error'>⚠️  IMPORTANT: Delete this migrate.php file for security!</span>\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span>\n";
}

echo '</pre>
</body>
</html>';
