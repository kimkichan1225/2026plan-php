-- 목표 우선순위 컬럼 추가
-- 실행: mysql -u root -p new_year_goals < database/add_priority.sql

USE new_year_goals;

-- goals 테이블에 priority 컬럼 추가
ALTER TABLE goals
ADD COLUMN priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '목표 우선순위'
AFTER status;

-- 인덱스 추가 (우선순위별 정렬 최적화)
CREATE INDEX idx_priority ON goals(priority);

-- 기존 데이터에 기본값 설정
UPDATE goals SET priority = 'medium' WHERE priority IS NULL;

-- 확인
SELECT id, title, priority, status FROM goals LIMIT 5;
