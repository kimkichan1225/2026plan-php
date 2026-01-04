-- 테스트 사용자 삽입
-- 비밀번호: password123

USE new_year_goals;

-- 기존 테스트 사용자 삭제 (있다면)
DELETE FROM users WHERE email = 'test@example.com';

-- 새 테스트 사용자 삽입
INSERT INTO users (name, email, password_hash, created_at, updated_at)
VALUES (
    '테스트 사용자',
    'test@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW(),
    NOW()
);

-- 확인
SELECT id, name, email, created_at FROM users WHERE email = 'test@example.com';
