-- 프로필 사진 추가
-- 실행: mysql -u root -p new_year_goals < database/add_profile_picture.sql

USE new_year_goals;

-- users 테이블에 profile_picture 컬럼 추가
ALTER TABLE users
ADD COLUMN profile_picture VARCHAR(255) NULL COMMENT '프로필 사진 파일명' AFTER name;

-- 확인
DESCRIBE users;
