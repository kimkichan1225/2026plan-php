<?php

require_once __DIR__ . '/../config/database.php';

/**
 * User 모델 클래스
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * 사용자 생성 (회원가입)
     */
    public function create(string $name, string $email, string $password): int
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
        );

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 이메일로 사용자 조회
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * ID로 사용자 조회
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * 이메일 중복 체크
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * 로그인 인증
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    /**
     * 사용자 정보 업데이트
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params['email'] = $data['email'];
        }

        if (isset($data['profile_picture'])) {
            $fields[] = 'profile_picture = :profile_picture';
            $params['profile_picture'] = $data['profile_picture'];
        }

        if (isset($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 사용자 검색 (이름 또는 이메일)
     */
    public function search(string $keyword, int $limit = 20, int $offset = 0): array
    {
        $keyword = "%{$keyword}%";

        $stmt = $this->db->prepare(
            'SELECT id, name, email, profile_picture, followers_count, following_count, created_at
             FROM users
             WHERE name LIKE :keyword OR email LIKE :keyword
             ORDER BY followers_count DESC, name ASC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':keyword', $keyword);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 활동적인 사용자 목록 (공개 목표가 많은 순)
     */
    public function getActiveUsers(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.profile_picture, u.followers_count, u.following_count,
                    COUNT(DISTINCT g.id) as public_goals_count
             FROM users u
             LEFT JOIN goals g ON u.id = g.user_id AND g.visibility = "public"
             GROUP BY u.id
             HAVING public_goals_count > 0
             ORDER BY public_goals_count DESC, u.followers_count DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
