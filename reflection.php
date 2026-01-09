<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Reflection.php';
require_once __DIR__ . '/models/Goal.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$unreadNotifications = getUnreadNotificationCount();
$currentYear = (int) ($_GET['year'] ?? date('Y'));
$currentMonth = (int) ($_GET['month'] ?? date('n'));

$reflectionModel = new UserReflection();
$goalModel = new Goal();

// 회고 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $goalId = !empty($_POST['goal_id']) ? (int) $_POST['goal_id'] : null;
    $year = (int) $_POST['year'];
    $month = (int) $_POST['month'];
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        $error = '회고 내용을 입력해주세요.';
    } elseif ($reflectionModel->exists($userId, $goalId, $year, $month)) {
        $error = '이미 해당 월에 대한 회고가 존재합니다.';
    } else {
        try {
            $reflectionModel->create($userId, $goalId, $year, $month, $content);
            $success = '회고가 저장되었습니다.';
        } catch (Exception $e) {
            $error = '회고 저장 중 오류가 발생했습니다.';
        }
    }
}

// 회고 목록 조회
$reflections = $reflectionModel->findByUserAndMonth($userId, $currentYear, $currentMonth);

// 사용자의 목표 목록 (회고 작성용)
$goals = $goalModel->findByUser($userId, $currentYear);

// 달력 네비게이션
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    1 => '1월', 2 => '2월', 3 => '3월', 4 => '4월',
    5 => '5월', 6 => '6월', 7 => '7월', 8 => '8월',
    9 => '9월', 10 => '10월', 11 => '11월', 12 => '12월'
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회고 - 신년계획 관리</title>
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
                <h2>회고</h2>
            </div>

            <!-- 월 네비게이션 -->
            <div class="month-navigation">
                <a href="reflection.php?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-sm">
                    ← 이전 달
                </a>
                <h3 class="current-month"><?= $currentYear ?>년 <?= $monthNames[$currentMonth] ?></h3>
                <a href="reflection.php?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-sm">
                    다음 달 →
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <!-- 회고 작성 폼 -->
            <div class="card">
                <div class="card-header">
                    <h3>회고 작성하기</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="reflection.php?year=<?= $currentYear ?>&month=<?= $currentMonth ?>" class="form">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="year" value="<?= $currentYear ?>">
                        <input type="hidden" name="month" value="<?= $currentMonth ?>">

                        <div class="form-group">
                            <label for="goal_id">목표 선택 (선택사항)</label>
                            <select id="goal_id" name="goal_id">
                                <option value="">전체 회고</option>
                                <?php foreach ($goals as $goal): ?>
                                    <option value="<?= $goal['id'] ?>">
                                        <?= e($goal['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>특정 목표에 대한 회고를 작성하거나, 전체 회고를 작성할 수 있습니다.</small>
                        </div>

                        <div class="form-group">
                            <label for="content">회고 내용 *</label>
                            <textarea
                                id="content"
                                name="content"
                                rows="8"
                                required
                                placeholder="이번 달의 성과, 배운 점, 개선할 점 등을 자유롭게 작성하세요."
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">회고 저장</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 회고 목록 -->
            <div class="reflections-container">
                <h3><?= $currentYear ?>년 <?= $monthNames[$currentMonth] ?> 회고 목록</h3>

                <?php if (empty($reflections)): ?>
                    <div class="empty-state">
                        <p>이번 달 작성된 회고가 없습니다.</p>
                    </div>
                <?php else: ?>
                    <div class="reflection-list">
                        <?php foreach ($reflections as $reflection): ?>
                            <div class="reflection-item">
                                <div class="reflection-header">
                                    <?php if ($reflection['goal_id']): ?>
                                        <span class="reflection-goal-badge">
                                            목표: <?= e($reflection['goal_title']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="reflection-goal-badge reflection-general">
                                            전체 회고
                                        </span>
                                    <?php endif; ?>
                                    <span class="reflection-date">
                                        <?= formatDate($reflection['created_at'], 'Y-m-d H:i') ?>
                                    </span>
                                </div>
                                <div class="reflection-content">
                                    <?= nl2br(e($reflection['content'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 연말 요약 (12월만 표시) -->
            <?php if ($currentMonth === 12): ?>
                <div class="year-summary-section">
                    <h3><?= $currentYear ?>년 연말 요약</h3>
                    <p class="text-muted">12월에는 한 해를 돌아보는 전체 회고를 작성해보세요.</p>

                    <?php
                    $yearSummary = $reflectionModel->findYearSummary($userId, $currentYear);
                    if ($yearSummary):
                    ?>
                        <div class="year-summary-card">
                            <h4>나의 <?= $currentYear ?>년</h4>
                            <p><?= nl2br(e($yearSummary['content'])) ?></p>
                            <small>작성일: <?= formatDate($yearSummary['created_at'], 'Y-m-d') ?></small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            아직 연말 요약을 작성하지 않았습니다. 위 폼에서 "전체 회고"를 선택하여 작성해주세요.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
