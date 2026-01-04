<?php

require_once __DIR__ . '/../config/database.php';

/**
 * GoalPlan 모델 클래스
 */
class GoalPlan
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 계획 조회 by ID
     */
    public function findById(int $planId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM goal_plans WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $planId]);

        $plan = $stmt->fetch();
        return $plan ?: null;
    }

    /**
     * 목표의 모든 계획 조회
     */
    public function findByGoal(int $goalId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM goal_plans WHERE goal_id = :goal_id ORDER BY month ASC'
        );

        $stmt->execute(['goal_id' => $goalId]);

        return $stmt->fetchAll();
    }

    /**
     * 특정 분기의 계획 조회
     */
    public function findByQuarter(int $goalId, int $quarter): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM goal_plans
             WHERE goal_id = :goal_id AND quarter = :quarter
             ORDER BY month ASC'
        );

        $stmt->execute([
            'goal_id' => $goalId,
            'quarter' => $quarter,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * 계획 업데이트
     */
    public function update(int $planId, array $data): bool
    {
        $fields = [];
        $params = ['id' => $planId];

        $allowedFields = ['plan_title', 'plan_description', 'is_completed'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        // 완료 시각 업데이트
        if (isset($data['is_completed']) && $data['is_completed']) {
            $fields[] = 'completed_at = NOW()';
        } elseif (isset($data['is_completed']) && !$data['is_completed']) {
            $fields[] = 'completed_at = NULL';
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE goal_plans SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 계획 완료 토글
     */
    public function toggleComplete(int $planId): bool
    {
        $plan = $this->findById($planId);

        if (!$plan) {
            return false;
        }

        $newStatus = !$plan['is_completed'];

        return $this->update($planId, ['is_completed' => $newStatus]);
    }

    /**
     * 분기별 완료율 조회
     */
    public function getQuarterProgress(int $goalId, int $quarter): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
             FROM goal_plans
             WHERE goal_id = :goal_id AND quarter = :quarter'
        );

        $stmt->execute([
            'goal_id' => $goalId,
            'quarter' => $quarter,
        ]);

        $result = $stmt->fetch();

        $total = $result['total'];
        $completed = $result['completed'];
        $progress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'progress' => $progress,
        ];
    }
}
