<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Goal.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$currentYear = date('Y');

$goalModel = new Goal();

// 목표 생성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'other';
    $priority = $_POST['priority'] ?? 'medium';
    $year = (int) ($_POST['year'] ?? $currentYear);

    if (empty($title)) {
        $error = '목표 제목을 입력해주세요.';
    } else {
        try {
            $goalId = $goalModel->create($userId, $year, $title, $description, $category, $priority);
            redirect("goal_detail.php?id=$goalId");
        } catch (Exception $e) {
            $error = '목표 생성 중 오류가 발생했습니다.';
        }
    }
}

// 목표 목록 조회
$goals = $goalModel->findByUser($userId, $currentYear);

// 생성 폼 표시 여부
$showCreateForm = isset($_GET['action']) && $_GET['action'] === 'create';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>목표 관리 - 신년계획 관리</title>
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
                    <a href="goal_list.php" class="nav-link active">목표 관리</a>
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
                <h2><?= $currentYear ?>년 목표 목록</h2>
                <?php if (!$showCreateForm): ?>
                    <a href="goal_list.php?action=create" class="btn btn-primary">새 목표 추가</a>
                <?php endif; ?>
            </div>

            <!-- 목표 생성 폼 -->
            <?php if ($showCreateForm): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>새 목표 만들기</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="goal_list.php" class="form">
                            <input type="hidden" name="action" value="create">

                            <div class="form-group">
                                <label for="title">목표 제목 *</label>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    required
                                    placeholder="예: PHP 백엔드 마스터하기"
                                    value="<?= e($_POST['title'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="description">목표 설명</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="4"
                                    placeholder="목표에 대한 상세 설명을 입력하세요"
                                ><?= e($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">카테고리</label>
                                    <select id="category" name="category">
                                        <option value="career">커리어</option>
                                        <option value="health">건강</option>
                                        <option value="study">학습</option>
                                        <option value="finance">재정</option>
                                        <option value="hobby">취미</option>
                                        <option value="relationship">관계</option>
                                        <option value="other">기타</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="priority">우선순위</label>
                                    <select id="priority" name="priority">
                                        <option value="high">🔥 높음</option>
                                        <option value="medium" selected>➡️ 보통</option>
                                        <option value="low">⬇️ 낮음</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="year">연도</label>
                                    <input
                                        type="number"
                                        id="year"
                                        name="year"
                                        value="<?= $currentYear ?>"
                                        min="2020"
                                        max="2030"
                                    >
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">목표 생성</button>
                                <a href="goal_list.php" class="btn btn-secondary">취소</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 목표 목록 -->
            <div class="goals-container">
                <?php if (empty($goals)): ?>
                    <div class="empty-state">
                        <p>아직 목표가 없습니다.</p>
                        <a href="goal_list.php?action=create" class="btn btn-primary">첫 목표 만들기</a>
                    </div>
                <?php else: ?>
                    <div class="goal-grid">
                        <?php foreach ($goals as $goal): ?>
                            <div class="goal-card">
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
                                <div class="goal-footer">
                                    <span class="goal-date"><?= formatDate($goal['created_at'], 'Y-m-d') ?></span>
                                    <a href="goal_detail.php?id=<?= $goal['id'] ?>" class="btn btn-sm btn-primary">상세보기</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
