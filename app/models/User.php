<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    public static function createParticipant(string $name, string $email, string $password, ?string $college, ?string $phone): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO users (name, email, password, role, status, college, phone) VALUES (?, ?, ?, "participant", "active", ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $college, $phone]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function allByRole(string $role): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE role = ? ORDER BY name');
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    public static function organizersWithoutClub(): array
    {
        $stmt = Database::connect()->query(
            "SELECT u.* FROM users u
             LEFT JOIN clubs c ON c.organizer_id = u.id
             WHERE u.role = 'organizer' AND c.id IS NULL
             ORDER BY u.name"
        );
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function countByRole(string $role): int
    {
        $stmt = Database::connect()->prepare("SELECT COUNT(*) AS c FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetch()['c'];
    }
}
