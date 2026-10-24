<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

// A distinct session name keeps this app's session isolated from sibling
// projects in the same suite -- PHP's default "PHPSESSID" cookie isn't
// port-scoped on 127.0.0.1/localhost, so two suite projects running their
// dev servers at once would otherwise share one session (and, since the
// default file-based session handler keys purely by session ID with no
// per-app namespace, actually share session *data* too).
session_name('fest_event_organizer_sess');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Club.php';
require_once __DIR__ . '/../app/models/Fest.php';
require_once __DIR__ . '/../app/models/Event.php';
require_once __DIR__ . '/../app/models/Registration.php';
require_once __DIR__ . '/../app/models/Result.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/OrganizerController.php';
require_once __DIR__ . '/../app/controllers/ParticipantController.php';

$router = new Router();

// ---- Auth ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->get('register', fn() => (new AuthController())->showRegister());
$router->post('register', fn() => (new AuthController())->register());
$router->post('logout', fn() => (new AuthController())->logout());

// ---- Super Admin (Fest Committee) ----
$router->get('admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('admin/fests', fn() => (new AdminController())->fests());
$router->post('admin/fests/store', fn() => (new AdminController())->festStore());
$router->post('admin/fests/update', fn() => (new AdminController())->festUpdate());
$router->post('admin/fests/status', fn() => (new AdminController())->festStatus());
$router->get('admin/clubs', fn() => (new AdminController())->clubs());
$router->post('admin/clubs/store', fn() => (new AdminController())->clubStore());
$router->post('admin/clubs/status', fn() => (new AdminController())->clubStatus());
$router->get('admin/events', fn() => (new AdminController())->events());
$router->post('admin/events/approve', fn() => (new AdminController())->eventApprove());
$router->post('admin/events/reject', fn() => (new AdminController())->eventReject());
$router->get('admin/clashes', fn() => (new AdminController())->clashes());
$router->get('admin/results', fn() => (new AdminController())->results());

// ---- Organizer ----
$router->get('organizer/dashboard', fn() => (new OrganizerController())->dashboard());
$router->get('organizer/events', fn() => (new OrganizerController())->events());
$router->get('organizer/events/new', fn() => (new OrganizerController())->eventNew());
$router->post('organizer/events/store', fn() => (new OrganizerController())->eventStore());
$router->get('organizer/events/edit', fn() => (new OrganizerController())->eventEdit());
$router->post('organizer/events/update', fn() => (new OrganizerController())->eventUpdate());
$router->get('organizer/events/roster', fn() => (new OrganizerController())->roster());
$router->post('organizer/registrations/verify', fn() => (new OrganizerController())->verifyEpass());
$router->get('organizer/events/results', fn() => (new OrganizerController())->resultsForm());
$router->post('organizer/results/store', fn() => (new OrganizerController())->resultStore());
$router->post('organizer/results/delete', fn() => (new OrganizerController())->resultDelete());
$router->get('organizer/club', fn() => (new OrganizerController())->profile());
$router->post('organizer/club/update', fn() => (new OrganizerController())->profileUpdate());
$router->get('organizer/fests', fn() => (new OrganizerController())->browseFests());
$router->get('organizer/fests/show', fn() => (new OrganizerController())->browseFestShow());

// ---- Participant ----
$router->get('participant/dashboard', fn() => (new ParticipantController())->dashboard());
$router->get('participant/fests', fn() => (new ParticipantController())->fests());
$router->get('participant/fests/show', fn() => (new ParticipantController())->festShow());
$router->get('participant/events/show', fn() => (new ParticipantController())->eventShow());
$router->post('participant/events/register', fn() => (new ParticipantController())->register());
$router->get('participant/registrations', fn() => (new ParticipantController())->registrations());
$router->post('participant/registrations/cancel', fn() => (new ParticipantController())->registrationCancel());
$router->get('participant/registrations/epass', fn() => (new ParticipantController())->epass());
$router->get('participant/certificates', fn() => (new ParticipantController())->certificates());
$router->get('participant/certificates/show', fn() => (new ParticipantController())->certificateShow());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    redirect('login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
