<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        Csrf::requireValid();

        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $v = new Validator();
        $v->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password');

        if ($v->fails()) {
            $errors = $v->errors();
            $old = $_POST;
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        if (!Auth::attempt($email, $password)) {
            $errors = ['login' => 'Invalid email/password, or the account is inactive.'];
            $old = $_POST;
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        flash_set('success', 'Welcome back, ' . Auth::name() . '.');
        redirect(Auth::dashboardRoute(Auth::role()));
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void
    {
        Csrf::requireValid();

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 100, 'Name')
          ->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password')->minLength($_POST, 'password', 8, 'Password')
          ->required($_POST, 'college', 'College')->maxLength($_POST, 'college', 150, 'College');

        $errors = $v->errors();
        if (empty($errors['email']) && User::emailExists(trim($_POST['email'] ?? ''))) {
            $errors['email'] = 'That email is already registered.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        $id = User::createParticipant(
            trim($_POST['name']),
            trim($_POST['email']),
            $_POST['password'],
            trim($_POST['college']),
            trim($_POST['phone'] ?? '') ?: null
        );

        Auth::attempt(trim($_POST['email']), $_POST['password']);
        flash_set('success', 'Account created! Welcome to the fest.');
        redirect('participant/dashboard');
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        redirect('login');
    }
}
