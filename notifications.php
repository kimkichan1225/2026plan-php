<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Notification.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();

$notificationModel = new Notification();

// 알림 읽음 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_as_read' && isset($_POST['notification_id'])) {
        $notificationModel->markAsRead((int) $_POST['notification_id'], $userId);
        header('Location: notifications.php');
        exit;
    } elseif ($_POST['action'] === 'mark_all_as_read') {
        $notificationModel->markAllAsRead($userId);
        header('Location: notifications.php');
        exit;
    } elseif ($_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
        $notificationModel->delete((int) $_POST['notification_id'], $userId);
        header('Location: notifications.php');
        exit;
    }
}

// 필터
$filter = $_GET['filter'] ?? 'all'; // all, unread, read

$isReadFilter = null;
if ($filter === 'unread') {
    $isReadFilter = false;
} elseif ($filter === 'read') {
    $isReadFilter = true;
}

// 알림 목록 조회
$notifications = $notificationModel->findByUser($userId, $isReadFilter, 50);
$unreadCount = $notificationModel->getUnreadCount($userId);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알림 - 신년계획 관리</title>
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
                    <a href="notifications.php" class="nav-link active">
                        알림
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?= $unreadCount ?></span>
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
                <h2>🔔 알림</h2>
                <?php if ($unreadCount > 0): ?>
                    <form method="POST" action="notifications.php" style="display: inline;">
                        <input type="hidden" name="action" value="mark_all_as_read">
                        <button type="submit" class="btn btn-secondary btn-sm">모두 읽음 처리</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- 필터 -->
            <div class="notification-filters">
                <a href="notifications.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                    전체 (<?= count($notifications) ?>)
                </a>
                <a href="notifications.php?filter=unread" class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">
                    읽지 않음 (<?= $unreadCount ?>)
                </a>
                <a href="notifications.php?filter=read" class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">
                    읽음
                </a>
            </div>

            <!-- 알림 목록 -->
            <div class="notifications-container">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <p>알림이 없습니다.</p>
                    </div>
                <?php else: ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>">
                                <div class="notification-avatar">
                                    <?php if ($notification['actor_profile_picture']): ?>
                                        <img src="uploads/profiles/<?= e($notification['actor_profile_picture']) ?>" alt="">
                                    <?php elseif ($notification['actor_name']): ?>
                                        <?= strtoupper(mb_substr($notification['actor_name'], 0, 1)) ?>
                                    <?php else: ?>
                                        🔔
                                    <?php endif; ?>
                                </div>

                                <div class="notification-content">
                                    <p class="notification-message">
                                        <?= e($notification['message']) ?>
                                    </p>
                                    <span class="notification-time">
                                        <?= formatDate($notification['created_at'], 'Y-m-d H:i') ?>
                                    </span>

                                    <div class="notification-actions">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" action="notifications.php" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_as_read">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" class="btn-link">읽음 처리</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($notification['goal_id']): ?>
                                            <a href="goal_detail.php?id=<?= $notification['goal_id'] ?>" class="btn-link">목표 보기</a>
                                        <?php endif; ?>

                                        <?php if ($notification['actor_id']): ?>
                                            <a href="user_profile.php?id=<?= $notification['actor_id'] ?>" class="btn-link">프로필 보기</a>
                                        <?php endif; ?>

                                        <form method="POST" action="notifications.php" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                            <button type="submit" class="btn-link btn-link-danger" onclick="return confirm('이 알림을 삭제하시겠습니까?')">삭제</button>
                                        </form>
                                    </div>
                                </div>

                                <?php if (!$notification['is_read']): ?>
                                    <div class="notification-indicator"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
