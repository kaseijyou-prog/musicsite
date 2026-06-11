<?php
// src/Models/Album.php

require_once __DIR__ . '/../Config/database.php';

class Album
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM albums ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM albums WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $description = '', ?string $cover = null): int
    {
        $stmt = $this->db->prepare('INSERT INTO albums (name, description, cover) VALUES (?, ?, ?)');
        $stmt->execute([$name, $description, $cover]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $description = '', ?string $cover = null): bool
    {
        $stmt = $this->db->prepare('UPDATE albums SET name = ?, description = ?, cover = COALESCE(?, cover) WHERE id = ?');
        return $stmt->execute([$name, $description, $cover, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM albums WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
