<?php
// src/Controllers/AuthController.php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $input = $this->getInput();

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $nickname = trim($input['nickname'] ?? '');

        if (strlen($username) < 2 || strlen($username) > 50) {
            Response::error('用户名长度需在2-50个字符之间');
        }
        if (strlen($password) < 6) {
            Response::error('密码长度不能少于6位');
        }

        $existing = $this->userModel->findByUsername($username);
        if ($existing) {
            Response::error('用户名已存在');
        }

        $userId = $this->userModel->create($username, $password, $nickname);
        $user = $this->userModel->findById($userId);

        AuthMiddleware::login($user);
        $token = $this->generateToken($user);

        Response::success([
            'token'    => $token,
            'id'       => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'],
            'avatar'   => $user['avatar'],
            'role'     => $user['role'],
        ], '注册成功');
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $input = $this->getInput();

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (!$username || !$password) {
            Response::error('请输入用户名和密码');
        }

        $user = $this->userModel->findByUsername($username);
        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            Response::error('用户名或密码错误');
        }

        AuthMiddleware::login($user);
        $token = $this->generateToken($user);

        Response::success([
            'token'    => $token,
            'id'       => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'],
            'avatar'   => $user['avatar'],
            'role'     => $user['role'],
        ], '登录成功');
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        AuthMiddleware::logout();
        Response::success(null, '已退出登录');
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        $user = $this->getUserFromRequest();
        $profile = $this->userModel->findById($user['id']);
        if (!$profile) {
            Response::error('用户不存在', 404);
        }
        Response::success([
            'id'       => $profile['id'],
            'username' => $profile['username'],
            'nickname' => $profile['nickname'],
            'avatar'   => $profile['avatar'],
            'role'     => $profile['role'],
        ]);
    }

    /**
     * PUT /api/auth/profile
     */
    public function updateProfile(): void
    {
        $user = $this->getUserFromRequest();
        $input = $this->getInput();

        $this->userModel->updateProfile($user['id'], $input);
        $profile = $this->userModel->findById($user['id']);

        Response::success([
            'id'       => $profile['id'],
            'username' => $profile['username'],
            'nickname' => $profile['nickname'],
            'avatar'   => $profile['avatar'],
        ], '更新成功');
    }

    /**
     * PUT /api/auth/password
     */
    public function changePassword(): void
    {
        $user = $this->getUserFromRequest();
        $input = $this->getInput();

        $currentPassword = $input['current_password'] ?? '';
        $newPassword     = $input['new_password'] ?? '';

        if (!$currentPassword || !$newPassword) {
            Response::error('请填写当前密码和新密码');
        }
        if (strlen($newPassword) < 6) {
            Response::error('新密码长度不能少于6位');
        }

        $profile = $this->userModel->findById($user['id']);
        if (!$this->userModel->verifyPassword($currentPassword, $profile['password_hash'])) {
            Response::error('当前密码错误');
        }

        $this->userModel->changePassword($user['id'], $newPassword);
        Response::success(null, '密码修改成功');
    }

    private function generateToken(array $user): string
    {
        $secret = 'musicsite-secret-key-2024';
        $data = $user['id'] . '|' . $user['username'] . '|' . $user['role'];
        $sig = hash_hmac('sha256', $data, $secret);
        return base64_encode($data) . '.' . $sig;
    }

    private function getUserFromRequest(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['user_id'])) {
            return [
                'id'       => (int) $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role'     => $_SESSION['role'],
            ];
        }
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            $token = $m[1];
            $user = $this->verifyToken($token);
            if ($user) return $user;
        }
        Response::unauthorized();
    }

    private function verifyToken(string $token): ?array
    {
        $secret = 'musicsite-secret-key-2024';
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return null;
        $data = base64_decode($parts[0]);
        $sig  = $parts[1];
        $expected = hash_hmac('sha256', $data, $secret);
        if (!hash_equals($expected, $sig)) return null;
        $fields = explode('|', $data);
        if (count($fields) !== 3) return null;
        return [
            'id'       => (int) $fields[0],
            'username' => $fields[1],
            'role'     => $fields[2],
        ];
    }

    private function getInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
