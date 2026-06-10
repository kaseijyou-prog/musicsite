<?php
// src/Models/Category.php

require_once __DIR__ . '/../Config/database.php';

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM categories ORDER BY sort_order ASC');
        return $stmt->fetchAll();
    }

    public function create(string $name, int $sortOrder = 0): int
    {
        $stmt = $this->db->prepare('INSERT INTO categories (name, sort_order) VALUES (?, ?)');
        $stmt->execute([$name, $sortOrder]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, int $sortOrder): bool
    {
        $stmt = $this->db->prepare('UPDATE categories SET name = ?, sort_order = ? WHERE id = ?');
        return $stmt->execute([$name, $sortOrder, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
