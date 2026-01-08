<?php
/**
 * 커뮤니티 기능 마이그레이션 스크립트
 *
 * 사용법: 브라우저에서 migrate_community.php를 방문하여 실행
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
    <title>커뮤니티 기능 마이그레이션</title>
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
        <h1>🔧 커뮤니티 기능 데이터베이스 마이그레이션</h1>

        <?php
        try {
            echo '<div class="status info">📋 마이그레이션 시작...</div>';

            $db = getDBConnection();

            // 이미 마이그레이션되었는지 확인
            echo '<div class="status info">🔍 기존 컬럼 확인 중...</div>';

            $stmt = $db->query("SHOW COLUMNS FROM goals LIKE 'visibility'");
            $visibilityExists = $stmt->fetch() !== false;

            if ($visibilityExists) {
                echo '<div class="status warning">⚠️ 마이그레이션이 이미 실행되었습니다. visibility 컬럼이 존재합니다.</div>';
                echo '<div class="status info">현재 goals 테이블 구조:</div>';

                $stmt = $db->query("DESCRIBE goals");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<div class="status info"><pre>';
                foreach ($columns as $col) {
                    echo sprintf("%-20s %-20s %s\n",
                        $col['Field'],
                        $col['Type'],
                        $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                    );
                }
                echo '</pre></div>';

            } else {
                echo '<div class="status info">✅ 마이그레이션 필요. 컬럼 추가 시작...</div>';

                // visibility 컬럼 추가
                echo '<div class="status info">➕ visibility 컬럼 추가...</div>';
                $db->exec("ALTER TABLE goals ADD COLUMN visibility ENUM('private', 'public') DEFAULT 'private' COMMENT '공개 설정' AFTER priority");

                // views 컬럼 추가
                echo '<div class="status info">➕ views 컬럼 추가...</div>';
                $db->exec("ALTER TABLE goals ADD COLUMN views INT UNSIGNED DEFAULT 0 COMMENT '조회수' AFTER visibility");

                // likes 컬럼 추가
                echo '<div class="status info">➕ likes 컬럼 추가...</div>';
                $db->exec("ALTER TABLE goals ADD COLUMN likes INT UNSIGNED DEFAULT 0 COMMENT '좋아요 수' AFTER views");

                // 인덱스 추가
                echo '<div class="status info">📊 인덱스 추가...</div>';

                try {
                    $db->exec("CREATE INDEX idx_visibility ON goals(visibility)");
                    echo '<div class="status success">✅ idx_visibility 인덱스 생성 완료</div>';
                } catch (Exception $e) {
                    echo '<div class="status warning">⚠️ idx_visibility 인덱스가 이미 존재합니다.</div>';
                }

                try {
                    $db->exec("CREATE INDEX idx_views ON goals(views)");
                    echo '<div class="status success">✅ idx_views 인덱스 생성 완료</div>';
                } catch (Exception $e) {
                    echo '<div class="status warning">⚠️ idx_views 인덱스가 이미 존재합니다.</div>';
                }

                try {
                    $db->exec("CREATE INDEX idx_likes ON goals(likes)");
                    echo '<div class="status success">✅ idx_likes 인덱스 생성 완료</div>';
                } catch (Exception $e) {
                    echo '<div class="status warning">⚠️ idx_likes 인덱스가 이미 존재합니다.</div>';
                }

                // 결과 확인
                echo '<div class="status info">🔍 변경 사항 확인...</div>';
                $stmt = $db->query("DESCRIBE goals");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<div class="status success"><pre>';
                foreach ($columns as $col) {
                    echo sprintf("%-20s %-20s %s\n",
                        $col['Field'],
                        $col['Type'],
                        $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                    );
                }
                echo '</pre></div>';

                echo '<div class="status success">🎉 마이그레이션 완료!</div>';
            }

            // 샘플 데이터 확인
            echo '<div class="status info">📊 goals 테이블 샘플 데이터 (최대 5개):</div>';
            $stmt = $db->query("SELECT id, title, visibility, views, likes FROM goals LIMIT 5");
            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($goals)) {
                echo '<div class="status warning">⚠️ 아직 목표가 없습니다.</div>';
            } else {
                echo '<div class="status info"><pre>';
                echo sprintf("%-5s %-30s %-12s %-8s %-8s\n", 'ID', 'Title', 'Visibility', 'Views', 'Likes');
                echo str_repeat('-', 70) . "\n";
                foreach ($goals as $goal) {
                    echo sprintf("%-5d %-30s %-12s %-8d %-8d\n",
                        $goal['id'],
                        mb_substr($goal['title'], 0, 28),
                        $goal['visibility'] ?? 'private',
                        $goal['views'] ?? 0,
                        $goal['likes'] ?? 0
                    );
                }
                echo '</pre></div>';
            }

            echo '<div class="status warning">⚠️ <strong>보안 주의:</strong> 마이그레이션 완료 후 이 파일(<code>migrate_community.php</code>)을 삭제하세요!</div>';

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
