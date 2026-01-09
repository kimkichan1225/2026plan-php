<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Follow.php';
require_once __DIR__ . '/models/User.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$unreadNotifications = getUnreadNotificationCount();

$followModel = new Follow();
$userModel = new User();

// 조회할 사용자 ID (기본값은 현재 사용자)
$targetUserId = (int) ($_GET['user_id'] ?? $userId);
$targetUser = $userModel->findById($targetUserId);

if (!$targetUser) {
    header('Location: profile.php');
    exit;
}

// 팔로워 목록 조회
$followers = $followModel->getFollowers($targetUserId, 100);

// 각 팔로워에 대한 팔로우 여부 확인 (현재 사용자가)
foreach ($followers as &$follower) {
    $follower['is_following'] = $followModel->isFollowing($userId, $follower['id']);
}
unset($follower);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($targetUser['name']) ?>님의 팔로워 - 신년계획 관리</title>
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
                    <a href="users.php" class="nav-link">사용자</a>
                    <a href="notifications.php" class="nav-link">
                        알림
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-badge"><?= $unreadNotifications ?></span>
                        <?php endif; ?>
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
                <h2>👥 <?= e($targetUser['name']) ?>님의 팔로워</h2>
                <?php if ($targetUserId !== $userId): ?>
                    <a href="user_profile.php?id=<?= $targetUserId ?>" class="btn btn-secondary">프로필로 돌아가기</a>
                <?php else: ?>
                    <a href="profile.php" class="btn btn-secondary">프로필로 돌아가기</a>
                <?php endif; ?>
            </div>

            <div class="follow-list-container">
                <?php if (empty($followers)): ?>
                    <div class="empty-state">
                        <p>아직 팔로워가 없습니다.</p>
                    </div>
                <?php else: ?>
                    <div class="users-grid">
                        <?php foreach ($followers as $follower): ?>
                            <?php if ($follower['id'] === $userId) continue; // 자기 자신 제외 ?>

                            <div class="user-card">
                                <a href="user_profile.php?id=<?= $follower['id'] ?>" class="user-card-link">
                                    <div class="user-avatar">
                                        <?php if (!empty($follower['profile_picture'])): ?>
                                            <img src="uploads/profiles/<?= e($follower['profile_picture']) ?>" alt="<?= e($follower['name']) ?>">
                                        <?php else: ?>
                                            <?= strtoupper(mb_substr($follower['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="user-name"><?= e($follower['name']) ?></h4>
                                </a>

                                <div class="user-stats">
                                    <div class="user-stat">
                                        <span class="stat-value"><?= $follower['followers_count'] ?? 0 ?></span>
                                        <span class="stat-label">팔로워</span>
                                    </div>
                                    <div class="user-stat">
                                        <span class="stat-value"><?= $follower['following_count'] ?? 0 ?></span>
                                        <span class="stat-label">팔로잉</span>
                                    </div>
                                </div>

                                <form method="POST" action="ajax/follow.php" class="follow-form" data-user-id="<?= $follower['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $follower['is_following'] ? 'unfollow' : 'follow' ?>">
                                    <input type="hidden" name="user_id" value="<?= $follower['id'] ?>">
                                    <button
                                        type="submit"
                                        class="btn <?= $follower['is_following'] ? 'btn-secondary' : 'btn-primary' ?> btn-sm follow-btn"
                                    >
                                        <?= $follower['is_following'] ? '팔로잉' : '팔로우' ?>
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
