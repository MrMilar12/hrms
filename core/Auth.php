<?php
// Session/RBAC guard: login state, permission checks, 2FA, lockout, and CSRF token helpers.

class Auth
{
    private const RECORD_UNLOCK_SECONDS = 900;
    private const RECORD_LOCK_SCOPES = ['profile', 'pds'];
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const SESSION_IDLE_SECONDS = 1800;
    private const TWO_FACTOR_PENDING_SECONDS = 300;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.gc_maxlifetime', (string) self::SESSION_IDLE_SECONDS);
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => self::isHttps(),
            ]);
            session_start();

            $lastActivity = (int) ($_SESSION['last_activity_at'] ?? 0);
            if ($lastActivity > 0 && time() - $lastActivity > self::SESSION_IDLE_SECONDS) {
                self::clearSession();
                session_start();
            }
            $_SESSION['last_activity_at'] = time();
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
            AuditLogger::log('login_failed', 'users', $user ? (int) $user['id'] : null, null, ['reason' => 'invalid_or_inactive_account']);
            return 'invalid';
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            AuditLogger::log('login_blocked', 'users', (int) $user['id'], null, ['reason' => 'account_lockout']);
            return 'locked';
        }

        if (!password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt($pdo, (int) $user['id'], (int) $user['failed_login_attempts']);
            AuditLogger::log('login_failed', 'users', (int) $user['id'], null, ['reason' => 'invalid_credentials']);
            return 'invalid';
        }

        // Successful password check: clear any failure count/lockout.
        $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$user['id']]);

        if ((int) $user['two_factor_enabled'] === 1) {
            session_regenerate_id(true);
            $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
            $_SESSION['pending_2fa_started_at'] = time();
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
        $startedAt = (int) ($_SESSION['pending_2fa_started_at'] ?? 0);
        if (!$userId || !$startedAt || time() - $startedAt > self::TWO_FACTOR_PENDING_SECONDS) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_started_at']);
            return false;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name, r.id AS role_id, pi.first_name, pi.surname
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = u.employee_id
             WHERE u.id = ? AND u.status = \'active\' AND u.two_factor_enabled = 1 LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !TwoFactor::verify($user['two_factor_secret'], $code)) {
            AuditLogger::log('two_factor_failed', 'users', $user ? (int) $user['id'] : (int) $userId, null, ['reason' => 'invalid_or_expired_code']);
            return false;
        }

        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_started_at']);
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
        $_SESSION['personnel_setup_complete'] = self::hasPersonnelClassification($_SESSION['employee_id']);
        $_SESSION['personal_details_complete'] = self::hasRequiredPersonalDetails($_SESSION['employee_id']);
        $_SESSION['last_activity_at'] = time();
        $pdo = Database::getInstance();
        $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $update->execute([$user['id']]);
        AuditLogger::log('login', 'users', (int) $user['id'], null, ['result' => 'success']);
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

    private static function hasPersonnelClassification(?int $employeeId): bool
    {
        if (!$employeeId) return true;
        $stmt = Database::getInstance()->prepare("SELECT 1 FROM employee_work_profiles WHERE employee_id = ? AND personnel_type IN ('Teaching', 'Non-Teaching')");
        $stmt->execute([$employeeId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function needsPersonnelSetup(): bool
    {
        if (!self::check() || !self::employeeId()) return false;
        if (!array_key_exists('personnel_setup_complete', $_SESSION)) {
            $_SESSION['personnel_setup_complete'] = self::hasPersonnelClassification(self::employeeId());
        }
        return !$_SESSION['personnel_setup_complete'];
    }

    public static function completePersonnelSetup(): void
    {
        $_SESSION['personnel_setup_complete'] = true;
    }

    private static function hasRequiredPersonalDetails(?int $employeeId): bool
    {
        if (!$employeeId) return true;
        $stmt = Database::getInstance()->prepare(
            "SELECT 1
             FROM pds_personal_info pi
             JOIN pds_addresses a ON a.employee_id = pi.employee_id AND a.address_type = 'Residential'
             WHERE pi.employee_id = ?
               AND NULLIF(TRIM(pi.first_name), '') IS NOT NULL
               AND NULLIF(TRIM(pi.surname), '') IS NOT NULL
               AND pi.birth_date IS NOT NULL
               AND pi.sex IN ('Male', 'Female')
               AND pi.civil_status IS NOT NULL
               AND NULLIF(TRIM(pi.mobile_no), '') IS NOT NULL
               AND NULLIF(TRIM(pi.email), '') IS NOT NULL
               AND NULLIF(TRIM(a.house_block_lot), '') IS NOT NULL
               AND NULLIF(TRIM(a.barangay), '') IS NOT NULL
               AND NULLIF(TRIM(a.city_municipality), '') IS NOT NULL
               AND NULLIF(TRIM(a.province), '') IS NOT NULL"
        );
        $stmt->execute([$employeeId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function needsPersonalDetailsSetup(): bool
    {
        if (!self::check() || !self::employeeId()) return false;
        if (!array_key_exists('personal_details_complete', $_SESSION)) {
            $_SESSION['personal_details_complete'] = self::hasRequiredPersonalDetails(self::employeeId());
        }
        return !$_SESSION['personal_details_complete'];
    }

    public static function completePersonalDetailsSetup(): void
    {
        $_SESSION['personal_details_complete'] = true;
    }

    public static function logout(): void
    {
        if (self::check()) {
            AuditLogger::log('logout', 'users', self::userId(), null, ['result' => 'success']);
        }
        self::clearSession();
    }

    private static function clearSession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
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

    public static function isRecordUnlocked(string $scope): bool
    {
        if (!in_array($scope, self::RECORD_LOCK_SCOPES, true)) return false;
        $expiresAt = (int) ($_SESSION['record_unlocks'][$scope] ?? 0);
        if ($expiresAt <= time()) {
            unset($_SESSION['record_unlocks'][$scope]);
            return false;
        }
        return true;
    }

    public static function unlockRecord(string $scope, string $password): bool
    {
        if (!in_array($scope, self::RECORD_LOCK_SCOPES, true) || $password === '' || !self::userId()) return false;
        $stmt = Database::getInstance()->prepare('SELECT password_hash FROM users WHERE id = ? AND status = "active" LIMIT 1');
        $stmt->execute([self::userId()]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($password, $hash)) {
            AuditLogger::log('record_unlock_failed', $scope, self::employeeId(), null, ['scope' => $scope]);
            return false;
        }
        $_SESSION['record_unlocks'][$scope] = time() + self::RECORD_UNLOCK_SECONDS;
        AuditLogger::log('record_unlocked', $scope, self::employeeId(), null, ['scope' => $scope]);
        return true;
    }

    public static function lockRecord(string $scope): void
    {
        if (!in_array($scope, self::RECORD_LOCK_SCOPES, true)) return;
        unset($_SESSION['record_unlocks'][$scope]);
        AuditLogger::log('record_locked', $scope, self::employeeId(), null, ['scope' => $scope]);
    }
}
