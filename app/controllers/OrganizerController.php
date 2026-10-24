<?php

require_once __DIR__ . '/../models/Club.php';
require_once __DIR__ . '/../models/Fest.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Registration.php';
require_once __DIR__ . '/../models/Result.php';

class OrganizerController
{
    /** Loads the logged-in organizer's own club, or 404s if they somehow don't have one. */
    private function myClub(): array
    {
        $club = Club::findByOrganizer(Auth::id());
        if (!$club) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }
        return $club;
    }

    /**
     * Write-path guard for a deactivated club. Read-only screens stay reachable
     * (the organizer needs to see their roster and *why* they're locked out),
     * but every action that changes data is refused -- otherwise "Deactivate
     * club" only hides the club from participants while its organizer carries
     * on proposing events and recording results.
     */
    private function requireActiveClub(array $club, string $backRoute): void
    {
        if (($club['status'] ?? 'active') !== 'active') {
            flash_set('error', 'Your club has been deactivated by the Fest Committee, so it can no longer '
                . 'propose or change events, verify e-passes, or record results. Contact the Committee to reactivate it.');
            redirect($backRoute);
        }
    }

    public function dashboard(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $events = Event::forClub((int)$club['id']);

        $stats = [
            'events'        => count($events),
            'approved'      => count(array_filter($events, fn($e) => $e['status'] === 'approved')),
            'pending'       => count(array_filter($events, fn($e) => $e['status'] === 'pending')),
            'registrations' => array_sum(array_column($events, 'seats_taken')),
        ];

        require __DIR__ . '/../views/organizer/dashboard.php';
    }

    public function events(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $events = Event::forClub((int)$club['id']);
        require __DIR__ . '/../views/organizer/events.php';
    }

    public function eventNew(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');
        $fests = Fest::all();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/organizer/event_form.php';
    }

    public function eventStore(): void
    {
        Auth::requireRole('organizer');
        Csrf::requireValid();
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');

        [$errors, $data] = $this->validateEvent($_POST);

        if (!empty($errors)) {
            $fests = Fest::all();
            $old = $_POST;
            require __DIR__ . '/../views/organizer/event_form.php';
            return;
        }

        $slugBase = slugify($data['title']);
        $slug = $slugBase;
        $suffix = 2;
        while (Event::findBySlug((int)$data['fest_id'], $slug)) {
            $slug = $slugBase . '-' . $suffix++;
        }

        Event::create([
            'fest_id' => $data['fest_id'],
            'club_id' => (int)$club['id'],
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'],
            'category' => $data['category'],
            'venue' => $data['venue'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'capacity' => $data['capacity'],
            'team_size_min' => $data['team_size_min'],
            'team_size_max' => $data['team_size_max'],
            'entry_fee' => $data['entry_fee'],
            'created_by' => Auth::id(),
        ]);

        flash_set('success', 'Event proposed. It will be bookable once the Fest Committee approves it.');
        redirect('organizer/events');
    }

    public function eventEdit(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');
        $id = (int)($_GET['id'] ?? 0);
        $event = Event::find($id);

        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $fests = Fest::all();
        $errors = [];
        $old = $event;
        require __DIR__ . '/../views/organizer/event_form.php';
    }

    public function eventUpdate(): void
    {
        Auth::requireRole('organizer');
        Csrf::requireValid();
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');

        $id = (int)($_POST['id'] ?? 0);
        $event = Event::find($id);
        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        // Lifecycle lock: an event with confirmed registrations is frozen.
        //
        // This is a single rule rather than a per-field one because *any*
        // successful edit here also flips the event back to `pending` for
        // re-approval (below), and a pending event drops straight out of
        // Event::forFestApproved() and 404s in ParticipantController::eventShow().
        // So editing even the description of a live event silently pulls it off
        // the participant-facing schedule while everyone already registered
        // keeps a confirmed row and a printed e-pass pointing at it -- the same
        // "parent state change strands existing children" shape as the
        // capacity/schedule cases this previously guarded one field at a time.
        //
        // $event['seats_taken'] (Event::find's correlated subquery) is the sum
        // of confirmed team sizes, so > 0 means at least one confirmed
        // registration exists.
        $seatsTaken = (int)$event['seats_taken'];

        if ($seatsTaken > 0) {
            flash_set('error', "\"{$event['title']}\" already has {$seatsTaken} confirmed seat(s), so it can no longer be edited: "
                . 'any change sends the event back for Committee re-approval, which would take it off the published schedule '
                . 'while those participants still hold valid e-passes for it. Have the existing registrations cancelled first '
                . 'if the event genuinely needs to change.');
            redirect('organizer/events');
        }

        [$errors, $data] = $this->validateEvent($_POST, (int)$event['fest_id']);

        if (!empty($errors)) {
            flash_set('error', 'Could not update event: ' . implode(' ', $errors));
            redirect('organizer/events');
        }

        if (!Event::update($id, $data)) {
            // Lost a race against a registration that committed between the
            // check above and the write -- Event::update() re-checks capacity
            // against confirmed seats while holding the same event-row lock the
            // registration mutex uses, so it can refuse instead of overselling.
            flash_set('error', 'Could not update event: a registration was confirmed while you were editing, and the new '
                . 'capacity no longer covers the seats already taken. Reload and try again.');
            redirect('organizer/events');
        }

        // Editing a previously-approved/rejected event sends it back to pending review.
        if ($event['status'] !== 'pending') {
            Event::setApprovalStatus($id, 'pending', null);
            flash_set('success', 'Event updated and sent back for Committee re-approval.');
        } else {
            flash_set('success', 'Event updated.');
        }

        redirect('organizer/events');
    }

    public function roster(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $id = (int)($_GET['id'] ?? 0);
        $event = Event::find($id);

        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $registrations = Registration::forEvent($id);
        foreach ($registrations as &$reg) {
            $reg['members'] = Registration::membersFor((int)$reg['id']);
        }
        unset($reg);

        require __DIR__ . '/../views/organizer/roster.php';
    }

    public function verifyEpass(): void
    {
        Auth::requireRole('organizer');
        Csrf::requireValid();
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');
        $eventId = (int)($_POST['event_id'] ?? 0);
        $event = Event::find($eventId);

        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $epassCode = strtoupper(trim((string)($_POST['epass_code'] ?? '')));
        if ($epassCode === '') {
            flash_set('error', 'Enter an e-pass code to verify.');
            redirect('organizer/events/roster?id=' . $eventId);
        }

        $registration = Registration::verifyForEvent($eventId, $epassCode);
        if (!$registration) {
            flash_set('error', 'Invalid or cancelled e-pass for this event.');
        } else {
            flash_set('success', 'Valid e-pass: ' . $registration['leader_name'] . ' (' . $registration['team_size'] . ' member(s)).');
        }
        redirect('organizer/events/roster?id=' . $eventId);
    }

    public function resultsForm(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $id = (int)($_GET['id'] ?? 0);
        $event = Event::find($id);

        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $registrations = Registration::forEvent($id);
        $results = Result::forEvent($id);
        $resultsByRegistration = [];
        foreach ($results as $r) {
            $resultsByRegistration[(int)$r['registration_id']] = $r;
        }

        require __DIR__ . '/../views/organizer/results.php';
    }

    public function resultStore(): void
    {
        Auth::requireRole('organizer');
        Csrf::requireValid();
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');

        $eventId = (int)($_POST['event_id'] ?? 0);
        $registrationId = (int)($_POST['registration_id'] ?? 0);
        $position = $_POST['position'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        $event = Event::find($eventId);
        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!in_array($position, ['winner', 'runner_up', 'second_runner_up', 'participant'], true)) {
            flash_set('error', 'Invalid result position.');
            redirect('organizer/events/results?id=' . $eventId);
        }

        // An event's outcome can't be known before it has actually run --
        // without this an organizer could declare a "winner" for an event that
        // hasn't even started yet. Uses the DB's own clock, consistent with the
        // same-shaped check in Registration::register().
        $stmt = Database::connect()->prepare('SELECT NOW() < ? AS not_ended');
        $stmt->execute([$event['end_time']]);
        if ((bool)$stmt->fetch()['not_ended']) {
            flash_set('error', 'Results can only be recorded once the event has ended (' . format_datetime($event['end_time']) . ').');
            redirect('organizer/events/results?id=' . $eventId);
        }

        // Verify the registration actually belongs to this event.
        $regs = Registration::forEvent($eventId);
        $valid = array_filter($regs, fn($r) => (int)$r['id'] === $registrationId);
        if (empty($valid)) {
            flash_set('error', 'That registration does not belong to this event.');
            redirect('organizer/events/results?id=' . $eventId);
        }

        // A rank (as opposed to the plain "participant" position) has to be
        // unique per event -- otherwise two different teams could both be
        // recorded as "winner" of the same competition.
        if ($position !== 'participant') {
            $holder = Result::holderOfPosition($eventId, $position, $registrationId);
            if ($holder) {
                flash_set('error', '"' . ($holder['team_name'] ?: $holder['leader_name']) . '" is already recorded as '
                    . str_replace('_', ' ', $position) . ' for this event. Remove that result first if this is a correction.');
                redirect('organizer/events/results?id=' . $eventId);
            }
        }

        Result::record($eventId, $registrationId, $position, $remarks ?: null, Auth::id());
        flash_set('success', 'Result recorded.');
        redirect('organizer/events/results?id=' . $eventId);
    }

    public function resultDelete(): void
    {
        Auth::requireRole('organizer');
        Csrf::requireValid();
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/events');

        $resultId = (int)($_POST['id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        $event = Event::find($eventId);

        if (!$event || (int)$event['club_id'] !== (int)$club['id']) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        Result::delete($resultId, $eventId);
        flash_set('success', 'Result entry removed.');
        redirect('organizer/events/results?id=' . $eventId);
    }

    public function profile(): void
    {
        Auth::requireRole('organizer');
        $club = $this->myClub();
        $errors = [];
        require __DIR__ . '/../views/organizer/profile.php';
    }

    public function profileUpdate(): void
    {
        Auth::requireRole('organizer');
        Csrf::requireValid();
        $club = $this->myClub();
        $this->requireActiveClub($club, 'organizer/club');

        $description = trim($_POST['description'] ?? '');
        $v = new Validator();
        $v->required($_POST, 'description', 'Description')->maxLength($_POST, 'description', 500, 'Description');

        if ($v->fails()) {
            flash_set('error', 'Description is required (max 500 characters).');
            redirect('organizer/club');
        }

        Club::updateDescription((int)$club['id'], $description);
        flash_set('success', 'Club profile updated.');
        redirect('organizer/club');
    }

    public function browseFests(): void
    {
        Auth::requireRole('organizer');
        $fests = Fest::published();
        require __DIR__ . '/../views/organizer/browse_fests.php';
    }

    public function browseFestShow(): void
    {
        Auth::requireRole('organizer');
        $id = (int)($_GET['id'] ?? 0);
        $fest = Fest::find($id);

        if (!$fest || $fest['status'] === 'draft') {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $events = Event::forFestApproved($id);
        require __DIR__ . '/../views/organizer/browse_fest_show.php';
    }

    private function validateEvent(array $post, ?int $existingFestId = null): array
    {
        $v = new Validator();
        $v->required($post, 'fest_id', 'Fest')
          ->required($post, 'title', 'Title')->maxLength($post, 'title', 150, 'Title')
          ->required($post, 'description', 'Description')
          ->inArray($post, 'category', ['competition', 'workshop', 'show', 'seminar'], 'Category')
          ->required($post, 'venue', 'Venue')->maxLength($post, 'venue', 150, 'Venue')
          ->required($post, 'start_time', 'Start time')->datetime($post, 'start_time', 'Start time')
          ->required($post, 'end_time', 'End time')->datetime($post, 'end_time', 'End time')
          ->integerMin($post, 'capacity', 1, 'Capacity')
          ->integerMin($post, 'team_size_min', 1, 'Minimum team size')
          ->integerMin($post, 'team_size_max', 1, 'Maximum team size');

        $errors = $v->errors();

        $festId = $existingFestId ?? (int)($post['fest_id'] ?? 0);
        $fest = empty($errors['fest_id']) ? Fest::find($festId) : null;
        if (empty($errors['fest_id']) && !$fest) {
            $errors['fest_id'] = 'Please choose a valid fest.';
        }

        $startTime = !empty($post['start_time']) ? str_replace('T', ' ', $post['start_time']) . ':00' : '';
        $endTime = !empty($post['end_time']) ? str_replace('T', ' ', $post['end_time']) . ':00' : '';

        if (empty($errors['start_time']) && empty($errors['end_time']) && strtotime($endTime) <= strtotime($startTime)) {
            $errors['end_time'] = 'End time must be after the start time.';
        }

        // An event has to actually fall inside the fest it belongs to -- without
        // this an organizer can schedule (or edit) an event to any date, and it
        // then shows up under a fest whose own published date range says
        // something else entirely to participants browsing it.
        if ($fest && empty($errors['start_time']) && empty($errors['end_time'])) {
            $festStart = strtotime($fest['start_date'] . ' 00:00:00');
            $festEnd = strtotime($fest['end_date'] . ' 23:59:59');
            if (strtotime($startTime) < $festStart || strtotime($endTime) > $festEnd) {
                $errors['start_time'] = 'Event must fall within the fest\'s dates ('
                    . format_date($fest['start_date']) . ' - ' . format_date($fest['end_date']) . ').';
            }
        }

        $teamMin = (int)($post['team_size_min'] ?? 1);
        $teamMax = (int)($post['team_size_max'] ?? 1);
        if (empty($errors['team_size_min']) && empty($errors['team_size_max']) && $teamMax < $teamMin) {
            $errors['team_size_max'] = 'Maximum team size cannot be less than the minimum.';
        }

        $capacity = (int)($post['capacity'] ?? 0);
        if (empty($errors['capacity']) && $teamMax > 0 && $capacity < $teamMax) {
            $errors['capacity'] = 'Capacity must be at least the maximum team size.';
        }

        $entryFee = trim((string)($post['entry_fee'] ?? '0'));
        if ($entryFee === '' || !is_numeric($entryFee) || (float)$entryFee < 0) {
            $errors['entry_fee'] = 'Entry fee must be a non-negative number.';
            $entryFee = '0';
        }

        return [$errors, [
            'fest_id' => $festId,
            'title' => trim($post['title'] ?? ''),
            'description' => trim($post['description'] ?? ''),
            'category' => $post['category'] ?? '',
            'venue' => trim($post['venue'] ?? ''),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'capacity' => $capacity,
            'team_size_min' => $teamMin,
            'team_size_max' => $teamMax,
            'entry_fee' => (float)$entryFee,
        ]];
    }
}
