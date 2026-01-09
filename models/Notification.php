<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Notification 모델 클래스
 */
class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 알림 생성
     */
    public function create(
        int $userId,
        string $type,
        string $message,
        ?int $actorId = null,
        ?int $goalId = null,
        ?int $commentId = null
    ): int {
        // 자기 자신에게는 알림을 보내지 않음
        if ($actorId === $userId) {
            return 0;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, type, message, actor_id, goal_id, comment_id)
             VALUES (:user_id, :type, :message, :actor_id, :goal_id, :comment_id)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'actor_id' => $actorId,
            'goal_id' => $goalId,
            'comment_id' => $commentId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 팔로우 알림 생성
     */
    public function createFollowNotification(int $followerId, int $followingId, string $followerName): int
    {
        return $this->create(
            $followingId,
            'follow',
            "{$followerName}님이 회원님을 팔로우하기 시작했습니다.",
            $followerId
        );
    }

    /**
     * 좋아요 알림 생성
     */
    public function createLikeNotification(int $goalOwnerId, int $likerId, string $likerName, int $goalId, string $goalTitle): int
    {
        return $this->create(
            $goalOwnerId,
            'like',
            "{$likerName}님이 회원님의 목표 '{$goalTitle}'을(를) 좋아합니다.",
            $likerId,
            $goalId
        );
    }

    /**
     * 댓글 알림 생성
     */
    public function createCommentNotification(
        int $goalOwnerId,
        int $commenterId,
        string $commenterName,
        int $goalId,
        string $goalTitle,
        int $commentId
    ): int {
        return $this->create(
            $goalOwnerId,
            'comment',
            "{$commenterName}님이 회원님의 목표 '{$goalTitle}'에 댓글을 남겼습니다.",
            $commenterId,
            $goalId,
            $commentId
        );
    }

    /**
     * 목표 공유 알림 생성 (팔로워들에게)
     */
    public function createGoalSharedNotification(int $goalOwnerId, string $ownerName, int $goalId, string $goalTitle): void
    {
        // 팔로워들 조회
        $stmt = $this->db->prepare(
            'SELECT follower_id FROM user_follows WHERE following_id = :user_id'
        );
        $stmt->execute(['user_id' => $goalOwnerId]);
        $followers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 각 팔로워에게 알림 생성
        foreach ($followers as $followerId) {
            $this->create(
                $followerId,
                'goal_shared',
                "{$ownerName}님이 새로운 목표 '{$goalTitle}'을(를) 공유했습니다.",
                $goalOwnerId,
                $goalId
            );
        }
    }

    /**
     * 사용자의 알림 목록 조회
     */
    public function findByUser(int $userId, ?bool $isRead = null, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT n.*, u.name as actor_name, u.profile_picture as actor_profile_picture
                FROM notifications n
                LEFT JOIN users u ON n.actor_id = u.id
                WHERE n.user_id = :user_id';

        $params = ['user_id' => $userId];

        if ($isRead !== null) {
            $sql .= ' AND n.is_read = :is_read';
            $params['is_read'] = $isRead ? 1 : 0;
        }

        $sql .= ' ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 읽지 않은 알림 수 조회
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = FALSE'
        );

        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 알림을 읽음으로 표시
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id'
        );

        return $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    /**
     * 모든 알림을 읽음으로 표시
     */
    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE'
        );

        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * 알림 삭제
     */
    public function delete(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notifications WHERE id = :id AND user_id = :user_id'
        );

        return $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    /**
     * 오래된 읽은 알림 삭제 (30일 이상)
     */
    public function deleteOldReadNotifications(int $days = 30): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notifications
             WHERE is_read = TRUE
             AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
        );

        $stmt->execute(['days' => $days]);

        return $stmt->rowCount();
    }
}
