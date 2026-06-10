<?php
// src/Middleware/AuthMiddleware.php

class AuthMiddleware
{
    /**
     * 检查是否已登录
     */
    public static function check(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            Response::unauthorized();
        }

        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role'     => $_SESSION['role'],
        ];
    }

    /**
     * 检查是否为管理员
     */
    public static function admin(): array
    {
        $user = self::check();
        if ($user['role'] !== 'admin') {
            Response::forbidden('需要管理员权限');
        }
        return $user;
    }

    /**
     * 可选登录（不强制）
     */
    public static function optional(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            return [
                'id'       => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role'     => $_SESSION['role'],
            ];
        }

        return ['id' => 0, 'username' => '', 'role' => 'guest'];
    }

    /**
     * 设置登录会话
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
    }

    /**
     * 注销
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }
}
