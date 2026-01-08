<?php

require_once __DIR__ . '/../config/database.php';

/**
 * GoalLike 모델 클래스
 */
class GoalLike
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 좋아요 추가 (토글)
     * 이미 좋아요한 경우 삭제, 아닌 경우 추가
     */
    public function toggle(int $goalId, int $userId): bool
    {
        if ($this->isLiked($goalId, $userId)) {
            return $this->unlike($goalId, $userId);
        } else {
            return $this->like($goalId, $userId);
        }
    }

    /**
     * 좋아요 추가
     */
    public function like(int $goalId, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            // 좋아요 추가
            $stmt = $this->db->prepare(
                'INSERT INTO goal_likes (goal_id, user_id) VALUES (:goal_id, :user_id)'
            );
            $stmt->execute([
                'goal_id' => $goalId,
                'user_id' => $userId,
            ]);

            // goals 테이블의 likes 카운트 증가
            $stmt = $this->db->prepare(
                'UPDATE goals SET likes = likes + 1 WHERE id = :goal_id'
            );
            $stmt->execute(['goal_id' => $goalId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * 좋아요 취소
     */
    public function unlike(int $goalId, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            // 좋아요 삭제
            $stmt = $this->db->prepare(
                'DELETE FROM goal_likes WHERE goal_id = :goal_id AND user_id = :user_id'
            );
            $stmt->execute([
                'goal_id' => $goalId,
                'user_id' => $userId,
            ]);

            // goals 테이블의 likes 카운트 감소
            $stmt = $this->db->prepare(
                'UPDATE goals SET likes = GREATEST(0, likes - 1) WHERE id = :goal_id'
            );
            $stmt->execute(['goal_id' => $goalId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * 특정 사용자가 목표에 좋아요를 눌렀는지 확인
     */
    public function isLiked(int $goalId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM goal_likes WHERE goal_id = :goal_id AND user_id = :user_id'
        );
        $stmt->execute([
            'goal_id' => $goalId,
            'user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * 목표의 좋아요 수 조회
     */
    public function getLikeCount(int $goalId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM goal_likes WHERE goal_id = :goal_id'
        );
        $stmt->execute(['goal_id' => $goalId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 목표에 좋아요를 누른 사용자 목록
     */
    public function getLikedUsers(int $goalId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, gl.created_at
             FROM goal_likes gl
             INNER JOIN users u ON gl.user_id = u.id
             WHERE gl.goal_id = :goal_id
             ORDER BY gl.created_at DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
