-- 专辑表
CREATE TABLE IF NOT EXISTS `albums` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL COMMENT '专辑名称',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '专辑描述',
    `cover` VARCHAR(255) DEFAULT NULL COMMENT '专辑封面',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='专辑表';

-- 歌曲表添加 album_id 外键
ALTER TABLE `songs` ADD COLUMN `album_id` INT UNSIGNED DEFAULT NULL COMMENT '专辑ID' AFTER `album`;
ALTER TABLE `songs` ADD FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE SET NULL;
ALTER TABLE `songs` ADD INDEX `idx_album` (`album_id`);

-- 插入默认专辑
INSERT INTO `albums` (`name`, `description`) VALUES ('默认专辑', '系统默认专辑');

-- 更新已有歌曲关联默认专辑
UPDATE `songs` SET `album_id` = 1 WHERE `album_id` IS NULL;
