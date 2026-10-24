<?php

require_once __DIR__ . '/../../core/Database.php';

class Result
{
    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT res.*, r.team_name, r.team_size, u.name AS leader_name
             FROM results res
             JOIN registrations r ON r.id = res.registration_id
             JOIN users u ON u.id = r.leader_id
             WHERE res.event_id = ?
             ORDER BY FIELD(res.position, 'winner', 'runner_up', 'second_runner_up', 'participant')"
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public static function forRegistration(int $registrationId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM results WHERE registration_id = ?');
        $stmt->execute([$registrationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function record(int $eventId, int $registrationId, string $position, ?string $remarks, int $recordedBy): void
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO results (event_id, registration_id, position, remarks, recorded_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE position = VALUES(position), remarks = VALUES(remarks), recorded_by = VALUES(recorded_by), recorded_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$eventId, $registrationId, $position, $remarks, $recordedBy]);
    }

    /**
     * The other registration (if any) already holding $position for this
     * event, excluding $registrationId itself -- used to stop a rank
     * (winner/runner_up/second_runner_up) being awarded to two different
     * teams in the same event. Re-saving the same registration's own result
     * (e.g. changing its remarks) is not blocked by this.
     */
    public static function holderOfPosition(int $eventId, string $position, int $registrationId): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT res.*, r.team_name, u.name AS leader_name
             FROM results res
             JOIN registrations r ON r.id = res.registration_id
             JOIN users u ON u.id = r.leader_id
             WHERE res.event_id = ? AND res.position = ? AND res.registration_id != ?
             LIMIT 1"
        );
        $stmt->execute([$eventId, $position, $registrationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(int $id, int $eventId): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM results WHERE id = ? AND event_id = ?');
        $stmt->execute([$id, $eventId]);
    }

    public static function forLeaderWithCertificateInfo(int $leaderId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT res.*, r.team_name, ev.title AS event_title, ev.category, f.name AS fest_name, u.name AS leader_name
             FROM results res
             JOIN registrations r ON r.id = res.registration_id
             JOIN events ev ON ev.id = res.event_id
             JOIN fests f ON f.id = ev.fest_id
             JOIN users u ON u.id = r.leader_id
             WHERE r.leader_id = ?
             ORDER BY res.recorded_at DESC"
        );
        $stmt->execute([$leaderId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT res.*, r.team_name, r.leader_id, ev.title AS event_title, ev.category, f.name AS fest_name, u.name AS leader_name
             FROM results res
             JOIN registrations r ON r.id = res.registration_id
             JOIN events ev ON ev.id = res.event_id
             JOIN fests f ON f.id = ev.fest_id
             JOIN users u ON u.id = r.leader_id
             WHERE res.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allWithContext(): array
    {
        $stmt = Database::connect()->query(
            "SELECT res.*, r.team_name, ev.title AS event_title, f.name AS fest_name, u.name AS leader_name
             FROM results res
             JOIN registrations r ON r.id = res.registration_id
             JOIN events ev ON ev.id = res.event_id
             JOIN fests f ON f.id = ev.fest_id
             JOIN users u ON u.id = r.leader_id
             ORDER BY res.recorded_at DESC"
        );
        return $stmt->fetchAll();
    }
}
