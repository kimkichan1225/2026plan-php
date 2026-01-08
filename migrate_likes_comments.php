<?php
/**
 * 좋아요/댓글 시스템 마이그레이션 스크립트
 *
 * 사용법: 브라우저에서 migrate_likes_comments.php를 방문하여 실행
 * 실행 후 보안을 위해 이 파일을 삭제하세요.
 */

require_once __DIR__ . '/config/database.php';

// HTML 헤더
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>좋아요/댓글 시스템 마이그레이션</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💙 좋아요/댓글 시스템 데이터베이스 마이그레이션</h1>

        <?php
        try {
            echo '<div class="status info">📋 마이그레이션 시작...</div>';

            $db = getDBConnection();

            // goal_likes 테이블 생성
            echo '<div class="status info">💙 좋아요 테이블 생성 중...</div>';

            $stmt = $db->query("SHOW TABLES LIKE 'goal_likes'");
            if ($stmt->fetch()) {
                echo '<div class="status warning">⚠️ goal_likes 테이블이 이미 존재합니다.</div>';
            } else {
                $db->exec("
                    CREATE TABLE goal_likes (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        goal_id INT UNSIGNED NOT NULL,
                        user_id INT UNSIGNED NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_like (goal_id, user_id) COMMENT '중복 좋아요 방지'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='목표 좋아요'
                ");
                echo '<div class="status success">✅ goal_likes 테이블 생성 완료</div>';
            }

            // goal_comments 테이블 생성
            echo '<div class="status info">💬 댓글 테이블 생성 중...</div>';

            $stmt = $db->query("SHOW TABLES LIKE 'goal_comments'");
            if ($stmt->fetch()) {
                echo '<div class="status warning">⚠️ goal_comments 테이블이 이미 존재합니다.</div>';
            } else {
                $db->exec("
                    CREATE TABLE goal_comments (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        goal_id INT UNSIGNED NOT NULL,
                        user_id INT UNSIGNED NOT NULL,
                        content TEXT NOT NULL COMMENT '댓글 내용',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_goal_id (goal_id),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='목표 댓글'
                ");
                echo '<div class="status success">✅ goal_comments 테이블 생성 완료</div>';
            }

            // 테이블 구조 확인
            echo '<div class="status info">🔍 테이블 구조 확인...</div>';

            echo '<div class="status info"><strong>goal_likes 테이블:</strong><pre>';
            $stmt = $db->query("DESCRIBE goal_likes");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo sprintf("%-15s %-25s %s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                );
            }
            echo '</pre></div>';

            echo '<div class="status info"><strong>goal_comments 테이블:</strong><pre>';
            $stmt = $db->query("DESCRIBE goal_comments");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo sprintf("%-15s %-25s %s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                );
            }
            echo '</pre></div>';

            echo '<div class="status success">🎉 마이그레이션 완료!</div>';
            echo '<div class="status warning">⚠️ <strong>보안 주의:</strong> 마이그레이션 완료 후 이 파일(<code>migrate_likes_comments.php</code>)을 삭제하세요!</div>';

        } catch (PDOException $e) {
            echo '<div class="status error">❌ 데이터베이스 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="status error">Error Code: ' . $e->getCode() . '</div>';
        } catch (Exception $e) {
            echo '<div class="status error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>
