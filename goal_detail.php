<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Goal.php';
require_once __DIR__ . '/models/GoalPlan.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$goalId = (int) ($_GET['id'] ?? 0);

// 계획 업데이트 처리 (AJAX) - 반드시 다른 출력 전에 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 이전 출력 버퍼 클리어
    if (ob_get_level()) {
        ob_clean();
    }

    header('Content-Type: application/json');

    try {
        $planModel = new GoalPlan();
        $goalModel = new Goal();

        if ($_POST['action'] === 'update_plan') {
            $planId = (int) ($_POST['plan_id'] ?? 0);
            $planTitle = trim($_POST['plan_title'] ?? '');
            $planDescription = trim($_POST['plan_description'] ?? '');

            if (!$planId) {
                echo json_encode(['success' => false, 'error' => 'Invalid plan ID']);
                exit;
            }

            $result = $planModel->update($planId, [
                'plan_title' => $planTitle,
                'plan_description' => $planDescription,
            ]);

            echo json_encode(['success' => $result]);
            exit;
        }

        if ($_POST['action'] === 'toggle_complete') {
            $planId = (int) ($_POST['plan_id'] ?? 0);

            if (!$planId) {
                echo json_encode(['success' => false, 'error' => 'Invalid plan ID']);
                exit;
            }

            // plan에서 goal_id를 가져와서 권한 확인
            $plan = $planModel->findById($planId);

            if (!$plan) {
                echo json_encode(['success' => false, 'error' => 'Plan not found']);
                exit;
            }

            $planGoalId = $plan['goal_id'];
            $planGoal = $goalModel->findById($planGoalId);

            // 권한 확인: 해당 목표의 소유자인지 확인
            if (!$planGoal || $planGoal['user_id'] !== $userId) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }

            $result = $planModel->toggleComplete($planId);

            if ($result) {
                // 진행률 재계산
                $goalModel->updateProgress($planGoalId);
            }

            echo json_encode(['success' => $result]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;

    } catch (Exception $e) {
        error_log('Error in goal_detail.php: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

if (!$goalId) {
    redirect('goal_list.php');
}

$goalModel = new Goal();
$goal = $goalModel->findWithPlans($goalId);

// 목표가 없는 경우
if (!$goal) {
    redirect('goal_list.php');
}

// 권한 확인
$isOwner = $goal['user_id'] === $userId;
$isPublic = $goal['visibility'] === 'public';

// 비공개 목표는 소유자만 볼 수 있음
if (!$isPublic && !$isOwner) {
    redirect('community.php');
}

// 공개 목표를 다른 사람이 볼 때 조회수 증가
if ($isPublic && !$isOwner) {
    $goalModel->incrementViews($goalId);
    // 조회수 증가 후 다시 조회하여 최신 데이터 반영
    $goal = $goalModel->findWithPlans($goalId);
}

$planModel = new GoalPlan();

$quarterNames = [1 => '1분기 (1~3월)', 2 => '2분기 (4~6월)', 3 => '3분기 (7~9월)', 4 => '4분기 (10~12월)'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($goal['title']) ?> - 신년계획 관리</title>
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
            <!-- 목표 헤더 -->
            <?php if (!$isOwner): ?>
                <div class="public-goal-notice">
                    <span>👤 <?= e($goal['user_name']) ?>님의 공개 목표</span>
                    <span>👁️ 조회수 <?= number_format($goal['views']) ?></span>
                </div>
            <?php endif; ?>

            <div class="goal-detail-header">
                <div class="goal-detail-info">
                    <div class="goal-meta-row">
                        <?php if (isset($goal['priority'])): ?>
                            <?= getPriorityBadge($goal['priority']) ?>
                        <?php endif; ?>
                        <span class="badge badge-category"><?= e(getCategoryName($goal['category'])) ?></span>
                        <?= getStatusBadge($goal['status']) ?>
                        <span class="goal-year"><?= $goal['year'] ?>년</span>
                        <?php if ($isPublic): ?>
                            <span class="badge badge-public">🌐 공개</span>
                        <?php endif; ?>
                    </div>
                    <h2><?= e($goal['title']) ?></h2>
                    <?php if ($goal['description']): ?>
                        <p class="goal-description"><?= nl2br(e($goal['description'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="goal-detail-progress">
                    <div class="circular-progress">
                        <div class="progress-value"><?= $goal['progress_percentage'] ?>%</div>
                    </div>
                    <p>전체 진행률</p>
                </div>
            </div>

            <!-- 분기별 계획 -->
            <div class="quarters-container">
                <?php foreach ([1, 2, 3, 4] as $quarter): ?>
                    <div class="quarter-section">
                        <h3 class="quarter-title"><?= $quarterNames[$quarter] ?></h3>

                        <?php
                        $quarterPlans = $goal['quarter_plans'][$quarter] ?? [];
                        $quarterProgress = $planModel->getQuarterProgress($goalId, $quarter);
                        ?>

                        <div class="quarter-progress-info">
                            <span><?= $quarterProgress['completed'] ?> / <?= $quarterProgress['total'] ?> 완료</span>
                            <span class="quarter-progress-percent"><?= $quarterProgress['progress'] ?>%</span>
                        </div>

                        <div class="plans-list">
                            <?php foreach ($quarterPlans as $plan): ?>
                                <div class="plan-item <?= $plan['is_completed'] ? 'completed' : '' ?>" data-plan-id="<?= $plan['id'] ?>">
                                    <?php if ($isOwner): ?>
                                        <div class="plan-checkbox">
                                            <input
                                                type="checkbox"
                                                class="plan-toggle"
                                                <?= $plan['is_completed'] ? 'checked' : '' ?>
                                                data-plan-id="<?= $plan['id'] ?>"
                                            >
                                        </div>
                                    <?php else: ?>
                                        <div class="plan-checkbox">
                                            <span class="plan-status-icon">
                                                <?= $plan['is_completed'] ? '✅' : '⬜' ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="plan-content">
                                        <div class="plan-header">
                                            <strong><?= $plan['month'] ?>월</strong>
                                            <?php if ($plan['completed_at']): ?>
                                                <span class="plan-completed-date">
                                                    <?= formatDate($plan['completed_at'], 'Y-m-d') ?> 완료
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="plan-title">
                                            <?= e($plan['plan_title'] ?: "{$plan['month']}월 계획") ?>
                                        </div>
                                        <?php if ($plan['plan_description']): ?>
                                            <p class="plan-description"><?= nl2br(e($plan['plan_description'])) ?></p>
                                        <?php endif; ?>
                                        <?php if ($isOwner): ?>
                                            <button
                                                class="btn-edit-plan"
                                                data-plan-id="<?= $plan['id'] ?>"
                                                data-plan-title="<?= e($plan['plan_title']) ?>"
                                                data-plan-description="<?= e($plan['plan_description']) ?>"
                                            >편집</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="goal-actions">
                <?php if ($isOwner): ?>
                    <a href="goal_list.php" class="btn btn-secondary">목록으로</a>
                <?php else: ?>
                    <a href="community.php" class="btn btn-secondary">커뮤니티로</a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- 계획 편집 모달 -->
    <div id="editPlanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>계획 편집</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editPlanForm">
                    <input type="hidden" id="edit_plan_id" name="plan_id">

                    <div class="form-group">
                        <label for="edit_plan_title">계획 제목</label>
                        <input
                            type="text"
                            id="edit_plan_title"
                            name="plan_title"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="edit_plan_description">계획 설명</label>
                        <textarea
                            id="edit_plan_description"
                            name="plan_description"
                            rows="5"
                        ></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">저장</button>
                        <button type="button" class="btn btn-secondary modal-close">취소</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
