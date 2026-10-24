<?php

require_once __DIR__ . '/../../core/Database.php';

class Event
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT ev.*, f.name AS fest_name, f.slug AS fest_slug, f.status AS fest_status,
                    c.name AS club_name, c.status AS club_status,
                    (SELECT COALESCE(SUM(r.team_size), 0) FROM registrations r WHERE r.event_id = ev.id AND r.status = 'confirmed') AS seats_taken
             FROM events ev
             JOIN fests f ON f.id = ev.fest_id
             JOIN clubs c ON c.id = ev.club_id
             WHERE ev.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBySlug(int $festId, string $slug): ?array
    {
        $stmt = Database::connect()->prepare('SELECT id FROM events WHERE fest_id = ? AND slug = ?');
        $stmt->execute([$festId, $slug]);
        $row = $stmt->fetch();
        return $row ? self::find((int)$row['id']) : null;
    }

    /**
     * Participant/organizer-facing listing of an approved fest's events.
     * Deliberately joins on the owning club's status as well: deactivating a
     * club has to actually take its events off the schedule, otherwise the
     * Committee's "Deactivate" button is cosmetic and a disbanded club's
     * events stay browsable and bookable.
     */
    public static function forFestApproved(int $festId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT ev.*, c.name AS club_name, c.status AS club_status,
                    (SELECT COALESCE(SUM(r.team_size), 0) FROM registrations r WHERE r.event_id = ev.id AND r.status = 'confirmed') AS seats_taken
             FROM events ev JOIN clubs c ON c.id = ev.club_id
             WHERE ev.fest_id = ? AND ev.status = 'approved' AND c.status = 'active'
             ORDER BY ev.start_time"
        );
        $stmt->execute([$festId]);
        return $stmt->fetchAll();
    }

    public static function forFestAll(int $festId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT ev.*, c.name AS club_name,
                    (SELECT COALESCE(SUM(r.team_size), 0) FROM registrations r WHERE r.event_id = ev.id AND r.status = 'confirmed') AS seats_taken
             FROM events ev JOIN clubs c ON c.id = ev.club_id
             WHERE ev.fest_id = ?
             ORDER BY ev.start_time"
        );
        $stmt->execute([$festId]);
        return $stmt->fetchAll();
    }

    public static function forClub(int $clubId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT ev.*, f.name AS fest_name, f.status AS fest_status,
                    (SELECT COALESCE(SUM(r.team_size), 0) FROM registrations r WHERE r.event_id = ev.id AND r.status = 'confirmed') AS seats_taken
             FROM events ev JOIN fests f ON f.id = ev.fest_id
             WHERE ev.club_id = ?
             ORDER BY ev.start_time DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public static function pending(): array
    {
        $stmt = Database::connect()->query(
            "SELECT ev.*, f.name AS fest_name, c.name AS club_name
             FROM events ev JOIN fests f ON f.id = ev.fest_id JOIN clubs c ON c.id = ev.club_id
             WHERE ev.status = 'pending'
             ORDER BY ev.created_at"
        );
        return $stmt->fetchAll();
    }

    public static function allWithContext(): array
    {
        $stmt = Database::connect()->query(
            "SELECT ev.*, f.name AS fest_name, c.name AS club_name,
                    (SELECT COALESCE(SUM(r.team_size), 0) FROM registrations r WHERE r.event_id = ev.id AND r.status = 'confirmed') AS seats_taken
             FROM events ev JOIN fests f ON f.id = ev.fest_id JOIN clubs c ON c.id = ev.club_id
             ORDER BY ev.start_time"
        );
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO events (fest_id, club_id, title, slug, description, category, venue, start_time, end_time, capacity, team_size_min, team_size_max, entry_fee, created_by)
             VALUES (:fest_id, :club_id, :title, :slug, :description, :category, :venue, :start_time, :end_time, :capacity, :team_size_min, :team_size_max, :entry_fee, :created_by)'
        );
        $stmt->execute($data);
        return (int)Database::connect()->lastInsertId();
    }

    /**
     * Updates an event's editable columns. Returns false if the write was
     * refused because the new capacity no longer covers the seats already
     * confirmed.
     *
     * Two things worth noting:
     *
     *  1. The parameter array is built explicitly from $data rather than
     *     passing $data straight to execute(). $data comes from
     *     OrganizerController::validateEvent(), which also carries a `fest_id`
     *     key -- and fest_id is deliberately *not* updatable here. Handing PDO
     *     an array with a key that has no matching placeholder is an
     *     HY093 "Invalid parameter number" fatal when emulated prepares are
     *     off (as they are in Database::connect()), which is exactly what used
     *     to happen: every otherwise-valid event edit died with a 500.
     *
     *  2. The capacity re-check runs inside a transaction that takes the same
     *     `SELECT ... FOR UPDATE` lock on the event row that
     *     Registration::register() uses as its mutex. Without it, an organizer
     *     lowering capacity and a participant registering can interleave --
     *     both read "this fits", both commit, and the event ends up oversold
     *     with confirmed seats above capacity. Sharing the lock makes the two
     *     serialize against each other.
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM events WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare("SELECT COALESCE(SUM(team_size), 0) AS taken FROM registrations WHERE event_id = ? AND status = 'confirmed'");
            $stmt->execute([$id]);
            $seatsTaken = (int)$stmt->fetch()['taken'];

            if ((int)$data['capacity'] < $seatsTaken) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare(
                'UPDATE events SET title = :title, description = :description, category = :category, venue = :venue,
                        start_time = :start_time, end_time = :end_time, capacity = :capacity,
                        team_size_min = :team_size_min, team_size_max = :team_size_max, entry_fee = :entry_fee
                 WHERE id = :id'
            );
            $stmt->execute([
                'title'         => $data['title'],
                'description'   => $data['description'],
                'category'      => $data['category'],
                'venue'         => $data['venue'],
                'start_time'    => $data['start_time'],
                'end_time'      => $data['end_time'],
                'capacity'      => $data['capacity'],
                'team_size_min' => $data['team_size_min'],
                'team_size_max' => $data['team_size_max'],
                'entry_fee'     => $data['entry_fee'],
                'id'            => $id,
            ]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function setApprovalStatus(int $id, string $status, ?string $reason): void
    {
        $stmt = Database::connect()->prepare('UPDATE events SET status = ?, rejection_reason = ? WHERE id = ?');
        $stmt->execute([$status, $reason, $id]);
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) AS c FROM events')->fetch()['c'];
    }

    public static function countByStatus(string $status): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM events WHERE status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetch()['c'];
    }

    /**
     * Venue/time clash report across every *approved* event: two approved
     * events sharing the same venue with overlapping time ranges. Purely a
     * read-side report for the Committee -- it does not block anything on
     * its own (an Organizer's own event's schedule fields aren't
     * venue-checked against other clubs' events at proposal time), it just
     * surfaces double-booked venues for a human to resolve before the fest.
     */
    public static function venueClashes(): array
    {
        $stmt = Database::connect()->query(
            "SELECT a.id AS event_a_id, a.title AS event_a_title, a.start_time AS a_start, a.end_time AS a_end,
                    b.id AS event_b_id, b.title AS event_b_title, b.start_time AS b_start, b.end_time AS b_end,
                    a.venue, f.name AS fest_name
             FROM events a
             JOIN events b ON a.fest_id = b.fest_id AND a.venue = b.venue AND a.id < b.id
             JOIN fests f ON f.id = a.fest_id
             WHERE a.status = 'approved' AND b.status = 'approved'
               AND a.start_time < b.end_time AND b.start_time < a.end_time
             ORDER BY f.name, a.venue, a.start_time"
        );
        return $stmt->fetchAll();
    }
}
