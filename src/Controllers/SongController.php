<?php
// src/Controllers/SongController.php

require_once __DIR__ . '/../Models/Song.php';
require_once __DIR__ . '/../Models/Category.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/Response.php';

class SongController
{
    private Song $songModel;
    private Category $categoryModel;

    public function __construct()
    {
        $this->songModel     = new Song();
        $this->categoryModel = new Category();
    }

    /**
     * GET /api/songs
     */
    public function index(): void
    {
        $page       = max(1, intval($_GET['page'] ?? 1));
        $limit      = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $keyword    = trim($_GET['keyword'] ?? '');
        $categoryId = intval($_GET['category_id'] ?? 0);
        $user       = AuthMiddleware::optional();

        $result = $this->songModel->getList($page, $limit, $keyword, $categoryId, $user['id']);
        Response::success($result);
    }

    /**
     * GET /api/songs/hot
     */
    public function hot(): void
    {
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $songs = $this->songModel->getHot($limit);
        Response::success($songs);
    }

    /**
     * GET /api/songs/latest
     */
    public function latest(): void
    {
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $songs = $this->songModel->getLatest($limit);
        Response::success($songs);
    }

    /**
     * GET /api/songs/{id}
     */
    public function show(int $id): void
    {
        $song = $this->songModel->findById($id);
        if (!$song) {
            Response::notFound('歌曲不存在');
        }
        Response::success($song);
    }

    /**
     * GET /api/songs/{id}/play
     * 播放歌曲并增加计数
     */
    public function play(int $id): void
    {
        $song = $this->songModel->findById($id);
        if (!$song) {
            Response::notFound('歌曲不存在');
        }

        $this->songModel->incrementPlayCount($id);

        // 记录播放历史
        $user = AuthMiddleware::optional();
        if ($user['id'] > 0) {
            $db = Database::getInstance();
            $stmt = $db->prepare('INSERT INTO play_history (user_id, song_id) VALUES (?, ?)');
            $stmt->execute([$user['id'], $id]);
        }

        Response::success($song);
    }

    /**
     * GET /api/categories
     */
    public function categories(): void
    {
        $categories = $this->categoryModel->getAll();
        Response::success($categories);
    }

    /**
     * POST /api/favorite/{songId}
     */
    public function favorite(int $songId): void
    {
        $user = AuthMiddleware::check();

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT user_id FROM favorites WHERE user_id = ? AND song_id = ?');
        $stmt->execute([$user['id'], $songId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $db->prepare('DELETE FROM favorites WHERE user_id = ? AND song_id = ?');
            $stmt->execute([$user['id'], $songId]);
            Response::success(['is_favorite' => false], '已取消收藏');
        } else {
            $stmt = $db->prepare('INSERT INTO favorites (user_id, song_id) VALUES (?, ?)');
            $stmt->execute([$user['id'], $songId]);
            Response::success(['is_favorite' => true], '已收藏');
        }
    }

    /**
     * GET /api/favorites
     */
    public function favorites(): void
    {
        $user = AuthMiddleware::check();
        $page  = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        $total = (int) $stmt->fetchColumn();

        $stmt = $db->prepare('SELECT s.id, s.title, s.artist, s.album, s.cover_path, s.file_path, s.duration,
                                     f.created_at as favorited_at
                              FROM favorites f 
                              JOIN songs s ON f.song_id = s.id 
                              WHERE f.user_id = ? 
                              ORDER BY f.created_at DESC 
                              LIMIT ? OFFSET ?');
        $stmt->execute([$user['id'], $limit, $offset]);

        Response::success([
            'total' => $total,
            'list'  => $stmt->fetchAll(),
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /api/history
     */
    public function history(): void
    {
        $user = AuthMiddleware::check();
        $page  = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(DISTINCT song_id) FROM play_history WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        $total = (int) $stmt->fetchColumn();

        $stmt = $db->prepare('SELECT s.id, s.title, s.artist, s.album, s.cover_path, s.file_path, s.duration,
                                     MAX(h.played_at) as played_at
                              FROM play_history h 
                              JOIN songs s ON h.song_id = s.id 
                              WHERE h.user_id = ? 
                              GROUP BY s.id 
                              ORDER BY played_at DESC 
                              LIMIT ? OFFSET ?');
        $stmt->execute([$user['id'], $limit, $offset]);

        Response::success([
            'total' => $total,
            'list'  => $stmt->fetchAll(),
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
