<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Follow.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();

$userModel = new User();
$followModel = new Follow();

// 검색 키워드
$searchQuery = trim($_GET['q'] ?? '');

// 사용자 검색
$users = [];
if (!empty($searchQuery)) {
    $users = $userModel->search($searchQuery);
} else {
    // 검색어가 없으면 활동적인 사용자 표시
    $users = $userModel->getActiveUsers(20);
}

// 각 사용자에 대한 팔로우 여부 확인
foreach ($users as &$user) {
    $user['is_following'] = $followModel->isFollowing($userId, $user['id']);
}
unset($user);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사용자 검색 - 신년계획 관리</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <header class="header">
            <div class="header-content">
                <h1 class="logo">신년계획 관리</h1>
                <nav class="nav">
                    <a href="dashboard.php" class="nav-link">대시보드</a>
                    <a href="goal_list.php" class="nav-link">목표 관리</a>
                    <a href="community.php" class="nav-link">커뮤니티</a>
                    <a href="users.php" class="nav-link active">사용자</a>
                    <a href="notifications.php" class="nav-link">
                        알림
                        <span class="notification-badge" id="notificationBadge" style="display: none;"></span>
                    </a>
                    <a href="profile.php" class="nav-link">프로필</a>
                    <button id="themeToggle" class="theme-toggle" aria-label="테마 전환">
                        <span class="icon">☀️</span>
                    </button>
                    <span class="user-info">안녕하세요, <?= e($userName) ?>님</span>
                    <a href="logout.php" class="btn btn-sm btn-secondary">로그아웃</a>
                </nav>
            </div>
        </header>

        <!-- 메인 컨텐츠 -->
        <main class="main-content">
            <div class="page-header">
                <h2>👥 사용자 검색</h2>
            </div>

            <!-- 검색 폼 -->
            <div class="search-section">
                <form method="GET" action="users.php" class="search-form">
                    <input
                        type="text"
                        name="q"
                        placeholder="이름 또는 이메일로 검색..."
                        value="<?= e($searchQuery) ?>"
                        class="search-input"
                    >
                    <button type="submit" class="btn btn-primary">검색</button>
                </form>
            </div>

            <!-- 검색 결과 또는 추천 사용자 -->
            <div class="users-container">
                <?php if (!empty($searchQuery)): ?>
                    <h3>검색 결과: "<?= e($searchQuery) ?>"</h3>
                <?php else: ?>
                    <h3>활동적인 사용자</h3>
                <?php endif; ?>

                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <p>검색 결과가 없습니다.</p>
                    </div>
                <?php else: ?>
                    <div class="users-grid">
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['id'] === $userId) continue; // 자기 자신은 표시하지 않음 ?>

                            <div class="user-card">
                                <a href="user_profile.php?id=<?= $user['id'] ?>" class="user-card-link">
                                    <div class="user-avatar">
                                        <?php if (!empty($user['profile_picture'])): ?>
                                            <img src="uploads/profiles/<?= e($user['profile_picture']) ?>" alt="<?= e($user['name']) ?>">
                                        <?php else: ?>
                                            <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="user-name"><?= e($user['name']) ?></h4>
                                </a>

                                <div class="user-stats">
                                    <div class="user-stat">
                                        <span class="stat-value"><?= $user['followers_count'] ?? 0 ?></span>
                                        <span class="stat-label">팔로워</span>
                                    </div>
                                    <div class="user-stat">
                                        <span class="stat-value"><?= $user['following_count'] ?? 0 ?></span>
                                        <span class="stat-label">팔로잉</span>
                                    </div>
                                    <?php if (isset($user['public_goals_count'])): ?>
                                        <div class="user-stat">
                                            <span class="stat-value"><?= $user['public_goals_count'] ?></span>
                                            <span class="stat-label">목표</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <form method="POST" action="ajax/follow.php" class="follow-form" data-user-id="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $user['is_following'] ? 'unfollow' : 'follow' ?>">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button
                                        type="submit"
                                        class="btn <?= $user['is_following'] ? 'btn-secondary' : 'btn-primary' ?> btn-sm follow-btn"
                                    >
                                        <?= $user['is_following'] ? '팔로잉' : '팔로우' ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // 팔로우 버튼 AJAX 처리
        document.querySelectorAll('.follow-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                const btn = form.querySelector('.follow-btn');
                const originalText = btn.textContent;

                try {
                    btn.disabled = true;
                    btn.textContent = '처리 중...';

                    const response = await fetch('ajax/follow.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // 버튼 상태 토글
                        if (result.action === 'followed') {
                            btn.textContent = '팔로잉';
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-secondary');
                            form.querySelector('input[name="action"]').value = 'unfollow';
                        } else {
                            btn.textContent = '팔로우';
                            btn.classList.remove('btn-secondary');
                            btn.classList.add('btn-primary');
                            form.querySelector('input[name="action"]').value = 'follow';
                        }
                    } else {
                        alert(result.message || '오류가 발생했습니다.');
                        btn.textContent = originalText;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('오류가 발생했습니다.');
                    btn.textContent = originalText;
                } finally {
                    btn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>
