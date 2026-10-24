<?php

require_once __DIR__ . '/Database.php';

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login');
        }

        // Account status is re-read from the database on *every* request, not
        // just at login. Checking it only in attempt() means an account
        // deactivated mid-session keeps full access until the user happens to
        // log out -- the session carries no status of its own. Deactivating an
        // account has to take effect on that account's very next request.
        $stmt = Database::connect()->prepare('SELECT status FROM users WHERE id = ?');
        $stmt->execute([self::id()]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] !== 'active') {
            self::logout();
            session_start();
            flash_set('error', 'Your account is no longer active. Contact the Fest Committee.');
            redirect('login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            require __DIR__ . '/../app/views/errors/404.php';
            exit;
        }
    }

    public static function dashboardRoute(string $role): string
    {
        return match ($role) {
            'super_admin' => 'admin/dashboard',
            'organizer'   => 'organizer/dashboard',
            'participant' => 'participant/dashboard',
            default       => 'login',
        };
    }
}
