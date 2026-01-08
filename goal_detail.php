<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Goal.php';
require_once __DIR__ . '/models/GoalPlan.php';
require_once __DIR__ . '/models/GoalLike.php';
require_once __DIR__ . '/models/GoalComment.php';

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

        if ($_POST['action'] === 'like_toggle') {
            $likeGoalId = (int) ($_POST['goal_id'] ?? 0);

            if (!$likeGoalId) {
                echo json_encode(['success' => false, 'error' => 'Invalid goal ID']);
                exit;
            }

            // 공개 목표인지 확인
            $likeGoal = $goalModel->findById($likeGoalId);
            if (!$likeGoal || ($likeGoal['visibility'] !== 'public' && $likeGoal['user_id'] !== $userId)) {
                echo json_encode(['success' => false, 'error' => 'Goal not accessible']);
                exit;
            }

            $likeModel = new GoalLike();
            $result = $likeModel->toggle($likeGoalId, $userId);

            if ($result) {
                $likeCount = $likeModel->getLikeCount($likeGoalId);
                $isLiked = $likeModel->isLiked($likeGoalId, $userId);

                echo json_encode([
                    'success' => true,
                    'like_count' => $likeCount,
                    'is_liked' => $isLiked
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to toggle like']);
            }
            exit;
        }

        if ($_POST['action'] === 'add_comment') {
            $commentGoalId = (int) ($_POST['goal_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');

            if (!$commentGoalId) {
                echo json_encode(['success' => false, 'error' => 'Invalid goal ID']);
                exit;
            }

            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => 'Comment content is required']);
                exit;
            }

            // 공개 목표인지 확인
            $commentGoal = $goalModel->findById($commentGoalId);
            if (!$commentGoal || ($commentGoal['visibility'] !== 'public' && $commentGoal['user_id'] !== $userId)) {
                echo json_encode(['success' => false, 'error' => 'Goal not accessible']);
                exit;
            }

            $commentModel = new GoalComment();
            $commentId = $commentModel->create($commentGoalId, $userId, $content);

            if ($commentId) {
                echo json_encode([
                    'success' => true,
                    'comment_id' => $commentId,
                    'user_name' => $userName,
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
            }
            exit;
        }

        if ($_POST['action'] === 'delete_comment') {
            $commentId = (int) ($_POST['comment_id'] ?? 0);

            if (!$commentId) {
                echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
                exit;
            }

            $commentModel = new GoalComment();
            $comment = $commentModel->findById($commentId);

            // 권한 확인: 댓글 작성자만 삭제 가능
            if (!$comment || $comment['user_id'] !== $userId) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }

            $result = $commentModel->delete($commentId);
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

// 좋아요/댓글 데이터 로드
$likeModel = new GoalLike();
$commentModel = new GoalComment();

$isLiked = $likeModel->isLiked($goalId, $userId);
$likeCount = $likeModel->getLikeCount($goalId);
$comments = $commentModel->findByGoal($goalId, 'latest');

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

            <!-- 좋아요 및 댓글 섹션 (공개 목표만) -->
            <?php if ($isPublic): ?>
                <!-- 좋아요 버튼 -->
                <div class="goal-interactions">
                    <button
                        id="likeButton"
                        class="btn-like <?= $isLiked ? 'liked' : '' ?>"
                        data-goal-id="<?= $goalId ?>"
                    >
                        <span class="like-icon"><?= $isLiked ? '❤️' : '🤍' ?></span>
                        <span class="like-text"><?= $isLiked ? '좋아요 취소' : '좋아요' ?></span>
                        <span class="like-count"><?= number_format($likeCount) ?></span>
                    </button>
                </div>

                <!-- 댓글 섹션 -->
                <div class="comments-section">
                    <h3 class="comments-title">
                        💬 댓글 <span class="comment-count"><?= count($comments) ?></span>
                    </h3>

                    <!-- 댓글 작성 폼 -->
                    <div class="comment-form">
                        <textarea
                            id="commentContent"
                            placeholder="응원의 댓글을 남겨보세요..."
                            rows="3"
                        ></textarea>
                        <button id="submitComment" class="btn btn-primary">댓글 작성</button>
                    </div>

                    <!-- 댓글 목록 -->
                    <div id="commentsList" class="comments-list">
                        <?php if (empty($comments)): ?>
                            <p class="no-comments">아직 댓글이 없습니다. 첫 댓글을 남겨보세요!</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item" data-comment-id="<?= $comment['id'] ?>">
                                    <div class="comment-header">
                                        <span class="comment-author">👤 <?= e($comment['user_name']) ?></span>
                                        <span class="comment-date"><?= formatDate($comment['created_at'], 'Y-m-d H:i') ?></span>
                                    </div>
                                    <div class="comment-content"><?= nl2br(e($comment['content'])) ?></div>
                                    <?php if ($comment['user_id'] === $userId): ?>
                                        <button
                                            class="btn-delete-comment"
                                            data-comment-id="<?= $comment['id'] ?>"
                                        >삭제</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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
    <script>
        // 좋아요 버튼 클릭 처리
        const likeButton = document.getElementById('likeButton');
        if (likeButton) {
            likeButton.addEventListener('click', async function() {
                const goalId = this.dataset.goalId;

                try {
                    const response = await fetch('goal_detail.php?id=' + goalId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=like_toggle&goal_id=' + goalId
                    });

                    const data = await response.json();

                    if (data.success) {
                        const likeIcon = this.querySelector('.like-icon');
                        const likeText = this.querySelector('.like-text');
                        const likeCount = this.querySelector('.like-count');

                        if (data.is_liked) {
                            this.classList.add('liked');
                            likeIcon.textContent = '❤️';
                            likeText.textContent = '좋아요 취소';
                        } else {
                            this.classList.remove('liked');
                            likeIcon.textContent = '🤍';
                            likeText.textContent = '좋아요';
                        }

                        likeCount.textContent = data.like_count.toLocaleString();
                    } else {
                        alert('좋아요 처리 중 오류가 발생했습니다: ' + (data.error || ''));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('서버 오류가 발생했습니다.');
                }
            });
        }

        // 댓글 작성
        const submitComment = document.getElementById('submitComment');
        const commentContent = document.getElementById('commentContent');
        const commentsList = document.getElementById('commentsList');

        if (submitComment) {
            submitComment.addEventListener('click', async function() {
                const content = commentContent.value.trim();
                const goalId = <?= $goalId ?>;

                if (!content) {
                    alert('댓글 내용을 입력해주세요.');
                    return;
                }

                try {
                    const response = await fetch('goal_detail.php?id=' + goalId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=add_comment&goal_id=' + goalId + '&content=' + encodeURIComponent(content)
                    });

                    const data = await response.json();

                    if (data.success) {
                        // 댓글 추가
                        const noComments = commentsList.querySelector('.no-comments');
                        if (noComments) {
                            noComments.remove();
                        }

                        const commentHtml = `
                            <div class="comment-item" data-comment-id="${data.comment_id}">
                                <div class="comment-header">
                                    <span class="comment-author">👤 ${data.user_name}</span>
                                    <span class="comment-date">${data.created_at}</span>
                                </div>
                                <div class="comment-content">${data.content.replace(/\n/g, '<br>')}</div>
                                <button class="btn-delete-comment" data-comment-id="${data.comment_id}">삭제</button>
                            </div>
                        `;

                        commentsList.insertAdjacentHTML('afterbegin', commentHtml);

                        // 댓글 수 업데이트
                        const commentCount = document.querySelector('.comment-count');
                        commentCount.textContent = parseInt(commentCount.textContent) + 1;

                        // 입력창 초기화
                        commentContent.value = '';
                    } else {
                        alert('댓글 작성 중 오류가 발생했습니다: ' + (data.error || ''));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('서버 오류가 발생했습니다.');
                }
            });
        }

        // 댓글 삭제 (이벤트 위임)
        if (commentsList) {
            commentsList.addEventListener('click', async function(e) {
                if (e.target.classList.contains('btn-delete-comment')) {
                    if (!confirm('정말 댓글을 삭제하시겠습니까?')) {
                        return;
                    }

                    const commentId = e.target.dataset.commentId;
                    const goalId = <?= $goalId ?>;

                    try {
                        const response = await fetch('goal_detail.php?id=' + goalId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=delete_comment&comment_id=' + commentId
                        });

                        const data = await response.json();

                        if (data.success) {
                            // 댓글 제거
                            const commentItem = e.target.closest('.comment-item');
                            commentItem.remove();

                            // 댓글 수 업데이트
                            const commentCount = document.querySelector('.comment-count');
                            const newCount = parseInt(commentCount.textContent) - 1;
                            commentCount.textContent = newCount;

                            // 댓글이 없으면 메시지 표시
                            if (newCount === 0) {
                                commentsList.innerHTML = '<p class="no-comments">아직 댓글이 없습니다. 첫 댓글을 남겨보세요!</p>';
                            }
                        } else {
                            alert('댓글 삭제 중 오류가 발생했습니다: ' + (data.error || ''));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('서버 오류가 발생했습니다.');
                    }
                }
            });
        }
    </script>
</body>
</html>
