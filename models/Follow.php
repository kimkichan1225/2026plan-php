<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Follow 모델 클래스
 */
class Follow
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 팔로우하기
     */
    public function follow(int $followerId, int $followingId): bool
    {
        // 자기 자신을 팔로우하는 것 방지
        if ($followerId === $followingId) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO user_follows (follower_id, following_id) VALUES (:follower_id, :following_id)'
            );

            return $stmt->execute([
                'follower_id' => $followerId,
                'following_id' => $followingId,
            ]);
        } catch (PDOException $e) {
            // UNIQUE 제약 조건 위반 (이미 팔로우 중)
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * 언팔로우하기
     */
    public function unfollow(int $followerId, int $followingId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id'
        );

        return $stmt->execute([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);
    }

    /**
     * 팔로우 여부 확인
     */
    public function isFollowing(int $followerId, int $followingId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id'
        );

        $stmt->execute([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * 팔로워 목록 조회 (나를 팔로우하는 사람들)
     */
    public function getFollowers(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.profile_picture, u.followers_count, u.following_count, uf.created_at as followed_at
             FROM user_follows uf
             INNER JOIN users u ON uf.follower_id = u.id
             WHERE uf.following_id = :user_id
             ORDER BY uf.created_at DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 팔로잉 목록 조회 (내가 팔로우하는 사람들)
     */
    public function getFollowing(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.profile_picture, u.followers_count, u.following_count, uf.created_at as followed_at
             FROM user_follows uf
             INNER JOIN users u ON uf.following_id = u.id
             WHERE uf.follower_id = :user_id
             ORDER BY uf.created_at DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 팔로워 수 조회
     */
    public function getFollowersCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM user_follows WHERE following_id = :user_id'
        );

        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 팔로잉 수 조회
     */
    public function getFollowingCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM user_follows WHERE follower_id = :user_id'
        );

        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 맞팔로우 여부 확인 (서로 팔로우하는지)
     */
    public function isMutualFollow(int $userId1, int $userId2): bool
    {
        return $this->isFollowing($userId1, $userId2) && $this->isFollowing($userId2, $userId1);
    }

    /**
     * 추천 사용자 목록 (아직 팔로우하지 않은 활동적인 사용자)
     */
    public function getRecommendedUsers(int $currentUserId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.profile_picture, u.followers_count, u.following_count,
                    COUNT(DISTINCT g.id) as public_goals_count
             FROM users u
             LEFT JOIN goals g ON u.id = g.user_id AND g.visibility = "public"
             WHERE u.id != :current_user_id
             AND u.id NOT IN (
                 SELECT following_id FROM user_follows WHERE follower_id = :current_user_id
             )
             GROUP BY u.id
             ORDER BY public_goals_count DESC, u.followers_count DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
