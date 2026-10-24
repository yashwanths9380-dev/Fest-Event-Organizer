<?php

require_once __DIR__ . '/../models/Fest.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Registration.php';
require_once __DIR__ . '/../models/Result.php';

class ParticipantController
{
    public function dashboard(): void
    {
        Auth::requireRole('participant');
        $registrations = Registration::forLeader(Auth::id());
        $upcoming = array_values(array_filter($registrations, fn($r) => $r['status'] === 'confirmed' && strtotime($r['start_time']) >= time()));
        $certificates = Result::forLeaderWithCertificateInfo(Auth::id());

        require __DIR__ . '/../views/participant/dashboard.php';
    }

    public function fests(): void
    {
        Auth::requireRole('participant');
        $fests = Fest::published();
        require __DIR__ . '/../views/participant/fests.php';
    }

    public function festShow(): void
    {
        Auth::requireRole('participant');
        $id = (int)($_GET['id'] ?? 0);
        $fest = Fest::find($id);

        if (!$fest || $fest['status'] === 'draft') {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $category = $_GET['category'] ?? '';
        $events = Event::forFestApproved($id);
        if ($category !== '') {
            $events = array_values(array_filter($events, fn($e) => $e['category'] === $category));
        }

        $myRegisteredEventIds = [];
        $stmt = Database::connect()->prepare("SELECT event_id FROM registrations WHERE leader_id = ? AND status = 'confirmed'");
        $stmt->execute([Auth::id()]);
        $myRegisteredEventIds = array_map('intval', array_column($stmt->fetchAll(), 'event_id'));

        require __DIR__ . '/../views/participant/fest_show.php';
    }

    public function eventShow(): void
    {
        Auth::requireRole('participant');
        $id = (int)($_GET['id'] ?? 0);
        $event = Event::find($id);

        if (!$event || $event['status'] !== 'approved' || $event['club_status'] !== 'active'
            || !in_array($event['fest_status'], ['published', 'completed'], true)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $alreadyRegistered = false;
        $stmt = Database::connect()->prepare("SELECT id FROM registrations WHERE event_id = ? AND leader_id = ? AND status = 'confirmed'");
        $stmt->execute([$id, Auth::id()]);
        $alreadyRegistered = (bool)$stmt->fetch();

        $errors = [];
        $old = [];
        require __DIR__ . '/../views/participant/event_show.php';
    }

    public function register(): void
    {
        Auth::requireRole('participant');
        Csrf::requireValid();

        $eventId = (int)($_POST['event_id'] ?? 0);
        $event = Event::find($eventId);

        if (!$event) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $teamName = trim($_POST['team_name'] ?? '') ?: null;
        $members = [];
        $names = $_POST['member_name'] ?? [];
        $emails = $_POST['member_email'] ?? [];
        foreach ($names as $i => $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }
            $email = trim((string)($emails[$i] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash_set('error', 'One of the teammate emails is not valid.');
                redirect('participant/events/show?id=' . $eventId);
            }
            $members[] = ['name' => $name, 'email' => $email ?: null];
        }

        $result = Registration::register($eventId, Auth::id(), $teamName, $members);

        if ($result['ok']) {
            flash_set('success', 'Registered! Your e-pass code is ' . $result['epass_code'] . '.');
            redirect('participant/registrations/epass?id=' . $result['registration_id']);
        }

        $message = match ($result['error']) {
            Registration::ERR_EVENT_NOT_BOOKABLE => 'This event is not open for registration.',
            Registration::ERR_EVENT_STARTED => 'Registration is closed — this event has already started.',
            Registration::ERR_TEAM_SIZE => 'Your team size does not fit this event\'s allowed range (' . $event['team_size_min'] . '-' . $event['team_size_max'] . ').',
            Registration::ERR_ALREADY_REGISTERED => 'You are already registered for this event.',
            Registration::ERR_FULL => 'Sorry, only ' . ($result['seats_remaining'] ?? 0) . ' seat(s) remain and your team does not fit.',
            Registration::ERR_CONFLICT => 'Schedule conflict: you are already registered for "' . ($result['conflict_event']['title'] ?? 'another event') . '" which overlaps this time slot.',
            default => 'Registration could not be completed.',
        };
        flash_set('error', $message);
        redirect('participant/events/show?id=' . $eventId);
    }

    public function registrations(): void
    {
        Auth::requireRole('participant');
        $registrations = Registration::forLeader(Auth::id());
        require __DIR__ . '/../views/participant/registrations.php';
    }

    public function registrationCancel(): void
    {
        Auth::requireRole('participant');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $result = Registration::cancel($id, Auth::id());

        if ($result['ok']) {
            flash_set('success', 'Registration cancelled.');
        } else {
            flash_set('error', match ($result['error']) {
                Registration::ERR_CANCEL_STARTED    => 'This event has already started, so its registration can no longer be cancelled.',
                Registration::ERR_CANCEL_HAS_RESULT => 'A result has already been recorded for this registration, so it can no longer be cancelled.',
                default                             => 'Could not cancel that registration.',
            });
        }
        redirect('participant/registrations');
    }

    public function epass(): void
    {
        Auth::requireRole('participant');
        $id = (int)($_GET['id'] ?? 0);
        $registration = Registration::find($id);

        if (!$registration || (int)$registration['leader_id'] !== Auth::id()) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $members = Registration::membersFor($id);
        require __DIR__ . '/../views/participant/epass.php';
    }

    public function certificates(): void
    {
        Auth::requireRole('participant');
        $certificates = Result::forLeaderWithCertificateInfo(Auth::id());
        require __DIR__ . '/../views/participant/certificates.php';
    }

    public function certificateShow(): void
    {
        Auth::requireRole('participant');
        $id = (int)($_GET['id'] ?? 0);
        $certificate = Result::find($id);

        if (!$certificate || (int)$certificate['leader_id'] !== Auth::id()) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        require __DIR__ . '/../views/participant/certificate_show.php';
    }
}
