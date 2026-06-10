<?php
/**
 * 修复歌曲路径脚本
 * 将此文件上传到网站根目录，访问 http://你的域名/fix_paths.php 执行
 * 执行后请删除此文件
 */

require_once __DIR__ . '/../src/Config/database.php';

$db = Database::getInstance();

// 获取所有歌曲
$stmt = $db->query('SELECT id, cover_path, file_path FROM songs');
$songs = $stmt->fetchAll();

$fixed = 0;
foreach ($songs as $song) {
    $coverPath = $song['cover_path'];
    $filePath = $song['file_path'];
    $changed = false;

    // 修复封面路径
    if (strpos($coverPath, '/www/') === 0 || strpos($coverPath, 'C:/') === 0 || strpos($coverPath, 'D:/') === 0) {
        if (preg_match('#/uploads/covers/(.+)$#', $coverPath, $m)) {
            $coverPath = '/uploads/covers/' . $m[1];
        } else {
            $coverPath = '/assets/images/default-cover.svg';
        }
        $changed = true;
    }

    // 修复音频路径
    if (strpos($filePath, '/www/') === 0 || strpos($filePath, 'C:/') === 0 || strpos($filePath, 'D:/') === 0) {
        if (preg_match('#/uploads/music/(.+)$#', $filePath, $m)) {
            $filePath = '/uploads/music/' . $m[1];
        }
        $changed = true;
    }

    if ($changed) {
        $stmt = $db->prepare('UPDATE songs SET cover_path = ?, file_path = ? WHERE id = ?');
        $stmt->execute([$coverPath, $filePath, $song['id']]);
        $fixed++;
        echo "Fixed song #{$song['id']}: cover={$coverPath}, file={$filePath}<br>";
    }
}

echo "<br>Done. Fixed {$fixed} song(s).";
