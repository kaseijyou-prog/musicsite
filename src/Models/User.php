<?php
// src/Models/User.php

require_once __DIR__ . '/../Config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? AND status = 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, nickname, avatar, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $username, string $password, string $nickname = ''): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('INSERT INTO users (username, password_hash, nickname) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hash, $nickname ?: $username]);
        return (int) $this->db->lastInsertId();
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updateAvatar(int $userId, string $avatar): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        return $stmt->execute([$avatar, $userId]);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['nickname'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function changePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([$hash, $userId]);
    }

    /**
     * 管理端: 获取用户列表
     */
    public function getList(int $page = 1, int $limit = 20, string $keyword = ''): array
    {
        $offset = ($page - 1) * $limit;
        $where  = 'WHERE 1=1';
        $params = [];

        if ($keyword) {
            $where .= ' AND (username LIKE ? OR nickname LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $countSql = "SELECT COUNT(*) FROM users {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT id, username, nickname, avatar, role, status, created_at 
                FROM users {$where} 
                ORDER BY created_at DESC 
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

    public function toggleStatus(int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET status = IF(status=1, 0, 1) WHERE id = ?');
        return $stmt->execute([$userId]);
    }
}
