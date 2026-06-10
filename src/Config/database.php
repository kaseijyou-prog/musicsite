<?php
// src/Config/database.php

class Database
{
    private static ?PDO $instance = null;

    private const HOST = 'localhost';
    private const PORT = '3306';
    private const DBNAME = 'musicsite';
    private const USERNAME = 'root';
    private const PASSWORD = 'root';
    private const CHARSET = 'utf8mb4';

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::HOST,
                self::PORT,
                self::DBNAME,
                self::CHARSET
            );

            self::$instance = new PDO($dsn, self::USERNAME, self::PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        }

        return self::$instance;
    }

    // 禁止克隆和反序列化
    private function __clone() {}
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }
}
