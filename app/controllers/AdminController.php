<?php

require_once __DIR__ . '/../models/Fest.php';
require_once __DIR__ . '/../models/Club.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Registration.php';
require_once __DIR__ . '/../models/Result.php';
require_once __DIR__ . '/../models/User.php';

class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole('super_admin');

        $stats = [
            'fests'         => Fest::count(),
            'clubs'         => Club::count(),
            'events'        => Event::count(),
            'events_pending'=> Event::countByStatus('pending'),
            'registrations' => Registration::count(),
            'participants'  => User::countByRole('participant'),
        ];
        $fests = Fest::all();
        $pendingEvents = Event::pending();
        $clashes = Event::venueClashes();

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // ---- Fests ----

    public function fests(): void
    {
        Auth::requireRole('super_admin');
        $fests = Fest::all();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/admin/fests.php';
    }

    public function festStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        [$errors, $data] = $this->validateFest($_POST);

        if (!empty($errors)) {
            $fests = Fest::all();
            $old = $_POST;
            require __DIR__ . '/../views/admin/fests.php';
            return;
        }

        $slug = slugify($data['name']);
        Fest::create($data['name'], $slug, $data['description'], $data['theme'], $data['start_date'], $data['end_date'], $data['venue'], Auth::id());
        flash_set('success', 'Fest created as a draft. Publish it once you are ready for organizers/participants to see it.');
        redirect('admin/fests');
    }

    public function festUpdate(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $fest = Fest::find($id);
        if (!$fest) {
            flash_set('error', 'Fest not found.');
            redirect('admin/fests');
        }

        [$errors, $data] = $this->validateFest($_POST);

        // Lifecycle lock: once any event in this fest has a confirmed
        // registration, the fest's own date range can't move -- events (and
        // the participants registered into them) were scheduled inside it.
        $datesChanged = $data['start_date'] !== $fest['start_date'] || $data['end_date'] !== $fest['end_date'];
        if (empty($errors) && $datesChanged && Fest::hasConfirmedRegistrations($id)) {
            $errors['start_date'] = 'Fest dates cannot be changed once any of its events has a confirmed registration.';
        }

        if (!empty($errors)) {
            flash_set('error', 'Could not update fest: ' . implode(' ', $errors));
            redirect('admin/fests');
        }

        Fest::update($id, $data['name'], $data['description'], $data['theme'], $data['start_date'], $data['end_date'], $data['venue']);
        flash_set('success', 'Fest updated.');
        redirect('admin/fests');
    }

    public function festStatus(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $fest = Fest::find($id);

        if (!$fest || !in_array($status, ['draft', 'published', 'completed'], true)) {
            flash_set('error', 'Invalid fest status change.');
            redirect('admin/fests');
        }

        // Un-publishing strands everyone already registered: a draft fest drops
        // out of Fest::published(), and both festShow() and eventShow() 404 on
        // it, so participants holding confirmed registrations and printed
        // e-passes lose every page that shows them -- while the registrations
        // themselves stay confirmed. "Completed" is the correct way to close a
        // fest that has run; it stays visible and simply stops taking new
        // registrations.
        if ($status === 'draft' && $fest['status'] !== 'draft' && Fest::hasConfirmedRegistrations($id)) {
            $count = Fest::confirmedRegistrationCount($id);
            flash_set('error', "\"{$fest['name']}\" can't be reverted to draft: {$count} confirmed registration(s) already "
                . 'exist across its events, and un-publishing would hide the fest and every event from the participants '
                . 'holding them. Mark it Completed instead if the fest is over.');
            redirect('admin/fests');
        }

        Fest::setStatus($id, $status);
        flash_set('success', 'Fest status updated to "' . $status . '".');
        redirect('admin/fests');
    }

    private function validateFest(array $post): array
    {
        $v = new Validator();
        $v->required($post, 'name', 'Name')->maxLength($post, 'name', 150, 'Name')
          ->required($post, 'description', 'Description')
          ->required($post, 'theme', 'Theme')->maxLength($post, 'theme', 100, 'Theme')
          ->required($post, 'start_date', 'Start date')->datetime($post, 'start_date', 'Start date')
          ->required($post, 'end_date', 'End date')->datetime($post, 'end_date', 'End date')
          ->required($post, 'venue', 'Venue')->maxLength($post, 'venue', 150, 'Venue');

        $errors = $v->errors();

        if (empty($errors['start_date']) && empty($errors['end_date'])) {
            if (strtotime($post['end_date']) < strtotime($post['start_date'])) {
                $errors['end_date'] = 'End date cannot be before the start date.';
            }
        }

        return [$errors, [
            'name' => trim($post['name'] ?? ''),
            'description' => trim($post['description'] ?? ''),
            'theme' => trim($post['theme'] ?? ''),
            'start_date' => $post['start_date'] ?? '',
            'end_date' => $post['end_date'] ?? '',
            'venue' => trim($post['venue'] ?? ''),
        ]];
    }

    // ---- Clubs ----

    public function clubs(): void
    {
        Auth::requireRole('super_admin');
        $clubs = Club::all();
        $availableOrganizers = User::organizersWithoutClub();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/admin/clubs.php';
    }

    public function clubStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 120, 'Name')
          ->required($_POST, 'description', 'Description')->maxLength($_POST, 'description', 500, 'Description')
          ->required($_POST, 'organizer_id', 'Organizer account');

        $errors = $v->errors();
        $organizerId = (int)($_POST['organizer_id'] ?? 0);

        if (empty($errors)) {
            $organizer = User::find($organizerId);
            if (!$organizer || $organizer['role'] !== 'organizer') {
                $errors['organizer_id'] = 'Choose a valid organizer account.';
            } elseif (Club::findByOrganizer($organizerId)) {
                $errors['organizer_id'] = 'That organizer already manages a club.';
            }
        }

        if (!empty($errors)) {
            flash_set('error', 'Could not create club: ' . implode(' ', $errors));
            redirect('admin/clubs');
        }

        $slug = slugify(trim($_POST['name']));
        Club::create(trim($_POST['name']), $slug, trim($_POST['description']), $organizerId);
        flash_set('success', 'Club created.');
        redirect('admin/clubs');
    }

    public function clubStatus(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $club = Club::find($id);

        if (!$club || !in_array($status, ['active', 'inactive'], true)) {
            flash_set('error', 'Invalid club status change.');
            redirect('admin/clubs');
        }

        Club::setStatus($id, $status);
        flash_set('success', 'Club status updated.');
        redirect('admin/clubs');
    }

    // ---- Event approvals ----

    public function events(): void
    {
        Auth::requireRole('super_admin');
        $filter = $_GET['status'] ?? 'pending';
        if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'], true)) {
            $filter = 'pending';
        }

        $allEvents = Event::allWithContext();
        $events = $filter === 'all' ? $allEvents : array_values(array_filter($allEvents, fn($e) => $e['status'] === $filter));

        require __DIR__ . '/../views/admin/events.php';
    }

    public function eventApprove(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (!Event::find($id)) {
            flash_set('error', 'Event not found.');
            redirect('admin/events');
        }

        Event::setApprovalStatus($id, 'approved', null);
        flash_set('success', 'Event approved. It is now bookable if its fest is published.');
        redirect('admin/events');
    }

    public function eventReject(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $event = Event::find($id);
        if (!$event) {
            flash_set('error', 'Event not found.');
            redirect('admin/events');
        }
        if ($reason === '') {
            flash_set('error', 'Please provide a rejection reason.');
            redirect('admin/events');
        }

        // Defence in depth on the same stranding shape guarded in
        // OrganizerController::eventUpdate(): a rejected event disappears from
        // the participant-facing schedule, so rejecting one that already has
        // confirmed registrations would leave those e-passes pointing at a
        // page that 404s.
        if ((int)$event['seats_taken'] > 0) {
            flash_set('error', "\"{$event['title']}\" already has {$event['seats_taken']} confirmed seat(s) and can't be rejected — "
                . 'those participants hold valid e-passes for it. Their registrations have to be cancelled first.');
            redirect('admin/events');
        }

        Event::setApprovalStatus($id, 'rejected', $reason);
        flash_set('success', 'Event rejected.');
        redirect('admin/events');
    }

    // ---- Reports ----

    public function clashes(): void
    {
        Auth::requireRole('super_admin');
        $clashes = Event::venueClashes();
        require __DIR__ . '/../views/admin/clashes.php';
    }

    public function results(): void
    {
        Auth::requireRole('super_admin');
        $results = Result::allWithContext();
        require __DIR__ . '/../views/admin/results.php';
    }
}
