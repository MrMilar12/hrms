<?php
// Session/RBAC guard: login state, permission checks, 2FA, lockout, and CSRF token helpers.

class Auth
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => self::isHttps(),
            ]);
            session_start();
        }
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) == 443;
    }

    /**
     * Attempts username/password login.
     * @return string One of: 'ok' (fully logged in), 'needs_2fa' (password ok, awaiting TOTP code),
     *                 'locked' (too many recent failures), 'invalid' (bad credentials/inactive account).
     */
    public static function attempt(string $username, string $password): string
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.username, u.password_hash, u.status, u.employee_id, u.failed_login_attempts, u.locked_until,
                    u.two_factor_enabled, u.two_factor_secret,
                    r.name AS role_name, r.id AS role_id, pi.first_name, pi.surname
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = u.employee_id
             WHERE u.username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            return 'invalid';
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return 'locked';
        }

        if (!password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt($pdo, (int) $user['id'], (int) $user['failed_login_attempts']);
            return 'invalid';
        }

        // Successful password check: clear any failure count/lockout.
        $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$user['id']]);

        if ((int) $user['two_factor_enabled'] === 1) {
            session_regenerate_id(true);
            $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
            return 'needs_2fa';
        }

        self::completeLogin($user);
        return 'ok';
    }

    private static function registerFailedAttempt(PDO $pdo, int $userId, int $currentAttempts): void
    {
        $attempts = $currentAttempts + 1;
        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?');
            $stmt->execute([$attempts, self::LOCKOUT_MINUTES, $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = ? WHERE id = ?');
            $stmt->execute([$attempts, $userId]);
        }
    }

    /** Verifies the 6-digit TOTP code for the user left pending by attempt(), completing login on success. */
    public static function verifyTwoFactorCode(string $code): bool
    {
        $userId = $_SESSION['pending_2fa_user_id'] ?? null;
        if (!$userId) {
            return false;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name, r.id AS role_id, pi.first_name, pi.surname
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = u.employee_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !TwoFactor::verify($user['two_factor_secret'], $code)) {
            return false;
        }

        unset($_SESSION['pending_2fa_user_id']);
        self::completeLogin($user);
        return true;
    }

    public static function hasPending2fa(): bool
    {
        return isset($_SESSION['pending_2fa_user_id']);
    }

    private static function completeLogin(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = (int) $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['employee_id'] = $user['employee_id'] !== null ? (int) $user['employee_id'] : null;
        $_SESSION['permissions'] = self::loadPermissions((int) $user['role_id']);
        $_SESSION['display_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? '')) ?: $user['username'];
        $_SESSION['show_app_drawer'] = true;

        $pdo = Database::getInstance();
        $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $update->execute([$user['id']]);
    }

    private static function loadPermissions(int $roleId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT p.code FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?'
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public static function can(string $permissionCode): bool
    {
        return in_array($permissionCode, $_SESSION['permissions'] ?? [], true);
    }

    public static function requirePermission(string $permissionCode): void
    {
        self::requireLogin();
        if (!self::can($permissionCode)) {
            http_response_code(403);
            echo '403 Forbidden: insufficient permissions.';
            exit;
        }
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function employeeId(): ?int
    {
        return $_SESSION['employee_id'] ?? null;
    }

    public static function roleName(): ?string
    {
        return $_SESSION['role_name'] ?? null;
    }

    public static function displayName(): string
    {
        return $_SESSION['display_name'] ?? ($_SESSION['username'] ?? '');
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

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(string $token): bool
    {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
