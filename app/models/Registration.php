<?php

require_once __DIR__ . '/../../core/Database.php';

class Registration
{
    public const ERR_EVENT_NOT_BOOKABLE = 'event_not_bookable';
    public const ERR_EVENT_STARTED = 'event_started';
    public const ERR_TEAM_SIZE = 'team_size';
    public const ERR_ALREADY_REGISTERED = 'already_registered';
    public const ERR_FULL = 'full';
    public const ERR_CONFLICT = 'conflict';

    /**
     * Registers $leaderId (+ named teammates) for $eventId. Returns
     * ['ok' => true, 'epass_code' => ...] on success, or
     * ['ok' => false, 'error' => self::ERR_*, 'conflict_event' => ?array] on
     * a rejected attempt. Every rejection is decided server-side inside the
     * same transaction as the insert -- nothing here trusts a client-side
     * "seats remaining" number or a client-side conflict check.
     *
     * Concurrency/integrity mechanic (the technical centerpiece of this
     * project):
     *
     *   1. SELECT ... FOR UPDATE locks the *event* row first. Every
     *      concurrent registration attempt against this same event must
     *      wait for that lock, which serializes them -- turning what would
     *      otherwise be a classic read-then-write race (read seats_taken,
     *      decide it fits, write) into a safe sequence of atomic
     *      transactions. This is the same "row lock as mutex" pattern this
     *      suite already uses for slot booking (project 3) and seat holds
     *      (project 16), applied here to a variable-size team claim instead
     *      of a single seat.
     *   2. While holding that lock, it sums team_size across confirmed
     *      registrations for the event and rejects if this team would push
     *      the total over capacity -- so two simultaneous team
     *      registrations that both observe "2 seats left" can't both
     *      succeed if only one of them actually fits.
     *   3. Independently, it checks every other confirmed registration this
     *      same leader holds *in the same fest* for a start/end overlap
     *      with this event, and rejects on any overlap -- a participant can
     *      never end up double-booked into two events happening at once.
     *   4. The unique (leader_id, event_id) key on `registrations` is a
     *      second line of defense against a double-submit registering the
     *      same leader into the same event twice.
     *   5. Registration is rejected once the event's start_time has passed
     *      (checked against the DB's own NOW()), independent of the fest's
     *      manually-toggled status.
     *
     * All checks + the insert commit or roll back together.
     *
     * @param array<int, array{name:string,email:?string}> $members
     */
    public static function register(int $eventId, int $leaderId, ?string $teamName, array $members): array
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            // Step 1: lock the event row -- this is the mutex.
            $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? FOR UPDATE');
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            if (!$event) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_EVENT_NOT_BOOKABLE];
            }

            $stmt = $pdo->prepare('SELECT status FROM fests WHERE id = ?');
            $stmt->execute([$event['fest_id']]);
            $festStatus = $stmt->fetch()['status'] ?? null;

            // The owning club's status is part of "is this bookable", not just
            // the event's and the fest's: a deactivated club's events must stop
            // accepting registrations on the write path too, not merely drop out
            // of the browse listings.
            $stmt = $pdo->prepare('SELECT status FROM clubs WHERE id = ?');
            $stmt->execute([$event['club_id']]);
            $clubStatus = $stmt->fetch()['status'] ?? null;

            if ($event['status'] !== 'approved' || $festStatus !== 'published' || $clubStatus !== 'active') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_EVENT_NOT_BOOKABLE];
            }

            // Deadline check -- registration only allowed before the event actually
            // starts. Fest status is a manual admin toggle (never auto-flipped by
            // date), so without this an event stays "bookable" indefinitely after
            // its start_time has passed as long as nobody remembers to mark the
            // fest completed. Uses the DB's own clock (NOW()) rather than PHP's, to
            // stay consistent regardless of web-server/DB clock skew, and runs
            // inside the same locked transaction as everything else here.
            $stmt = $pdo->prepare('SELECT NOW() >= ? AS started');
            $stmt->execute([$event['start_time']]);
            if ((bool)$stmt->fetch()['started']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_EVENT_STARTED];
            }

            $teamSize = 1 + count($members);
            if ($teamSize < (int)$event['team_size_min'] || $teamSize > (int)$event['team_size_max']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_TEAM_SIZE];
            }

            // Already registered for this event as leader?
            $stmt = $pdo->prepare("SELECT id FROM registrations WHERE event_id = ? AND leader_id = ? AND status = 'confirmed'");
            $stmt->execute([$eventId, $leaderId]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_ALREADY_REGISTERED];
            }

            // Step 2: race-safe capacity check (event row already locked above).
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(team_size), 0) AS taken FROM registrations WHERE event_id = ? AND status = 'confirmed'");
            $stmt->execute([$eventId]);
            $seatsTaken = (int)$stmt->fetch()['taken'];

            if ($seatsTaken + $teamSize > (int)$event['capacity']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_FULL, 'seats_remaining' => max(0, (int)$event['capacity'] - $seatsTaken)];
            }

            // Step 3: fest-scoped schedule-conflict check against the leader's own registrations.
            $stmt = $pdo->prepare(
                "SELECT ev2.id, ev2.title, ev2.start_time, ev2.end_time
                 FROM registrations r
                 JOIN events ev2 ON ev2.id = r.event_id
                 WHERE r.leader_id = ? AND r.status = 'confirmed' AND ev2.fest_id = ? AND ev2.id != ?
                   AND ev2.start_time < ? AND ? < ev2.end_time
                 LIMIT 1"
            );
            $stmt->execute([$leaderId, $event['fest_id'], $eventId, $event['end_time'], $event['start_time']]);
            $clash = $stmt->fetch();

            if ($clash) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_CONFLICT, 'conflict_event' => $clash];
            }

            // Step 4: insert. The unique (leader_id, event_id) key is a second guard
            // against double-submit -- but it also means a leader who once
            // registered and later cancelled still owns that (leader_id, event_id)
            // slot, just in 'cancelled' state (the "already registered?" check above
            // only matches 'confirmed' rows, so it lets this through). A plain
            // INSERT would then die on that leftover row's unique key and get
            // mistaken for a genuine double-submit below. ON DUPLICATE KEY UPDATE
            // instead revives that same row as a fresh confirmed registration, which
            // is exactly what "register again after cancelling" should do.
            $epassCode = self::generateUniqueEpassCode($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO registrations (event_id, leader_id, team_name, team_size, epass_code, status)
                 VALUES (?, ?, ?, ?, ?, \'confirmed\')
                 ON DUPLICATE KEY UPDATE
                    team_name = VALUES(team_name), team_size = VALUES(team_size),
                    epass_code = VALUES(epass_code), status = \'confirmed\',
                    registered_at = CURRENT_TIMESTAMP, id = LAST_INSERT_ID(id)'
            );
            $stmt->execute([$eventId, $leaderId, $teamName, $teamSize, $epassCode]);
            $registrationId = (int)$pdo->lastInsertId();

            // Revived row may still carry a prior (cancelled) team's members.
            $pdo->prepare('DELETE FROM registration_members WHERE registration_id = ?')->execute([$registrationId]);

            if (!empty($members)) {
                $memberStmt = $pdo->prepare('INSERT INTO registration_members (registration_id, member_name, member_email) VALUES (?, ?, ?)');
                foreach ($members as $member) {
                    $memberStmt->execute([$registrationId, $member['name'], $member['email'] ?: null]);
                }
            }

            $pdo->commit();
            return ['ok' => true, 'registration_id' => $registrationId, 'epass_code' => $epassCode];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // A duplicate-key race on (leader_id, event_id) surfaces here if two
            // double-submits from the same leader land in the same instant.
            if ($e instanceof PDOException && (int)$e->getCode() === 23000) {
                return ['ok' => false, 'error' => self::ERR_ALREADY_REGISTERED];
            }
            throw $e;
        }
    }

    private static function generateUniqueEpassCode(PDO $pdo): string
    {
        do {
            $code = generate_epass_code();
            $stmt = $pdo->prepare('SELECT id FROM registrations WHERE epass_code = ?');
            $stmt->execute([$code]);
        } while ($stmt->fetch());

        return $code;
    }

    public const ERR_CANCEL_STARTED = 'cancel_started';
    public const ERR_CANCEL_HAS_RESULT = 'cancel_has_result';

    /**
     * Cancels a confirmed registration. Returns ['ok' => true] or
     * ['ok' => false, 'error' => self::ERR_*].
     *
     * Cancellation is a *deadline-bounded* action, symmetrical with
     * registration itself (which register() closes once start_time passes).
     * Without that bound a participant could cancel a registration for an event
     * that already ran, which desyncs three views at once: the event's roster
     * and results screen (both scoped to `confirmed`) drop the entry, while the
     * recorded result keeps rendering on the participant's own Certificates
     * page and in the Committee's results report -- a competitor could cancel
     * their way off a club's roster while keeping the winner's certificate.
     * The recorded-result check is belt-and-braces for the same reason.
     */
    public static function cancel(int $registrationId, int $leaderId): array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            "SELECT r.id, NOW() >= ev.start_time AS started,
                    EXISTS(SELECT 1 FROM results res WHERE res.registration_id = r.id) AS has_result
             FROM registrations r JOIN events ev ON ev.id = r.event_id
             WHERE r.id = ? AND r.leader_id = ? AND r.status = 'confirmed'"
        );
        $stmt->execute([$registrationId, $leaderId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['ok' => false, 'error' => 'not_cancellable'];
        }
        if ((bool)$row['has_result']) {
            return ['ok' => false, 'error' => self::ERR_CANCEL_HAS_RESULT];
        }
        if ((bool)$row['started']) {
            return ['ok' => false, 'error' => self::ERR_CANCEL_STARTED];
        }

        $stmt = $pdo->prepare(
            "UPDATE registrations SET status = 'cancelled' WHERE id = ? AND leader_id = ? AND status = 'confirmed'"
        );
        $stmt->execute([$registrationId, $leaderId]);

        return $stmt->rowCount() > 0 ? ['ok' => true] : ['ok' => false, 'error' => 'not_cancellable'];
    }

    /** True once the event this registration belongs to has started (Cancel is hidden past that point). */
    public static function isCancellable(array $registration): bool
    {
        return $registration['status'] === 'confirmed'
            && strtotime($registration['start_time']) > time()
            && empty($registration['has_result']);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT r.*, ev.title AS event_title, ev.venue, ev.start_time, ev.end_time, ev.category, ev.entry_fee,
                    f.name AS fest_name, u.name AS leader_name, u.email AS leader_email, u.college AS leader_college
             FROM registrations r
             JOIN events ev ON ev.id = r.event_id
             JOIN fests f ON f.id = ev.fest_id
             JOIN users u ON u.id = r.leader_id
             WHERE r.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function membersFor(int $registrationId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM registration_members WHERE registration_id = ? ORDER BY id');
        $stmt->execute([$registrationId]);
        return $stmt->fetchAll();
    }

    public static function verifyForEvent(int $eventId, string $epassCode): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT r.*, u.name AS leader_name, u.email AS leader_email
             FROM registrations r
             JOIN users u ON u.id = r.leader_id
             WHERE r.event_id = ? AND r.epass_code = ? AND r.status = 'confirmed'
             LIMIT 1"
        );
        $stmt->execute([$eventId, $epassCode]);
        $registration = $stmt->fetch();
        return $registration ?: null;
    }

    public static function forLeader(int $leaderId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT r.*, ev.title AS event_title, ev.venue, ev.start_time, ev.end_time, ev.category,
                    f.name AS fest_name,
                    EXISTS(SELECT 1 FROM results res WHERE res.registration_id = r.id) AS has_result
             FROM registrations r
             JOIN events ev ON ev.id = r.event_id
             JOIN fests f ON f.id = ev.fest_id
             WHERE r.leader_id = ?
             ORDER BY ev.start_time"
        );
        $stmt->execute([$leaderId]);
        return $stmt->fetchAll();
    }

    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT r.*, u.name AS leader_name, u.email AS leader_email, u.college AS leader_college
             FROM registrations r JOIN users u ON u.id = r.leader_id
             WHERE r.event_id = ? AND r.status = 'confirmed'
             ORDER BY r.registered_at"
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public static function belongsToLeader(int $registrationId, int $leaderId): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM registrations WHERE id = ? AND leader_id = ?');
        $stmt->execute([$registrationId, $leaderId]);
        return (bool)$stmt->fetch();
    }

    public static function count(): int
    {
        return (int)Database::connect()->query("SELECT COUNT(*) AS c FROM registrations WHERE status = 'confirmed'")->fetch()['c'];
    }
}
