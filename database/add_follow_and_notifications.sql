-- 팔로우 및 알림 시스템 추가
-- 실행: mysql -u root -p new_year_goals < database/add_follow_and_notifications.sql

USE new_year_goals;

-- user_follows 테이블: 팔로우 관계 저장
CREATE TABLE IF NOT EXISTS user_follows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT UNSIGNED NOT NULL COMMENT '팔로우하는 사용자 ID',
    following_id INT UNSIGNED NOT NULL COMMENT '팔로우되는 사용자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,

    -- 중복 팔로우 방지
    UNIQUE KEY unique_follow (follower_id, following_id),

    -- 자기 자신을 팔로우 방지는 애플리케이션 레벨에서 처리
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='사용자 팔로우 관계';

-- notifications 테이블: 알림 저장
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '알림을 받을 사용자 ID',
    type ENUM('follow', 'like', 'comment', 'goal_shared') NOT NULL COMMENT '알림 유형',
    actor_id INT UNSIGNED NULL COMMENT '알림을 발생시킨 사용자 ID',
    goal_id INT UNSIGNED NULL COMMENT '관련 목표 ID (있는 경우)',
    comment_id INT UNSIGNED NULL COMMENT '관련 댓글 ID (있는 경우)',
    message TEXT NOT NULL COMMENT '알림 메시지',
    is_read BOOLEAN DEFAULT FALSE COMMENT '읽음 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES goal_comments(id) ON DELETE CASCADE,

    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='사용자 알림';

-- users 테이블에 팔로워/팔로잉 수 컬럼 추가 (성능 최적화용)
ALTER TABLE users
ADD COLUMN followers_count INT UNSIGNED DEFAULT 0 COMMENT '팔로워 수' AFTER profile_picture,
ADD COLUMN following_count INT UNSIGNED DEFAULT 0 COMMENT '팔로잉 수' AFTER followers_count;

-- 팔로워 수 업데이트 트리거
DELIMITER $$

CREATE TRIGGER tr_update_follow_counts_after_insert
AFTER INSERT ON user_follows
FOR EACH ROW
BEGIN
    -- 팔로잉 수 증가 (팔로우하는 사람)
    UPDATE users SET following_count = following_count + 1 WHERE id = NEW.follower_id;
    -- 팔로워 수 증가 (팔로우되는 사람)
    UPDATE users SET followers_count = followers_count + 1 WHERE id = NEW.following_id;
END$$

CREATE TRIGGER tr_update_follow_counts_after_delete
AFTER DELETE ON user_follows
FOR EACH ROW
BEGIN
    -- 팔로잉 수 감소 (팔로우하는 사람)
    UPDATE users SET following_count = following_count - 1 WHERE id = OLD.follower_id;
    -- 팔로워 수 감소 (팔로우되는 사람)
    UPDATE users SET followers_count = followers_count - 1 WHERE id = OLD.following_id;
END$$

DELIMITER ;

-- 테이블 구조 확인
DESCRIBE user_follows;
DESCRIBE notifications;
DESCRIBE users;
