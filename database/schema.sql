CREATE DATABASE IF NOT EXISTS `musicsite` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `musicsite`;

-- ============================================
-- 用户表
-- ============================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    `password_hash` VARCHAR(255) NOT NULL COMMENT '密码哈希',
    `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    `avatar` VARCHAR(255) DEFAULT '/assets/images/default-avatar.png' COMMENT '头像路径',
    `role` ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0禁用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ============================================
-- 分类表
-- ============================================
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT '分类名称',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='歌曲分类表';

-- ============================================
-- 歌曲表
-- ============================================
CREATE TABLE `songs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL COMMENT '歌曲名',
    `artist` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '艺术家',
    `album` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '专辑',
    `cover_path` VARCHAR(255) DEFAULT '/assets/images/default-cover.png' COMMENT '封面图',
    `file_path` VARCHAR(255) NOT NULL COMMENT '音频文件路径',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时长(秒)',
    `lyrics` TEXT DEFAULT NULL COMMENT 'LRC歌词',
    `play_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '播放次数',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT '分类ID',
    `uploaded_by` INT UNSIGNED NOT NULL COMMENT '上传者',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0隐藏',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_category` (`category_id`),
    INDEX `idx_play_count` (`play_count` DESC),
    INDEX `idx_created` (`created_at` DESC),
    FULLTEXT KEY `ft_search` (`title`, `artist`, `album`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='歌曲表';

-- ============================================
-- 收藏表
-- ============================================
CREATE TABLE `favorites` (
    `user_id` INT UNSIGNED NOT NULL,
    `song_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `song_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户收藏表';

-- ============================================
-- 播放历史表
-- ============================================
CREATE TABLE `play_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `song_id` INT UNSIGNED NOT NULL,
    `played_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_time` (`user_id`, `played_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='播放历史表';

-- ============================================
-- 歌单表
-- ============================================
CREATE TABLE `playlists` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT '歌单名',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '描述',
    `cover` VARCHAR(255) DEFAULT NULL COMMENT '歌单封面',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='歌单表';

-- ============================================
-- 歌单歌曲关联表
-- ============================================
CREATE TABLE `playlist_songs` (
    `playlist_id` INT UNSIGNED NOT NULL,
    `song_id` INT UNSIGNED NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`playlist_id`, `song_id`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='歌单歌曲表';

-- ============================================
-- 插入默认管理员 (admin / admin123)
-- ============================================
INSERT INTO `users` (`username`, `password_hash`, `nickname`, `role`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', 'admin');

-- ============================================
-- 插入默认分类
-- ============================================
INSERT INTO `categories` (`name`, `sort_order`) VALUES
('流行音乐', 1),
('摇滚', 2),
('民谣', 3),
('电子', 4),
('说唱', 5),
('轻音乐', 6),
('经典', 7),
('其他', 8);
