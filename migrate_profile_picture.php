<?php
/**
 * 프로필 사진 마이그레이션 스크립트
 */

require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로필 사진 마이그레이션</title>
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
        <h1>🖼️ 프로필 사진 기능 마이그레이션</h1>

        <?php
        try {
            echo '<div class="status info">📋 마이그레이션 시작...</div>';

            $db = getDBConnection();

            // 컬럼 존재 확인
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
            if ($stmt->fetch()) {
                echo '<div class="status warning">⚠️ profile_picture 컬럼이 이미 존재합니다.</div>';
            } else {
                echo '<div class="status info">➕ profile_picture 컬럼 추가 중...</div>';
                $db->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL COMMENT '프로필 사진 파일명' AFTER name");
                echo '<div class="status success">✅ profile_picture 컬럼 추가 완료</div>';
            }

            // uploads 디렉토리 생성
            $uploadsDir = __DIR__ . '/uploads';
            $profileDir = $uploadsDir . '/profiles';

            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
                echo '<div class="status success">✅ uploads 디렉토리 생성 완료</div>';
            }

            if (!is_dir($profileDir)) {
                mkdir($profileDir, 0755, true);
                echo '<div class="status success">✅ uploads/profiles 디렉토리 생성 완료</div>';
            }

            // .htaccess 파일 생성 (보안)
            $htaccessContent = "# Allow only image files\n";
            $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
            $htaccessContent .= "    Order Allow,Deny\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</FilesMatch>\n";

            file_put_contents($profileDir . '/.htaccess', $htaccessContent);
            echo '<div class="status success">✅ .htaccess 파일 생성 완료</div>';

            // 테이블 구조 확인
            echo '<div class="status info">🔍 users 테이블 구조 확인...</div>';
            $stmt = $db->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<div class="status info"><pre>';
            foreach ($columns as $col) {
                echo sprintf("%-20s %-30s %s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
                );
            }
            echo '</pre></div>';

            echo '<div class="status success">🎉 마이그레이션 완료!</div>';
            echo '<div class="status warning">⚠️ <strong>보안:</strong> 완료 후 이 파일(migrate_profile_picture.php)을 삭제하세요!</div>';

        } catch (PDOException $e) {
            echo '<div class="status error">❌ 데이터베이스 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="status error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>
