<?php
/**
 * 修复歌曲路径脚本
 * 将此文件上传到网站根目录，访问 http://你的域名/fix_paths.php 执行
 * 执行后请删除此文件
 */

// 尝试两种目录结构
$cfgFile = __DIR__ . '/src/Config/database.php';
if (!file_exists($cfgFile)) {
    $cfgFile = __DIR__ . '/../src/Config/database.php';
}
if (!file_exists($cfgFile)) {
    die('Cannot find src/Config/database.php. Please check directory structure.');
}
require_once $cfgFile;

$db = Database::getInstance();

$stmt = $db->query('SELECT id, title, cover_path, file_path FROM songs');
$songs = $stmt->fetchAll();

$fixed = 0;
foreach ($songs as $song) {
    $coverPath = $song['cover_path'];
    $filePath  = $song['file_path'];
    $changed   = false;

    // 修复封面路径：提取 /uploads/covers/xxx 部分
    if (preg_match('#(/uploads/covers/[^/]+)$#', $coverPath, $m)) {
        if ($coverPath !== $m[1]) {
            $coverPath = $m[1];
            $changed = true;
        }
    } elseif (strpos($coverPath, '/www/') === 0 || preg_match('#^[A-Z]:/#', $coverPath)) {
        $coverPath = '/assets/images/default-cover.svg';
        $changed = true;
    }

    // 修复音频路径：提取 /uploads/music/xxx 部分
    if (preg_match('#(/uploads/music/[^/]+)$#', $filePath, $m)) {
        if ($filePath !== $m[1]) {
            $filePath = $m[1];
            $changed = true;
        }
    }

    if ($changed) {
        $stmt = $db->prepare('UPDATE songs SET cover_path = ?, file_path = ? WHERE id = ?');
        $stmt->execute([$coverPath, $filePath, $song['id']]);
        $fixed++;
        echo "Fixed #{$song['id']}: {$song['title']} → cover={$coverPath}, file={$filePath}<br>";
    }
}

echo "<br>Done. Fixed {$fixed} song(s).";
