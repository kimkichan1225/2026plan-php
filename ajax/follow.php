<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');

// 로그인 확인
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$currentUserId = getCurrentUserId();
$currentUserName = getCurrentUserName();
$action = $_POST['action'] ?? '';
$targetUserId = (int) ($_POST['user_id'] ?? 0);

if (empty($action) || $targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => '잘못된 파라미터입니다.']);
    exit;
}

// 자기 자신을 팔로우하는 것 방지
if ($currentUserId === $targetUserId) {
    echo json_encode(['success' => false, 'message' => '자기 자신을 팔로우할 수 없습니다.']);
    exit;
}

try {
    $followModel = new Follow();
    $notificationModel = new Notification();

    if ($action === 'follow') {
        $success = $followModel->follow($currentUserId, $targetUserId);

        if ($success) {
            // 팔로우 알림 생성
            $notificationModel->createFollowNotification($currentUserId, $targetUserId, $currentUserName);

            echo json_encode([
                'success' => true,
                'action' => 'followed',
                'message' => '팔로우했습니다.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '팔로우에 실패했습니다.'
            ]);
        }
    } elseif ($action === 'unfollow') {
        $success = $followModel->unfollow($currentUserId, $targetUserId);

        if ($success) {
            echo json_encode([
                'success' => true,
                'action' => 'unfollowed',
                'message' => '언팔로우했습니다.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '언팔로우에 실패했습니다.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => '알 수 없는 액션입니다.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
