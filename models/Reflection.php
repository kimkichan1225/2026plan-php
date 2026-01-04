<?php

require_once __DIR__ . '/../config/database.php';

/**
 * UserReflection 모델 클래스
 */
class UserReflection
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 회고 생성
     */
    public function create(int $userId, ?int $goalId, int $year, int $month, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reflections (user_id, goal_id, year, month, content)
             VALUES (:user_id, :goal_id, :year, :month, :content)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'goal_id' => $goalId,
            'year' => $year,
            'month' => $month,
            'content' => $content,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 회고 조회 by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM reflections WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $reflection = $stmt->fetch();
        return $reflection ?: null;
    }

    /**
     * 사용자의 특정 월 회고 조회
     */
    public function findByUserAndMonth(int $userId, int $year, int $month): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, g.title as goal_title
             FROM reflections r
             LEFT JOIN goals g ON r.goal_id = g.id
             WHERE r.user_id = :user_id AND r.year = :year AND r.month = :month
             ORDER BY r.created_at DESC'
        );

        $stmt->execute([
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * 특정 목표에 대한 회고 조회
     */
    public function findByGoal(int $goalId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM reflections
             WHERE goal_id = :goal_id
             ORDER BY year DESC, month DESC'
        );

        $stmt->execute(['goal_id' => $goalId]);

        return $stmt->fetchAll();
    }

    /**
     * 사용자의 전체 회고 조회
     */
    public function findByUser(int $userId, ?int $year = null): array
    {
        $sql = 'SELECT r.*, g.title as goal_title
                FROM reflections r
                LEFT JOIN goals g ON r.goal_id = g.id
                WHERE r.user_id = :user_id';

        $params = ['user_id' => $userId];

        if ($year !== null) {
            $sql .= ' AND r.year = :year';
            $params['year'] = $year;
        }

        $sql .= ' ORDER BY r.year DESC, r.month DESC, r.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * 회고 업데이트
     */
    public function update(int $id, string $content): bool
    {
        $stmt = $this->db->prepare('UPDATE reflections SET content = :content WHERE id = :id');

        return $stmt->execute([
            'content' => $content,
            'id' => $id,
        ]);
    }

    /**
     * 회고 삭제
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM reflections WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * 특정 회고가 존재하는지 확인
     */
    public function exists(int $userId, ?int $goalId, int $year, int $month): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reflections
             WHERE user_id = :user_id AND goal_id <=> :goal_id AND year = :year AND month = :month'
        );

        $stmt->execute([
            'user_id' => $userId,
            'goal_id' => $goalId,
            'year' => $year,
            'month' => $month,
        ]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * 연말 요약 회고 조회 (12월 + 목표 없음)
     */
    public function findYearSummary(int $userId, int $year): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM reflections
             WHERE user_id = :user_id AND year = :year AND month = 12 AND goal_id IS NULL
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'year' => $year,
        ]);

        $reflection = $stmt->fetch();
        return $reflection ?: null;
    }
}
