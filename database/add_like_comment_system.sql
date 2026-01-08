-- 좋아요 및 댓글 시스템
-- 실행: mysql -u root -p new_year_goals < database/add_like_comment_system.sql

USE new_year_goals;

-- 좋아요 테이블
CREATE TABLE IF NOT EXISTS goal_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (goal_id, user_id) COMMENT '중복 좋아요 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='목표 좋아요';

-- 댓글 테이블
CREATE TABLE IF NOT EXISTS goal_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL COMMENT '댓글 내용',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_goal_id (goal_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='목표 댓글';

-- 확인
SHOW TABLES;
DESCRIBE goal_likes;
DESCRIBE goal_comments;
