-- ======================================
-- 신년계획 관리 웹 서비스 DB 스키마
-- MySQL 8.0+
-- ======================================

-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS new_year_goals
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE new_year_goals;

-- ======================================
-- 1. users 테이블
-- ======================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '사용자 이름',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '이메일 (로그인 ID)',
    password_hash VARCHAR(255) NOT NULL COMMENT '해시된 비밀번호',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email)
) ENGINE=InnoDB COMMENT='사용자 정보';

-- ======================================
-- 2. goals 테이블
-- ======================================
CREATE TABLE goals (
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

-- ======================================
-- 3. goal_plans 테이블
-- ======================================
CREATE TABLE goal_plans (
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

-- ======================================
-- 4. reflections 테이블
-- ======================================
CREATE TABLE reflections (
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

-- ======================================
-- 샘플 데이터 (개발/테스트용)
-- ======================================

-- 테스트 사용자 생성
-- 비밀번호: password123 (실제 운영 시 제거 필요)
INSERT INTO users (name, email, password_hash) VALUES
('김개발', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 테스트 목표 생성 (user_id = 1)
INSERT INTO goals (user_id, year, title, description, category, status, progress_percentage) VALUES
(1, 2026, 'PHP 백엔드 마스터하기', 'Laravel, Symfony 프레임워크 학습 및 실전 프로젝트 3개 완성', 'study', 'in_progress', 25.00),
(1, 2026, '건강한 생활 습관 만들기', '주 3회 운동, 규칙적인 수면 패턴 유지', 'health', 'in_progress', 16.67),
(1, 2026, '사이드 프로젝트 수익화', 'SaaS 서비스 런칭 및 월 100만원 수익 달성', 'finance', 'not_started', 0.00);

-- 첫 번째 목표의 월별 계획 생성
INSERT INTO goal_plans (goal_id, quarter, month, plan_title, is_completed) VALUES
-- Q1
(1, 1, 1, 'PHP 8 기본 문법 및 OOP 복습', TRUE),
(1, 1, 2, 'Laravel 기초 강의 수강 및 블로그 프로젝트', TRUE),
(1, 1, 3, 'RESTful API 설계 및 구현 학습', TRUE),
-- Q2
(1, 2, 4, 'Symfony 프레임워크 입문', FALSE),
(1, 2, 5, 'Database 설계 패턴 학습', FALSE),
(1, 2, 6, '중간 프로젝트: 커뮤니티 사이트 제작', FALSE),
-- Q3
(1, 3, 7, '테스트 주도 개발(TDD) 실습', FALSE),
(1, 3, 8, '보안 및 성능 최적화', FALSE),
(1, 3, 9, 'Docker 및 배포 자동화', FALSE),
-- Q4
(1, 4, 10, '포트폴리오 프로젝트 1: 전자상거래', FALSE),
(1, 4, 11, '포트폴리오 프로젝트 2: 예약 시스템', FALSE),
(1, 4, 12, '포트폴리오 프로젝트 3: 신년계획 관리', FALSE);

-- 두 번째 목표의 월별 계획 생성
INSERT INTO goal_plans (goal_id, quarter, month, plan_title, is_completed) VALUES
(2, 1, 1, '운동 루틴 확립', TRUE),
(2, 1, 2, '식단 개선 및 영양 관리', TRUE),
(2, 1, 3, '수면 패턴 정상화', FALSE),
(2, 2, 4, '유산소 운동 추가', FALSE),
(2, 2, 5, '근력 운동 강화', FALSE),
(2, 2, 6, '건강 검진 및 평가', FALSE),
(2, 3, 7, '여름 체력 관리', FALSE),
(2, 3, 8, '스트레스 관리 기법', FALSE),
(2, 3, 9, '중간 목표 달성 평가', FALSE),
(2, 4, 10, '가을 운동 계획 조정', FALSE),
(2, 4, 11, '연말 건강 점검', FALSE),
(2, 4, 12, '내년 건강 계획 수립', FALSE);

-- 세 번째 목표의 월별 계획 생성
INSERT INTO goal_plans (goal_id, quarter, month, plan_title, is_completed) VALUES
(3, 1, 1, '아이디어 검증 및 시장 조사', FALSE),
(3, 1, 2, 'MVP 설계 및 기술 스택 선정', FALSE),
(3, 1, 3, '핵심 기능 개발 착수', FALSE),
(3, 2, 4, 'MVP 개발 완료', FALSE),
(3, 2, 5, '베타 테스터 모집 및 피드백', FALSE),
(3, 2, 6, '서비스 정식 런칭', FALSE),
(3, 3, 7, '초기 마케팅 및 사용자 확보', FALSE),
(3, 3, 8, '수익 모델 최적화', FALSE),
(3, 3, 9, '기능 개선 및 확장', FALSE),
(3, 4, 10, '마케팅 채널 다각화', FALSE),
(3, 4, 11, '수익 목표 달성 분석', FALSE),
(3, 4, 12, '다음 해 전략 수립', FALSE);

-- 샘플 회고 데이터
INSERT INTO reflections (user_id, goal_id, year, month, content) VALUES
(1, 1, 2026, 1, 'PHP 기본 문법 복습을 마쳤다. 특히 Trait과 Namespace 활용법을 깊이 이해할 수 있었다.'),
(1, 1, 2026, 2, 'Laravel 블로그 프로젝트를 완성했다. Eloquent ORM이 정말 강력하다는 것을 느꼈다.'),
(1, 2, 2026, 1, '아침 운동 루틴을 확립했다. 주 3회 목표를 꾸준히 지키고 있다.'),
(1, NULL, 2026, 3, '1분기를 마무리하며: 전반적으로 계획대로 잘 진행되고 있다. 다만 수면 패턴은 여전히 개선이 필요하다.');

-- ======================================
-- 유용한 뷰 (View) 생성
-- ======================================

-- 목표별 진행 상황 요약 뷰
CREATE OR REPLACE VIEW v_goal_progress AS
SELECT
    g.id AS goal_id,
    g.user_id,
    g.year,
    g.title,
    g.category,
    g.status,
    COUNT(gp.id) AS total_plans,
    SUM(CASE WHEN gp.is_completed = TRUE THEN 1 ELSE 0 END) AS completed_plans,
    ROUND((SUM(CASE WHEN gp.is_completed = TRUE THEN 1 ELSE 0 END) / COUNT(gp.id)) * 100, 2) AS calculated_progress
FROM goals g
LEFT JOIN goal_plans gp ON g.id = gp.goal_id
GROUP BY g.id, g.user_id, g.year, g.title, g.category, g.status;

-- 사용자별 대시보드 통계 뷰
CREATE OR REPLACE VIEW v_user_dashboard AS
SELECT
    u.id AS user_id,
    u.name,
    COUNT(DISTINCT g.id) AS total_goals,
    SUM(CASE WHEN g.status = 'completed' THEN 1 ELSE 0 END) AS completed_goals,
    SUM(CASE WHEN g.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_goals,
    SUM(CASE WHEN g.status = 'not_started' THEN 1 ELSE 0 END) AS not_started_goals,
    AVG(g.progress_percentage) AS avg_progress
FROM users u
LEFT JOIN goals g ON u.id = g.user_id
GROUP BY u.id, u.name;

-- ======================================
-- 프로시저: 목표 생성 시 자동 계획 생성
-- ======================================
DELIMITER //

CREATE PROCEDURE sp_create_goal_with_plans(
    IN p_user_id INT UNSIGNED,
    IN p_year YEAR,
    IN p_title VARCHAR(200),
    IN p_description TEXT,
    IN p_category VARCHAR(50)
)
BEGIN
    DECLARE v_goal_id INT UNSIGNED;
    DECLARE v_month INT DEFAULT 1;
    DECLARE v_quarter INT;

    -- 트랜잭션 시작
    START TRANSACTION;

    -- 목표 생성
    INSERT INTO goals (user_id, year, title, description, category, status)
    VALUES (p_user_id, p_year, p_title, p_description, p_category, 'not_started');

    SET v_goal_id = LAST_INSERT_ID();

    -- 12개월 계획 자동 생성
    WHILE v_month <= 12 DO
        -- 분기 계산
        SET v_quarter = CEIL(v_month / 3);

        INSERT INTO goal_plans (goal_id, quarter, month, plan_title, is_completed)
        VALUES (
            v_goal_id,
            v_quarter,
            v_month,
            CONCAT(p_title, ' - ', v_month, '월 계획'),
            FALSE
        );

        SET v_month = v_month + 1;
    END WHILE;

    COMMIT;

    -- 생성된 목표 ID 반환
    SELECT v_goal_id AS goal_id;
END //

DELIMITER ;

-- ======================================
-- 트리거: 계획 완료 시 진행률 자동 업데이트
-- ======================================
DELIMITER //

CREATE TRIGGER tr_update_progress_after_plan_update
AFTER UPDATE ON goal_plans
FOR EACH ROW
BEGIN
    DECLARE v_total INT;
    DECLARE v_completed INT;
    DECLARE v_progress DECIMAL(5,2);

    -- 해당 목표의 전체 계획 수 및 완료 계획 수 조회
    SELECT
        COUNT(*),
        SUM(CASE WHEN is_completed = TRUE THEN 1 ELSE 0 END)
    INTO v_total, v_completed
    FROM goal_plans
    WHERE goal_id = NEW.goal_id;

    -- 진행률 계산
    SET v_progress = (v_completed / v_total) * 100;

    -- 목표 테이블 업데이트
    UPDATE goals
    SET progress_percentage = v_progress,
        status = CASE
            WHEN v_progress = 0 THEN 'not_started'
            WHEN v_progress = 100 THEN 'completed'
            ELSE 'in_progress'
        END
    WHERE id = NEW.goal_id;
END //

DELIMITER ;

-- ======================================
-- 인덱스 추가 최적화
-- ======================================
CREATE INDEX idx_goals_progress ON goals(progress_percentage);
CREATE INDEX idx_plans_completed ON goal_plans(is_completed);

-- ======================================
-- 권한 설정 (선택 사항)
-- ======================================
-- CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'secure_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON new_year_goals.* TO 'app_user'@'localhost';
-- FLUSH PRIVILEGES;
