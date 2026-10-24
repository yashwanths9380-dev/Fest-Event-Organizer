<?php

require_once __DIR__ . '/../../core/Database.php';

class Fest
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            "SELECT f.*, (SELECT COUNT(*) FROM events e WHERE e.fest_id = f.id) AS event_count,
                    (SELECT COUNT(*) FROM registrations r JOIN events e2 ON e2.id = r.event_id
                      WHERE e2.fest_id = f.id AND r.status = 'confirmed') AS confirmed_registrations
             FROM fests f ORDER BY f.start_date DESC"
        );
        return $stmt->fetchAll();
    }

    public static function published(): array
    {
        $stmt = Database::connect()->query(
            "SELECT f.*, (SELECT COUNT(*) FROM events e WHERE e.fest_id = f.id AND e.status = 'approved') AS event_count
             FROM fests f WHERE f.status IN ('published', 'completed') ORDER BY f.start_date DESC"
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM fests WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $slug, string $description, string $theme, string $startDate, string $endDate, string $venue, int $createdBy): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO fests (name, slug, description, theme, start_date, end_date, venue, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $slug, $description, $theme, $startDate, $endDate, $venue, $createdBy]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name, string $description, string $theme, string $startDate, string $endDate, string $venue): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE fests SET name = ?, description = ?, theme = ?, start_date = ?, end_date = ?, venue = ? WHERE id = ?'
        );
        $stmt->execute([$name, $description, $theme, $startDate, $endDate, $venue, $id]);
    }

    /**
     * True if any event under this fest has at least one confirmed
     * registration. Used to lock the fest's own start_date/end_date once
     * participants have committed to schedules built around them --
     * mirroring the per-event lock in Event::update()'s caller.
     */
    public static function hasConfirmedRegistrations(int $festId): bool
    {
        $stmt = Database::connect()->prepare(
            "SELECT 1 FROM registrations r JOIN events e ON e.id = r.event_id
             WHERE e.fest_id = ? AND r.status = 'confirmed' LIMIT 1"
        );
        $stmt->execute([$festId]);
        return (bool)$stmt->fetch();
    }

    /** Confirmed registrations across every event in this fest -- used to name the number in lifecycle-lock messages. */
    public static function confirmedRegistrationCount(int $festId): int
    {
        $stmt = Database::connect()->prepare(
            "SELECT COUNT(*) AS c FROM registrations r JOIN events e ON e.id = r.event_id
             WHERE e.fest_id = ? AND r.status = 'confirmed'"
        );
        $stmt->execute([$festId]);
        return (int)$stmt->fetch()['c'];
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE fests SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) AS c FROM fests')->fetch()['c'];
    }
}
