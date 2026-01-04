-- ======================================
-- 신년계획 관리 웹 서비스 - 간단 스키마
-- 테이블만 생성 (프로시저/트리거 제외)
-- ======================================

-- 1. users 테이블
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '사용자 이름',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '이메일 (로그인 ID)',
    password_hash VARCHAR(255) NOT NULL COMMENT '해시된 비밀번호',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB COMMENT='사용자 정보';

-- 2. goals 테이블
CREATE TABLE IF NOT EXISTS goals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '목표 소유자',
    year YEAR NOT NULL COMMENT '목표 연도 (예: 2026)',
    title VARCHAR(200) NOT NULL COMMENT '목표 제목',
    description TEXT COMMENT '목표 상세 설명',
    category ENUM('career', 'health', 'study', 'finance', 'hobby', 'relationship', 'other')
        DEFAULT 'other' COMMENT '목표 카테고리',
    status ENUM('not_started', 'in_progress', 'completed')
        DEFAULT 'not_started' COMMENT '목표 상태',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT '진행률 (0.00 ~ 100.00)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_year (user_id, year),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB COMMENT='신년 목표';

-- 3. goal_plans 테이블
CREATE TABLE IF NOT EXISTS goal_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id INT UNSIGNED NOT NULL COMMENT '연결된 목표',
    quarter TINYINT UNSIGNED NOT NULL COMMENT '분기 (1~4)',
    month TINYINT UNSIGNED NOT NULL COMMENT '월 (1~12)',
    plan_title VARCHAR(200) COMMENT '월별 계획 제목',
    plan_description TEXT COMMENT '월별 계획 상세',
    is_completed BOOLEAN DEFAULT FALSE COMMENT '완료 여부',
    completed_at TIMESTAMP NULL COMMENT '완료 시각',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    INDEX idx_goal_quarter (goal_id, quarter),
    INDEX idx_goal_month (goal_id, month),
    UNIQUE KEY unique_goal_month (goal_id, month),
    CHECK (quarter BETWEEN 1 AND 4),
    CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB COMMENT='목표별 분기/월 계획';

-- 4. reflections 테이블
CREATE TABLE IF NOT EXISTS reflections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '작성자',
    goal_id INT UNSIGNED NULL COMMENT '연결된 목표 (NULL = 전체 회고)',
    year YEAR NOT NULL COMMENT '회고 연도',
    month TINYINT UNSIGNED NOT NULL COMMENT '회고 월 (1~12)',
    content TEXT NOT NULL COMMENT '회고 내용',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, year, month),
    INDEX idx_goal (goal_id),
    UNIQUE KEY unique_reflection (user_id, goal_id, year, month),
    CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB COMMENT='월별 회고';

-- 샘플 데이터
-- password_hash for 'test1234'
INSERT IGNORE INTO users (id, name, email, password_hash) VALUES
(1, '테스트 사용자', 'test@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye1IVI.9J6WqDqYZ3FqkMqZpVHXVKv6mO');
