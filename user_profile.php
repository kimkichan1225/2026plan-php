<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Goal.php';
require_once __DIR__ . '/models/Follow.php';

requireLogin();

$currentUserId = getCurrentUserId();
$currentUserName = getCurrentUserName();
$unreadNotifications = getUnreadNotificationCount();

$userModel = new User();
$goalModel = new Goal();
$followModel = new Follow();

// 조회할 사용자 ID
$profileUserId = (int) ($_GET['id'] ?? 0);

if ($profileUserId <= 0) {
    header('Location: users.php');
    exit;
}

// 자기 자신의 프로필이면 profile.php로 리다이렉트
if ($profileUserId === $currentUserId) {
    header('Location: profile.php');
    exit;
}

// 사용자 정보 조회
$profileUser = $userModel->findById($profileUserId);

if (!$profileUser) {
    header('Location: users.php');
    exit;
}

// 팔로우 여부
$isFollowing = $followModel->isFollowing($currentUserId, $profileUserId);

// 공개 목표 조회
$currentYear = date('Y');
$publicGoals = $goalModel->findPublicGoals(null, 'latest', 50, 0);
$publicGoals = array_filter($publicGoals, fn($g) => $g['user_id'] == $profileUserId);

// 통계
$totalPublicGoals = count($publicGoals);
$completedGoals = count(array_filter($publicGoals, fn($g) => $g['status'] === 'completed'));
$inProgressGoals = count(array_filter($publicGoals, fn($g) => $g['status'] === 'in_progress'));

// 평균 진행률
$totalProgress = array_sum(array_column($publicGoals, 'progress_percentage'));
$avgProgress = $totalPublicGoals > 0 ? round($totalProgress / $totalPublicGoals, 1) : 0;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($profileUser['name']) ?>님의 프로필 - 신년계획 관리</title>
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
                    <span class="user-info">안녕하세요, <?= e($currentUserName) ?>님</span>
                    <a href="logout.php" class="btn btn-sm btn-secondary">로그아웃</a>
                </nav>
            </div>
        </header>

        <!-- 메인 컨텐츠 -->
        <main class="main-content">
            <!-- 프로필 헤더 -->
            <div class="profile-header">
                <div class="profile-info">
                    <div class="profile-avatar-large">
                        <?php if (!empty($profileUser['profile_picture'])): ?>
                            <img src="uploads/profiles/<?= e($profileUser['profile_picture']) ?>" alt="<?= e($profileUser['name']) ?>">
                        <?php else: ?>
                            <?= strtoupper(mb_substr($profileUser['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-details">
                        <h2><?= e($profileUser['name']) ?></h2>
                        <p class="profile-email-public"><?= e($profileUser['email']) ?></p>
                        <div class="profile-stats-inline">
                            <div class="stat-inline">
                                <strong><?= $profileUser['followers_count'] ?? 0 ?></strong>
                                <span>팔로워</span>
                            </div>
                            <div class="stat-inline">
                                <strong><?= $profileUser['following_count'] ?? 0 ?></strong>
                                <span>팔로잉</span>
                            </div>
                            <div class="stat-inline">
                                <strong><?= $totalPublicGoals ?></strong>
                                <span>공개 목표</span>
                            </div>
                        </div>
                        <form method="POST" action="ajax/follow.php" class="follow-form-inline" data-user-id="<?= $profileUserId ?>">
                            <input type="hidden" name="action" value="<?= $isFollowing ? 'unfollow' : 'follow' ?>">
                            <input type="hidden" name="user_id" value="<?= $profileUserId ?>">
                            <button
                                type="submit"
                                class="btn <?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?> follow-btn"
                                id="followBtn"
                            >
                                <?= $isFollowing ? '팔로잉' : '팔로우' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 목표 현황 -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 목표 현황</h3>
                </div>
                <div class="card-body">
                    <div class="stats-summary">
                        <div class="stat-box">
                            <span class="stat-value"><?= $totalPublicGoals ?></span>
                            <span class="stat-label">전체 목표</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value"><?= $completedGoals ?></span>
                            <span class="stat-label">완료</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value"><?= $inProgressGoals ?></span>
                            <span class="stat-label">진행 중</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value"><?= $avgProgress ?>%</span>
                            <span class="stat-label">평균 진행률</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 공개 목표 목록 -->
            <div class="card">
                <div class="card-header">
                    <h3>🎯 공개 목표</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($publicGoals)): ?>
                        <p class="text-muted">아직 공개된 목표가 없습니다.</p>
                    <?php else: ?>
                        <div class="goal-list">
                            <?php foreach ($publicGoals as $goal): ?>
                                <div class="goal-item">
                                    <div class="goal-header">
                                        <h4>
                                            <a href="goal_detail.php?id=<?= $goal['id'] ?>" class="goal-title-link">
                                                <?= e($goal['title']) ?>
                                            </a>
                                        </h4>
                                        <span class="badge badge-<?= $goal['status'] ?>">
                                            <?= getStatusLabel($goal['status']) ?>
                                        </span>
                                    </div>
                                    <?php if ($goal['description']): ?>
                                        <p class="goal-description"><?= e(mb_substr($goal['description'], 0, 100)) ?><?= mb_strlen($goal['description']) > 100 ? '...' : '' ?></p>
                                    <?php endif; ?>
                                    <div class="goal-meta">
                                        <span class="badge badge-category"><?= e(getCategoryName($goal['category'])) ?></span>
                                        <span class="goal-progress">진행률: <?= $goal['progress_percentage'] ?>%</span>
                                        <span class="goal-engagement">
                                            ❤️ <?= $goal['likes'] ?> 👁️ <?= $goal['views'] ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // 팔로우 버튼 AJAX 처리
        const followForm = document.querySelector('.follow-form-inline');
        if (followForm) {
            followForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(followForm);
                const btn = followForm.querySelector('.follow-btn');
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
                            followForm.querySelector('input[name="action"]').value = 'unfollow';
                        } else {
                            btn.textContent = '팔로우';
                            btn.classList.remove('btn-secondary');
                            btn.classList.add('btn-primary');
                            followForm.querySelector('input[name="action"]').value = 'follow';
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
        }
    </script>
</body>
</html>
