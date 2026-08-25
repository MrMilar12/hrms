<?php
// Handles login/logout, and 2FA verification, for the auth module.

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth', 'login', ['error' => null]);
    }

    public function login(): void
    {
        $token = $this->input('csrf_token', '');
        if (!Auth::verifyCsrfToken($token)) {
            $this->view('auth', 'login', ['error' => 'Invalid session token. Please try again.']);
            return;
        }

        $username = Validator::sanitizeString($this->input('username', ''));
        $password = (string) $this->input('password', '');

        if ($username === '' || $password === '') {
            $this->view('auth', 'login', ['error' => 'Username and password are required.']);
            return;
        }

        switch (Auth::attempt($username, $password)) {
            case 'ok':
                $this->redirect('/dashboard');
                return;
            case 'needs_2fa':
                $this->redirect('/login/verify-2fa');
                return;
            case 'locked':
                $this->view('auth', 'login', ['error' => 'Too many failed attempts. Try again in a few minutes.']);
                return;
            default:
                $this->view('auth', 'login', ['error' => 'Invalid username or password.']);
        }
    }

    public function showTwoFactor(): void
    {
        if (!Auth::hasPending2fa()) {
            $this->redirect('/login');
            return;
        }
        $this->view('auth', 'verify_2fa', ['error' => null]);
    }

    public function verifyTwoFactor(): void
    {
        if (!Auth::hasPending2fa()) {
            $this->redirect('/login');
            return;
        }
        if (!Auth::verifyCsrfToken($this->input('csrf_token', ''))) {
            $this->view('auth', 'verify_2fa', ['error' => 'Invalid session token. Please try again.']);
            return;
        }

        $code = (string) $this->input('code', '');
        if (Auth::verifyTwoFactorCode($code)) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth', 'verify_2fa', ['error' => 'Invalid or expired code. Please try again.']);
    }

    public function logout(): void
    {
        if (!Auth::check() && !Auth::hasPending2fa()) {
            $this->redirect('/login');
            return;
        }
        $this->requireCsrf();
        Auth::logout();
        $this->redirect('/login');
    }
}
