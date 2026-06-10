<?php
// src/Models/Song.php

require_once __DIR__ . '/../Config/database.php';

class Song
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT s.*, c.name as category_name 
                                     FROM songs s 
                                     LEFT JOIN categories c ON s.category_id = c.id 
                                     WHERE s.id = ? AND s.status = 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 歌曲列表（带收藏状态）
     */
    public function getList(int $page = 1, int $limit = 20, string $keyword = '', int $categoryId = 0, int $userId = 0): array
    {
        $offset = ($page - 1) * $limit;
        $where  = 'WHERE s.status = 1';
        $params = [];

        if ($keyword) {
            $where .= ' AND (s.title LIKE ? OR s.artist LIKE ? OR s.album LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        if ($categoryId) {
            $where .= ' AND s.category_id = ?';
            $params[] = $categoryId;
        }

        $countSql = "SELECT COUNT(*) FROM songs s {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $favSelect = $userId ? ', IF(f.song_id IS NOT NULL, 1, 0) as is_favorite' : ', 0 as is_favorite';
        $favJoin   = $userId ? 'LEFT JOIN favorites f ON f.song_id = s.id AND f.user_id = ' . $userId : '';

        $sql = "SELECT s.id, s.title, s.artist, s.album, s.cover_path, s.file_path, 
                       s.duration, s.play_count, s.category_id, s.created_at,
                       c.name as category_name
                       {$favSelect}
                FROM songs s 
                LEFT JOIN categories c ON s.category_id = c.id 
                {$favJoin}
                {$where} 
                ORDER BY s.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'total' => $total,
            'list'  => $stmt->fetchAll(),
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 热门歌曲
     */
    public function getHot(int $limit = 20): array
    {
        $stmt = $this->db->prepare('SELECT id, title, artist, album, cover_path, file_path, duration, play_count 
                                     FROM songs WHERE status = 1 
                                     ORDER BY play_count DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * 最新歌曲
     */
    public function getLatest(int $limit = 20): array
    {
        $stmt = $this->db->prepare('SELECT id, title, artist, album, cover_path, file_path, duration, play_count 
                                     FROM songs WHERE status = 1 
                                     ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO songs (title, artist, album, cover_path, file_path, duration, lyrics, category_id, uploaded_by) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['title'],
            $data['artist'],
            $data['album'],
            $data['cover_path'],
            $data['file_path'],
            $data['duration'],
            $data['lyrics'] ?? null,
            $data['category_id'] ?? null,
            $data['uploaded_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['title', 'artist', 'album', 'cover_path', 'file_path', 'duration', 'lyrics', 'category_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $sql = 'UPDATE songs SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM songs WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function incrementPlayCount(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE songs SET play_count = play_count + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * 管理端列表
     */
    public function getAdminList(int $page = 1, int $limit = 20, string $keyword = ''): array
    {
        $offset = ($page - 1) * $limit;
        $where  = 'WHERE 1=1';
        $params = [];

        if ($keyword) {
            $where .= ' AND (s.title LIKE ? OR s.artist LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $countSql = "SELECT COUNT(*) FROM songs s {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT s.*, c.name as category_name, u.username as uploader_name
                FROM songs s 
                LEFT JOIN categories c ON s.category_id = c.id 
                LEFT JOIN users u ON s.uploaded_by = u.id 
                {$where} 
                ORDER BY s.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'total' => $total,
            'list'  => $stmt->fetchAll(),
            'page'  => $page,
            'limit' => $limit,
        ];
    }
}
