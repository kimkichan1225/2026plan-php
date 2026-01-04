<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Goal.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$currentYear = date('Y');

$goalModel = new Goal();

// 전체 목표 조회
$goals = $goalModel->findByUser($userId, $currentYear);

// 상태별 집계
$statusCounts = $goalModel->countByStatus($userId);
$totalGoals = count($goals);
$completedGoals = $statusCounts['completed'] ?? 0;
$inProgressGoals = $statusCounts['in_progress'] ?? 0;
$notStartedGoals = $statusCounts['not_started'] ?? 0;

// 카테고리별 집계
$categoryCounts = $goalModel->countByCategory($userId);

// 평균 진행률 계산
$totalProgress = 0;
foreach ($goals as $goal) {
    $totalProgress += $goal['progress_percentage'];
}
$avgProgress = $totalGoals > 0 ? round($totalProgress / $totalGoals, 2) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대시보드 - 신년계획 관리</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <header class="header">
            <div class="header-content">
                <h1 class="logo">신년계획 관리</h1>
                <nav class="nav">
                    <a href="dashboard.php" class="nav-link active">대시보드</a>
                    <a href="goal_list.php" class="nav-link">목표 관리</a>
                    <a href="reflection.php" class="nav-link">회고</a>
                    <span class="user-info">안녕하세요, <?= e($userName) ?>님</span>
                    <a href="logout.php" class="btn btn-sm btn-secondary">로그아웃</a>
                </nav>
            </div>
        </header>

        <!-- 메인 컨텐츠 -->
        <main class="main-content">
            <div class="page-header">
                <h2><?= $currentYear ?>년 목표 대시보드</h2>
                <a href="goal_list.php?action=create" class="btn btn-primary">새 목표 추가</a>
            </div>

            <!-- 통계 카드 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-total">📊</div>
                    <div class="stat-content">
                        <h3>전체 목표</h3>
                        <p class="stat-number"><?= $totalGoals ?>개</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-complete">✅</div>
                    <div class="stat-content">
                        <h3>완료된 목표</h3>
                        <p class="stat-number"><?= $completedGoals ?>개</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-progress">🚀</div>
                    <div class="stat-content">
                        <h3>진행 중</h3>
                        <p class="stat-number"><?= $inProgressGoals ?>개</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-avg">📈</div>
                    <div class="stat-content">
                        <h3>평균 진행률</h3>
                        <p class="stat-number"><?= $avgProgress ?>%</p>
                    </div>
                </div>
            </div>

            <!-- 카테고리별 분포 -->
            <div class="dashboard-section">
                <h3>카테고리별 목표 분포</h3>
                <div class="category-grid">
                    <?php if (empty($categoryCounts)): ?>
                        <p class="empty-state">등록된 목표가 없습니다.</p>
                    <?php else: ?>
                        <?php foreach ($categoryCounts as $category => $count): ?>
                            <div class="category-card">
                                <span class="category-name"><?= e(getCategoryName($category)) ?></span>
                                <span class="category-count"><?= $count ?>개</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 최근 목표 목록 -->
            <div class="dashboard-section">
                <h3>최근 목표</h3>
                <?php if (empty($goals)): ?>
                    <div class="empty-state">
                        <p>아직 목표가 없습니다.</p>
                        <a href="goal_list.php?action=create" class="btn btn-primary">첫 목표 만들기</a>
                    </div>
                <?php else: ?>
                    <div class="goal-list">
                        <?php foreach (array_slice($goals, 0, 5) as $goal): ?>
                            <div class="goal-item">
                                <div class="goal-item-header">
                                    <h4>
                                        <a href="goal_detail.php?id=<?= $goal['id'] ?>">
                                            <?= e($goal['title']) ?>
                                        </a>
                                    </h4>
                                    <div class="goal-meta">
                                        <span class="badge badge-category"><?= e(getCategoryName($goal['category'])) ?></span>
                                        <?= getStatusBadge($goal['status']) ?>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div
                                        class="progress-fill <?= getProgressColorClass($goal['progress_percentage']) ?>"
                                        style="width: <?= $goal['progress_percentage'] ?>%"
                                    ></div>
                                </div>
                                <p class="progress-text"><?= $goal['progress_percentage'] ?>% 완료</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($goals) > 5): ?>
                        <div class="text-center">
                            <a href="goal_list.php" class="btn btn-secondary">모든 목표 보기</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
