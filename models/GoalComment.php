<?php

require_once __DIR__ . '/../config/database.php';

/**
 * GoalComment 모델 클래스
 */
class GoalComment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 댓글 작성
     */
    public function create(int $goalId, int $userId, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO goal_comments (goal_id, user_id, content)
             VALUES (:goal_id, :user_id, :content)'
        );

        $stmt->execute([
            'goal_id' => $goalId,
            'user_id' => $userId,
            'content' => $content,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 목표의 댓글 목록 조회
     */
    public function findByGoal(int $goalId, string $orderBy = 'latest'): array
    {
        $sql = 'SELECT gc.*, u.name as user_name
                FROM goal_comments gc
                INNER JOIN users u ON gc.user_id = u.id
                WHERE gc.goal_id = :goal_id';

        if ($orderBy === 'oldest') {
            $sql .= ' ORDER BY gc.created_at ASC';
        } else {
            $sql .= ' ORDER BY gc.created_at DESC';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['goal_id' => $goalId]);

        return $stmt->fetchAll();
    }

    /**
     * 댓글 ID로 조회
     */
    public function findById(int $commentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM goal_comments WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $commentId]);

        $comment = $stmt->fetch();
        return $comment ?: null;
    }

    /**
     * 댓글 수정
     */
    public function update(int $commentId, string $content): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE goal_comments SET content = :content WHERE id = :id'
        );

        return $stmt->execute([
            'content' => $content,
            'id' => $commentId,
        ]);
    }

    /**
     * 댓글 삭제
     */
    public function delete(int $commentId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM goal_comments WHERE id = :id');
        return $stmt->execute(['id' => $commentId]);
    }

    /**
     * 목표의 댓글 수 조회
     */
    public function getCommentCount(int $goalId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM goal_comments WHERE goal_id = :goal_id'
        );
        $stmt->execute(['goal_id' => $goalId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 사용자의 댓글 목록
     */
    public function findByUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT gc.*, g.title as goal_title
             FROM goal_comments gc
             INNER JOIN goals g ON gc.goal_id = g.id
             WHERE gc.user_id = :user_id
             ORDER BY gc.created_at DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
