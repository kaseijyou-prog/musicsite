<?php
// src/Controllers/AdminController.php

require_once __DIR__ . '/../Models/Song.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Category.php';
require_once __DIR__ . '/../Models/Album.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/FileUpload.php';

class AdminController
{
    private Song $songModel;
    private User $userModel;
    private Category $categoryModel;
    private Album $albumModel;

    public function __construct()
    {
        $this->songModel     = new Song();
        $this->userModel     = new User();
        $this->categoryModel = new Category();
        $this->albumModel    = new Album();
    }

    // ==================== 歌曲管理 ====================

    /**
     * GET /api/admin/songs
     */
    public function songs(): void
    {
        AuthMiddleware::admin();
        $page    = max(1, intval($_GET['page'] ?? 1));
        $limit   = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $keyword = trim($_GET['keyword'] ?? '');
        $result  = $this->songModel->getAdminList($page, $limit, $keyword);
        Response::success($result);
    }

    /**
     * POST /api/admin/songs/upload
     */
    public function uploadSong(): void
    {
        AuthMiddleware::admin();

        $user = AuthMiddleware::admin();

        // 验证输入
        $title   = trim($_POST['title'] ?? '');
        $artist  = trim($_POST['artist'] ?? '');
        $album   = trim($_POST['album'] ?? '');
        $catId   = intval($_POST['category_id'] ?? 0);
        $lyrics  = trim($_POST['lyrics'] ?? '');

        if (!$title) {
            Response::error('请输入歌曲名称');
        }
        if (empty($_FILES['music_file'])) {
            Response::error('请上传音乐文件');
        }

        // 上传音乐文件
        $musicUploader = new FileUpload(__DIR__ . '/../../public/uploads/music', ['mp3', 'wav', 'flac', 'ogg', 'm4a']);
        $musicPath = $musicUploader->upload($_FILES['music_file']);

        // 上传封面（可选）
        $coverPath = '/assets/images/default-cover.svg';
        if (!empty($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $coverUploader = new FileUpload(__DIR__ . '/../../public/uploads/covers', ['jpg', 'jpeg', 'png', 'webp']);
            $coverPath = $coverUploader->upload($_FILES['cover_file']);
        }

        // 将上传路径转为 URL 相对路径（/uploads/xxx）
        // 用正则提取，避免 realpath() 在不同环境返回不同路径
        $musicUrl = $musicPath;
        if (preg_match('#[/\\\\]uploads[/\\\\]music[/\\\\]([^/\\\\]+)$#', $musicPath, $m)) {
            $musicUrl = '/uploads/music/' . $m[1];
        }
        if ($coverPath !== '/assets/images/default-cover.svg') {
            if (preg_match('#[/\\\\]uploads[/\\\\]covers[/\\\\]([^/\\\\]+)$#', $coverPath, $m)) {
                $coverPath = '/uploads/covers/' . $m[1];
            }
        }

        // 获取音频时长
        $duration = $this->getAudioDuration($musicPath);

        $songId = $this->songModel->create([
            'title'       => $title,
            'artist'      => $artist,
            'album'       => $album,
            'cover_path'  => $coverPath,
            'file_path'   => $musicUrl,
            'duration'    => $duration,
            'lyrics'      => $lyrics,
            'category_id' => $catId ?: null,
            'uploaded_by' => $user['id'],
        ]);

        Response::success(['id' => $songId], '上传成功');
    }

    /**
     * PUT /api/admin/songs/{id}
     */
    public function updateSong(int $id): void
    {
        AuthMiddleware::admin();
        $input = $this->getInput();
        $this->songModel->update($id, $input);
        Response::success(null, '更新成功');
    }

    /**
     * DELETE /api/admin/songs/{id}
     */
    public function deleteSong(int $id): void
    {
        AuthMiddleware::admin();
        $this->songModel->delete($id);
        Response::success(null, '删除成功');
    }

    // ==================== 用户管理 ====================

    /**
     * GET /api/admin/users
     */
    public function users(): void
    {
        AuthMiddleware::admin();
        $page    = max(1, intval($_GET['page'] ?? 1));
        $limit   = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $keyword = trim($_GET['keyword'] ?? '');
        $result  = $this->userModel->getList($page, $limit, $keyword);
        Response::success($result);
    }

    /**
     * POST /api/admin/users
     * 管理员创建新用户
     */
    public function createUser(): void
    {
        AuthMiddleware::admin();
        $input = $this->getInput();

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $nickname = trim($input['nickname'] ?? '');
        $role     = trim($input['role'] ?? 'user');

        if (strlen($username) < 2 || strlen($username) > 50) {
            Response::error('用户名长度需在2-50个字符之间');
        }
        if (strlen($password) < 6) {
            Response::error('密码长度不能少于6位');
        }
        if (!in_array($role, ['user', 'admin'])) {
            $role = 'user';
        }

        $existing = $this->userModel->findByUsername($username);
        if ($existing) {
            Response::error('用户名已存在');
        }

        $id = $this->userModel->create($username, $password, $nickname);
        Response::success(['id' => $id], '用户创建成功');
    }

    /**
     * PUT /api/admin/users/{id}/toggle
     */
    public function toggleUser(int $id): void
    {
        AuthMiddleware::admin();
        $this->userModel->toggleStatus($id);
        Response::success(null, '操作成功');
    }

    // ==================== 分类管理 ====================

    /**
     * POST /api/admin/categories
     */
    public function createCategory(): void
    {
        AuthMiddleware::admin();
        $input = $this->getInput();
        $name = trim($input['name'] ?? '');
        if (!$name) Response::error('请输入分类名称');
        $sortOrder = intval($input['sort_order'] ?? 0);
        $id = $this->categoryModel->create($name, $sortOrder);
        Response::success(['id' => $id], '创建成功');
    }

    /**
     * PUT /api/admin/categories/{id}
     */
    public function updateCategory(int $id): void
    {
        AuthMiddleware::admin();
        $input = $this->getInput();
        $name = trim($input['name'] ?? '');
        $sortOrder = intval($input['sort_order'] ?? 0);
        if (!$name) Response::error('请输入分类名称');
        $this->categoryModel->update($id, $name, $sortOrder);
        Response::success(null, '更新成功');
    }

    /**
     * DELETE /api/admin/categories/{id}
     */
    public function deleteCategory(int $id): void
    {
        AuthMiddleware::admin();
        $this->categoryModel->delete($id);
        Response::success(null, '删除成功');
    }

    // ==================== 专辑管理 ====================

    /**
     * GET /api/admin/albums
     */
    public function albums(): void
    {
        AuthMiddleware::admin();
        $result = $this->albumModel->getAll();
        Response::success($result);
    }

    /**
     * POST /api/admin/albums
     */
    public function createAlbum(): void
    {
        AuthMiddleware::admin();
        $input = $this->getInput();
        $name        = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $cover       = isset($input['cover']) ? trim($input['cover']) : null;

        if (!$name) {
            Response::error('请输入专辑名称');
        }

        $id = $this->albumModel->create($name, $description, $cover);
        Response::success(['id' => $id], '专辑创建成功');
    }

    /**
     * PUT /api/admin/albums/{id}
     */
    public function updateAlbum(int $id): void
    {
        AuthMiddleware::admin();
        $input = $this->getInput();
        $name        = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $cover       = isset($input['cover']) ? trim($input['cover']) : null;

        if (!$name) {
            Response::error('请输入专辑名称');
        }

        $this->albumModel->update($id, $name, $description, $cover);
        Response::success(null, '专辑更新成功');
    }

    /**
     * DELETE /api/admin/albums/{id}
     */
    public function deleteAlbum(int $id): void
    {
        AuthMiddleware::admin();
        $this->albumModel->delete($id);
        Response::success(null, '专辑删除成功');
    }

    // ==================== 统计 ====================

    /**
     * GET /api/admin/stats
     */
    public function stats(): void
    {
        AuthMiddleware::admin();
        $db = Database::getInstance();

        $stats = [];
        $stats['total_songs']   = (int) $db->query('SELECT COUNT(*) FROM songs')->fetchColumn();
        $stats['total_users']   = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['total_plays']   = (int) $db->query('SELECT IFNULL(SUM(play_count), 0) FROM songs')->fetchColumn();
        $stats['today_plays']   = (int) $db->query("SELECT COUNT(*) FROM play_history WHERE DATE(played_at) = CURDATE()")->fetchColumn();

        Response::success($stats);
    }

    // ==================== 工具方法 ====================

    private function getAudioDuration(string $filepath): int
    {
        // 简单估算：通过文件大小粗略计算（实际项目中推荐用 getid3 库）
        if (!file_exists($filepath)) return 0;
        $size = filesize($filepath);
        // 假设 128kbps 码率
        return (int) round($size / (128000 / 8));
    }

    private function getInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
