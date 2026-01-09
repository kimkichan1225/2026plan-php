<?php
/**
 * 팔로우 및 알림 시스템 마이그레이션 스크립트
 */

require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>팔로우 & 알림 시스템 마이그레이션</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>👥🔔 팔로우 & 알림 시스템 마이그레이션</h1>

        <?php
        try {
            echo '<div class="status info">📋 마이그레이션 시작...</div>';

            $db = getDBConnection();

            // user_follows 테이블 생성
            echo '<div class="status info">➕ user_follows 테이블 생성 중...</div>';
            $db->exec("
                CREATE TABLE IF NOT EXISTS user_follows (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    follower_id INT UNSIGNED NOT NULL COMMENT '팔로우하는 사용자 ID',
                    following_id INT UNSIGNED NOT NULL COMMENT '팔로우되는 사용자 ID',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,

                    UNIQUE KEY unique_follow (follower_id, following_id),

                    INDEX idx_follower (follower_id),
                    INDEX idx_following (following_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='사용자 팔로우 관계'
            ");
            echo '<div class="status success">✅ user_follows 테이블 생성 완료</div>';

            // notifications 테이블 생성
            echo '<div class="status info">➕ notifications 테이블 생성 중...</div>';
            $db->exec("
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL COMMENT '알림을 받을 사용자 ID',
                    type ENUM('follow', 'like', 'comment', 'goal_shared') NOT NULL COMMENT '알림 유형',
                    actor_id INT UNSIGNED NULL COMMENT '알림을 발생시킨 사용자 ID',
                    goal_id INT UNSIGNED NULL COMMENT '관련 목표 ID (있는 경우)',
                    comment_id INT UNSIGNED NULL COMMENT '관련 댓글 ID (있는 경우)',
                    message TEXT NOT NULL COMMENT '알림 메시지',
                    is_read BOOLEAN DEFAULT FALSE COMMENT '읽음 여부',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
                    FOREIGN KEY (comment_id) REFERENCES goal_comments(id) ON DELETE CASCADE,

                    INDEX idx_user_read (user_id, is_read),
                    INDEX idx_created (created_at DESC)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='사용자 알림'
            ");
            echo '<div class="status success">✅ notifications 테이블 생성 완료</div>';

            // users 테이블에 컬럼 추가
            echo '<div class="status info">➕ users 테이블에 팔로워/팔로잉 수 컬럼 추가 중...</div>';

            // followers_count 컬럼 확인 및 추가
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'followers_count'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE users ADD COLUMN followers_count INT UNSIGNED DEFAULT 0 COMMENT '팔로워 수' AFTER profile_picture");
                echo '<div class="status success">✅ followers_count 컬럼 추가 완료</div>';
            } else {
                echo '<div class="status warning">⚠️ followers_count 컬럼이 이미 존재합니다.</div>';
            }

            // following_count 컬럼 확인 및 추가
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'following_count'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE users ADD COLUMN following_count INT UNSIGNED DEFAULT 0 COMMENT '팔로잉 수' AFTER followers_count");
                echo '<div class="status success">✅ following_count 컬럼 추가 완료</div>';
            } else {
                echo '<div class="status warning">⚠️ following_count 컬럼이 이미 존재합니다.</div>';
            }

            // 트리거 생성
            echo '<div class="status info">➕ 팔로우 수 업데이트 트리거 생성 중...</div>';

            // 기존 트리거 삭제 (있다면)
            $db->exec("DROP TRIGGER IF EXISTS tr_update_follow_counts_after_insert");
            $db->exec("DROP TRIGGER IF EXISTS tr_update_follow_counts_after_delete");

            // INSERT 트리거
            $db->exec("
                CREATE TRIGGER tr_update_follow_counts_after_insert
                AFTER INSERT ON user_follows
                FOR EACH ROW
                BEGIN
                    UPDATE users SET following_count = following_count + 1 WHERE id = NEW.follower_id;
                    UPDATE users SET followers_count = followers_count + 1 WHERE id = NEW.following_id;
                END
            ");

            // DELETE 트리거
            $db->exec("
                CREATE TRIGGER tr_update_follow_counts_after_delete
                AFTER DELETE ON user_follows
                FOR EACH ROW
                BEGIN
                    UPDATE users SET following_count = following_count - 1 WHERE id = OLD.follower_id;
                    UPDATE users SET followers_count = followers_count - 1 WHERE id = OLD.following_id;
                END
            ");

            echo '<div class="status success">✅ 트리거 생성 완료</div>';

            // 테이블 구조 확인
            echo '<div class="status info">🔍 생성된 테이블 구조 확인...</div>';

            echo '<h3>user_follows 테이블:</h3>';
            echo '<div class="status info"><pre>';
            $stmt = $db->query("DESCRIBE user_follows");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo sprintf("%-20s %-30s %s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                );
            }
            echo '</pre></div>';

            echo '<h3>notifications 테이블:</h3>';
            echo '<div class="status info"><pre>';
            $stmt = $db->query("DESCRIBE notifications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo sprintf("%-20s %-30s %s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                );
            }
            echo '</pre></div>';

            echo '<div class="status success">🎉 마이그레이션 완료!</div>';
            echo '<div class="status warning">⚠️ <strong>보안:</strong> 완료 후 이 파일(migrate_follow_notifications.php)을 삭제하세요!</div>';

        } catch (PDOException $e) {
            echo '<div class="status error">❌ 데이터베이스 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="status error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>
