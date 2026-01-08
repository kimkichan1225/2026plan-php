-- 커뮤니티 기능: 목표 공유 관련 컬럼 추가
-- 실행: mysql -u root -p new_year_goals < database/add_community_features.sql

USE new_year_goals;

-- goals 테이블에 공개 설정, 조회수, 좋아요 수 추가
ALTER TABLE goals
ADD COLUMN visibility ENUM('private', 'public') DEFAULT 'private' COMMENT '공개 설정' AFTER priority,
ADD COLUMN views INT UNSIGNED DEFAULT 0 COMMENT '조회수' AFTER visibility,
ADD COLUMN likes INT UNSIGNED DEFAULT 0 COMMENT '좋아요 수' AFTER views;

-- 인덱스 추가 (공개 목표 조회 최적화)
CREATE INDEX idx_visibility ON goals(visibility);
CREATE INDEX idx_views ON goals(views);
CREATE INDEX idx_likes ON goals(likes);

-- 확인
DESCRIBE goals;

SELECT id, title, visibility, views, likes FROM goals LIMIT 5;
