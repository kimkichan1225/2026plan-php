<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Goal.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();

$goalModel = new Goal();

// 필터링 및 정렬 파라미터
$category = $_GET['category'] ?? null;
$orderBy = $_GET['order'] ?? 'latest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// 공개 목표 조회
$goals = $goalModel->findPublicGoals($category, $orderBy, $perPage, $offset);
$totalGoals = $goalModel->countPublicGoals($category);
$totalPages = ceil($totalGoals / $perPage);

// 카테고리 목록
$categories = [
    'career' => '커리어',
    'health' => '건강',
    'study' => '학습',
    'finance' => '재정',
    'hobby' => '취미',
    'relationship' => '관계',
    'other' => '기타',
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커뮤니티 - 신년계획 관리</title>
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
                    <a href="community.php" class="nav-link active">커뮤니티</a>
                    <a href="reflection.php" class="nav-link">회고</a>
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
                <div>
                    <h2>🌐 커뮤니티</h2>
                    <p class="text-muted">다른 사람들의 목표를 확인하고 영감을 받아보세요</p>
                </div>
            </div>

            <!-- 필터 및 정렬 -->
            <div class="community-filters">
                <div class="filter-section">
                    <label>카테고리:</label>
                    <div class="filter-buttons">
                        <a href="community.php?order=<?= e($orderBy) ?>"
                           class="filter-btn <?= $category === null ? 'active' : '' ?>">
                            전체
                        </a>
                        <?php foreach ($categories as $key => $label): ?>
                            <a href="community.php?category=<?= $key ?>&order=<?= e($orderBy) ?>"
                               class="filter-btn <?= $category === $key ? 'active' : '' ?>">
                                <?= e($label) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <label>정렬:</label>
                    <div class="sort-buttons">
                        <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=latest"
                           class="sort-btn <?= $orderBy === 'latest' ? 'active' : '' ?>">
                            최신순
                        </a>
                        <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=popular"
                           class="sort-btn <?= $orderBy === 'popular' ? 'active' : '' ?>">
                            인기순
                        </a>
                        <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=views"
                           class="sort-btn <?= $orderBy === 'views' ? 'active' : '' ?>">
                            조회순
                        </a>
                        <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=progress"
                           class="sort-btn <?= $orderBy === 'progress' ? 'active' : '' ?>">
                            진행률순
                        </a>
                    </div>
                </div>
            </div>

            <!-- 목표 그리드 -->
            <div class="community-stats">
                <p>총 <strong><?= number_format($totalGoals) ?></strong>개의 공개 목표</p>
            </div>

            <?php if (empty($goals)): ?>
                <div class="empty-state">
                    <p>아직 공개된 목표가 없습니다.</p>
                    <a href="goal_list.php?action=create" class="btn btn-primary">첫 목표를 공유해보세요</a>
                </div>
            <?php else: ?>
                <div class="goal-grid">
                    <?php foreach ($goals as $goal): ?>
                        <div class="community-goal-card">
                            <div class="goal-card-header">
                                <?php if (isset($goal['priority'])): ?>
                                    <?= getPriorityBadge($goal['priority']) ?>
                                <?php endif; ?>
                                <span class="badge badge-category"><?= e(getCategoryName($goal['category'])) ?></span>
                                <?= getStatusBadge($goal['status']) ?>
                            </div>

                            <h3 class="goal-title">
                                <a href="goal_detail.php?id=<?= $goal['id'] ?>">
                                    <?= e($goal['title']) ?>
                                </a>
                            </h3>

                            <p class="goal-description">
                                <?= e(mb_substr($goal['description'] ?? '', 0, 100)) ?>
                                <?= mb_strlen($goal['description'] ?? '') > 100 ? '...' : '' ?>
                            </p>

                            <div class="goal-progress">
                                <div class="progress-bar">
                                    <div
                                        class="progress-fill <?= getProgressColorClass($goal['progress_percentage']) ?>"
                                        style="width: <?= $goal['progress_percentage'] ?>%"
                                    ></div>
                                </div>
                                <span class="progress-label"><?= $goal['progress_percentage'] ?>% 완료</span>
                            </div>

                            <div class="goal-meta">
                                <span class="goal-author">👤 <?= e($goal['user_name']) ?></span>
                                <span class="goal-stats">
                                    <span>👁️ <?= number_format($goal['views']) ?></span>
                                    <span>❤️ <?= number_format($goal['likes']) ?></span>
                                </span>
                            </div>

                            <div class="goal-footer">
                                <span class="goal-date"><?= formatDate($goal['created_at'], 'Y-m-d') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=<?= e($orderBy) ?>&page=<?= $page - 1 ?>"
                               class="pagination-btn">
                                이전
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=<?= e($orderBy) ?>&page=<?= $i ?>"
                               class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="community.php?<?= $category ? 'category=' . e($category) . '&' : '' ?>order=<?= e($orderBy) ?>&page=<?= $page + 1 ?>"
                               class="pagination-btn">
                                다음
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
