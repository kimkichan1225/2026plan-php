<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Goal.php';
require_once __DIR__ . '/models/GoalComment.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();

$userModel = new User();
$goalModel = new Goal();
$commentModel = new GoalComment();

// 프로필 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = '이름을 입력해주세요.';
    } else {
        try {
            $updateData = ['name' => $name];

            // 프로필 사진 업로드 처리
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                // 파일 유효성 검사
                if (!in_array($file['type'], $allowedTypes)) {
                    $error = '이미지 파일만 업로드 가능합니다 (JPG, PNG, GIF).';
                } elseif ($file['size'] > $maxSize) {
                    $error = '파일 크기는 2MB 이하여야 합니다.';
                } else {
                    // 파일 저장
                    $uploadsDir = __DIR__ . '/uploads/profiles';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }

                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $destination = $uploadsDir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        // 이전 프로필 사진 삭제
                        $oldUser = $userModel->findById($userId);
                        if ($oldUser['profile_picture']) {
                            $oldFile = $uploadsDir . '/' . $oldUser['profile_picture'];
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }

                        $updateData['profile_picture'] = $filename;
                    } else {
                        $error = '파일 업로드 중 오류가 발생했습니다.';
                    }
                }
            }

            if (!isset($error)) {
                $userModel->update($userId, $updateData);
                $_SESSION['user_name'] = $name;
                $success = '프로필이 수정되었습니다.';
                $userName = $name;
            }
        } catch (Exception $e) {
            $error = '프로필 수정 중 오류가 발생했습니다.';
        }
    }
}

// 비밀번호 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = '모든 비밀번호 필드를 입력해주세요.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '새 비밀번호가 일치하지 않습니다.';
    } elseif (strlen($newPassword) < 6) {
        $error = '비밀번호는 최소 6자 이상이어야 합니다.';
    } else {
        $user = $userModel->findById($userId);
        if (!password_verify($currentPassword, $user['password'])) {
            $error = '현재 비밀번호가 올바르지 않습니다.';
        } else {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $userModel->update($userId, ['password' => $hashedPassword]);
                $success = '비밀번호가 변경되었습니다.';
            } catch (Exception $e) {
                $error = '비밀번호 변경 중 오류가 발생했습니다.';
            }
        }
    }
}

// 사용자 정보 조회
$user = $userModel->findById($userId);

// 사용자 통계
$currentYear = date('Y');
$allGoals = $goalModel->findByUser($userId, $currentYear);

$totalGoals = count($allGoals);
$completedGoals = count(array_filter($allGoals, fn($g) => $g['status'] === 'completed'));
$inProgressGoals = count(array_filter($allGoals, fn($g) => $g['status'] === 'in_progress'));
$notStartedGoals = count(array_filter($allGoals, fn($g) => $g['status'] === 'not_started'));

// 평균 진행률 계산
$totalProgress = array_sum(array_column($allGoals, 'progress_percentage'));
$avgProgress = $totalGoals > 0 ? round($totalProgress / $totalGoals, 1) : 0;

// 카테고리별 통계
$categoryStats = $goalModel->getCategoryStats($userId, $currentYear);

// 최근 댓글
$recentComments = $commentModel->findByUser($userId, 5);

// 공개 목표 수
$publicGoalsCount = count(array_filter($allGoals, fn($g) => $g['visibility'] === 'public'));

// 프로필 편집 모드
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로필 - 신년계획 관리</title>
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
                    <a href="reflection.php" class="nav-link">회고</a>
                    <a href="profile.php" class="nav-link active">프로필</a>
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
                <h2>👤 내 프로필</h2>
                <?php if (!$editMode): ?>
                    <a href="profile.php?edit=true" class="btn btn-secondary">프로필 수정</a>
                <?php endif; ?>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <!-- 프로필 정보 -->
            <div class="profile-container">
                <!-- 왼쪽: 사용자 정보 -->
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="uploads/profiles/<?= e($user['profile_picture']) ?>" alt="프로필 사진">
                            <?php else: ?>
                                <?= strtoupper(mb_substr($userName, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="profile-name"><?= e($userName) ?></h3>
                        <p class="profile-email"><?= e($user['email']) ?></p>
                        <p class="profile-joined">
                            가입일: <?= formatDate($user['created_at'], 'Y-m-d') ?>
                        </p>
                    </div>

                    <!-- 통계 요약 -->
                    <div class="profile-stats-card">
                        <h4>📊 활동 통계</h4>
                        <div class="stat-item">
                            <span class="stat-label">전체 목표</span>
                            <span class="stat-value"><?= $totalGoals ?>개</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">완료한 목표</span>
                            <span class="stat-value"><?= $completedGoals ?>개</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">진행 중</span>
                            <span class="stat-value"><?= $inProgressGoals ?>개</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">평균 진행률</span>
                            <span class="stat-value"><?= $avgProgress ?>%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">공개 목표</span>
                            <span class="stat-value"><?= $publicGoalsCount ?>개</span>
                        </div>
                    </div>
                </div>

                <!-- 오른쪽: 상세 정보 -->
                <div class="profile-main">
                    <?php if ($editMode): ?>
                        <!-- 프로필 수정 폼 -->
                        <div class="card">
                            <div class="card-header">
                                <h3>프로필 정보 수정</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php" class="form" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="form-group">
                                        <label for="name">이름</label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            required
                                            value="<?= e($userName) ?>"
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label for="profile_picture">프로필 사진</label>
                                        <input
                                            type="file"
                                            id="profile_picture"
                                            name="profile_picture"
                                            accept="image/jpeg,image/png,image/gif"
                                        >
                                        <small>JPG, PNG, GIF 형식 (최대 2MB)</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">이메일 (변경 불가)</label>
                                        <input
                                            type="email"
                                            id="email"
                                            value="<?= e($user['email']) ?>"
                                            disabled
                                        >
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">저장</button>
                                        <a href="profile.php" class="btn btn-secondary">취소</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- 비밀번호 변경 -->
                        <div class="card">
                            <div class="card-header">
                                <h3>비밀번호 변경</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php?edit=true" class="form">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="form-group">
                                        <label for="current_password">현재 비밀번호</label>
                                        <input
                                            type="password"
                                            id="current_password"
                                            name="current_password"
                                            required
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label for="new_password">새 비밀번호</label>
                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            required
                                            minlength="6"
                                        >
                                        <small>최소 6자 이상</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password">새 비밀번호 확인</label>
                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            required
                                            minlength="6"
                                        >
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">비밀번호 변경</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- 카테고리별 통계 -->
                        <div class="card">
                            <div class="card-header">
                                <h3>📈 카테고리별 목표</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($categoryStats)): ?>
                                    <p class="text-muted">아직 목표가 없습니다.</p>
                                <?php else: ?>
                                    <div class="category-stats-grid">
                                        <?php foreach ($categoryStats as $cat => $count): ?>
                                            <div class="category-stat-item">
                                                <span class="category-name"><?= e(getCategoryName($cat)) ?></span>
                                                <span class="category-count"><?= $count ?>개</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 최근 댓글 -->
                        <div class="card">
                            <div class="card-header">
                                <h3>💬 최근 작성한 댓글</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentComments)): ?>
                                    <p class="text-muted">아직 작성한 댓글이 없습니다.</p>
                                <?php else: ?>
                                    <div class="recent-comments-list">
                                        <?php foreach ($recentComments as $comment): ?>
                                            <div class="recent-comment-item">
                                                <div class="comment-meta">
                                                    <strong><?= e($comment['goal_title']) ?></strong>
                                                    <span class="comment-date"><?= formatDate($comment['created_at'], 'Y-m-d H:i') ?></span>
                                                </div>
                                                <p class="comment-text"><?= e(mb_substr($comment['content'], 0, 100)) ?><?= mb_strlen($comment['content']) > 100 ? '...' : '' ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 목표 현황 -->
                        <div class="card">
                            <div class="card-header">
                                <h3>🎯 <?= $currentYear ?>년 목표 현황</h3>
                            </div>
                            <div class="card-body">
                                <div class="goal-status-chart">
                                    <div class="status-item">
                                        <div class="status-bar status-completed" style="width: <?= $totalGoals > 0 ? ($completedGoals / $totalGoals * 100) : 0 ?>%"></div>
                                        <span class="status-label">완료: <?= $completedGoals ?>개</span>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-bar status-in-progress" style="width: <?= $totalGoals > 0 ? ($inProgressGoals / $totalGoals * 100) : 0 ?>%"></div>
                                        <span class="status-label">진행중: <?= $inProgressGoals ?>개</span>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-bar status-not-started" style="width: <?= $totalGoals > 0 ? ($notStartedGoals / $totalGoals * 100) : 0 ?>%"></div>
                                        <span class="status-label">시작 전: <?= $notStartedGoals ?>개</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
