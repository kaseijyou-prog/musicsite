<?php
// src/Middleware/AuthMiddleware.php

class AuthMiddleware
{
    private const SECRET = 'musicsite-secret-key-2024';

    public static function check(): array
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
            $user = self::verifyToken($m[1]);
            if ($user) return $user;
        }
        Response::unauthorized();
    }

    public static function admin(): array
    {
        $user = self::check();
        if ($user['role'] !== 'admin') {
            Response::forbidden('闇€瑕佺鐞嗗憳鏉冮檺');
        }
        return $user;
    }

    public static function optional(): array
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
            $user = self::verifyToken($m[1]);
            if ($user) return $user;
        }
        return ['id' => 0, 'username' => '', 'role' => 'guest'];
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    private static function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return null;
        $data = base64_decode($parts[0]);
        $sig  = $parts[1];
        $expected = hash_hmac('sha256', $data, self::SECRET);
        if (!hash_equals($expected, $sig)) return null;
        $fields = explode('|', $data);
        if (count($fields) !== 3) return null;
        return [
            'id'       => (int) $fields[0],
            'username' => $fields[1],
            'role'     => $fields[2],
        ];
    }
}