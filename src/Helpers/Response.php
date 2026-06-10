<?php
// src/Helpers/Response.php

class Response
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success($data = null, string $message = 'ok'): void
    {
        self::json([
            'code'    => 0,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    public static function error(string $message = 'error', int $code = 400): void
    {
        self::json([
            'code'    => $code,
            'message' => $message,
            'data'    => null,
        ], $code);
    }

    public static function unauthorized(string $message = '未登录'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = '无权限'): void
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = '资源不存在'): void
    {
        self::error($message, 404);
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
