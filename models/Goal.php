<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Goal 모델 클래스
 */
class Goal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 목표 생성 (월별 계획 자동 생성 포함)
     */
    public function create(int $userId, int $year, string $title, ?string $description, string $category): int
    {
        try {
            $this->db->beginTransaction();

            // 목표 생성
            $stmt = $this->db->prepare(
                'INSERT INTO goals (user_id, year, title, description, category, status)
                 VALUES (:user_id, :year, :title, :description, :category, :status)'
            );

            $stmt->execute([
                'user_id' => $userId,
                'year' => $year,
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'status' => 'not_started',
            ]);

            $goalId = (int) $this->db->lastInsertId();

            // 12개월 계획 자동 생성
            $this->createMonthlyPlans($goalId);

            $this->db->commit();

            return $goalId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 월별 계획 자동 생성 (1~12월)
     */
    private function createMonthlyPlans(int $goalId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO goal_plans (goal_id, quarter, month, plan_title, is_completed)
             VALUES (:goal_id, :quarter, :month, :plan_title, :is_completed)'
        );

        for ($month = 1; $month <= 12; $month++) {
            $quarter = (int) ceil($month / 3);

            $stmt->execute([
                'goal_id' => $goalId,
                'quarter' => $quarter,
                'month' => $month,
                'plan_title' => "{$month}월 계획",
                'is_completed' => 0,
            ]);
        }
    }

    /**
     * 사용자의 모든 목표 조회
     */
    public function findByUser(int $userId, ?int $year = null): array
    {
        $sql = 'SELECT * FROM goals WHERE user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($year !== null) {
            $sql .= ' AND year = :year';
            $params['year'] = $year;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * 목표 ID로 조회
     */
    public function findById(int $goalId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM goals WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $goalId]);

        $goal = $stmt->fetch();
        return $goal ?: null;
    }

    /**
     * 목표 업데이트
     */
    public function update(int $goalId, array $data): bool
    {
        $fields = [];
        $params = ['id' => $goalId];

        $allowedFields = ['title', 'description', 'category', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE goals SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 목표 삭제
     */
    public function delete(int $goalId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM goals WHERE id = :id');
        return $stmt->execute(['id' => $goalId]);
    }

    /**
     * 진행률 재계산 및 업데이트
     */
    public function updateProgress(int $goalId): bool
    {
        // 전체 계획 수와 완료 계획 수 조회
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as total,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
             FROM goal_plans
             WHERE goal_id = :goal_id'
        );

        $stmt->execute(['goal_id' => $goalId]);
        $result = $stmt->fetch();

        $total = $result['total'];
        $completed = $result['completed'];

        // 진행률 계산
        $progress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        // 상태 자동 결정
        $status = 'not_started';
        if ($progress > 0 && $progress < 100) {
            $status = 'in_progress';
        } elseif ($progress == 100) {
            $status = 'completed';
        }

        // 업데이트
        $stmt = $this->db->prepare(
            'UPDATE goals SET progress_percentage = :progress, status = :status WHERE id = :id'
        );

        return $stmt->execute([
            'progress' => $progress,
            'status' => $status,
            'id' => $goalId,
        ]);
    }

    /**
     * 목표 계획과 함께 조회
     */
    public function findWithPlans(int $goalId): ?array
    {
        $goal = $this->findById($goalId);

        if (!$goal) {
            return null;
        }

        // 분기별로 그룹화된 계획 조회
        $stmt = $this->db->prepare(
            'SELECT * FROM goal_plans
             WHERE goal_id = :goal_id
             ORDER BY month ASC'
        );

        $stmt->execute(['goal_id' => $goalId]);
        $plans = $stmt->fetchAll();

        // 분기별로 그룹화
        $quarterPlans = [1 => [], 2 => [], 3 => [], 4 => []];
        foreach ($plans as $plan) {
            $quarterPlans[$plan['quarter']][] = $plan;
        }

        $goal['plans'] = $plans;
        $goal['quarter_plans'] = $quarterPlans;

        return $goal;
    }

    /**
     * 카테고리별 목표 수 집계
     */
    public function countByCategory(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT category, COUNT(*) as count
             FROM goals
             WHERE user_id = :user_id
             GROUP BY category'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * 상태별 목표 수 집계
     */
    public function countByStatus(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) as count
             FROM goals
             WHERE user_id = :user_id
             GROUP BY status'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
