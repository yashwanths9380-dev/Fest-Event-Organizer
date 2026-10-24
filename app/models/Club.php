<?php

require_once __DIR__ . '/../../core/Database.php';

class Club
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            "SELECT c.*, u.name AS organizer_name, u.email AS organizer_email,
                    (SELECT COUNT(*) FROM events e WHERE e.club_id = c.id) AS event_count
             FROM clubs c JOIN users u ON u.id = c.organizer_id
             ORDER BY c.name"
        );
        return $stmt->fetchAll();
    }

    public static function activeAll(): array
    {
        $stmt = Database::connect()->query("SELECT * FROM clubs WHERE status = 'active' ORDER BY name");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM clubs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByOrganizer(int $organizerId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM clubs WHERE organizer_id = ?');
        $stmt->execute([$organizerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $slug, string $description, int $organizerId): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO clubs (name, slug, description, organizer_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $slug, $description, $organizerId]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function updateDescription(int $id, string $description): void
    {
        $stmt = Database::connect()->prepare('UPDATE clubs SET description = ? WHERE id = ?');
        $stmt->execute([$description, $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE clubs SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) AS c FROM clubs')->fetch()['c'];
    }
}
