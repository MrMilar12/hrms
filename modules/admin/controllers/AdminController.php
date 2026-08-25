<?php
// Administration controller: user accounts, departments, positions (all gated by user.manage).

class AdminController extends Controller
{
    public function activity(): void
    {
        Auth::requirePermission('user.manage');
        $pdo = Database::getInstance();
        $filters = [
            'q' => trim((string) $this->input('q', '')),
            'action' => trim((string) $this->input('action', '')),
            'date_from' => trim((string) $this->input('date_from', '')),
            'date_to' => trim((string) $this->input('date_to', '')),
        ];
        $where = [];
        $params = [];
        if ($filters['q'] !== '') {
            $where[] = '(a.action LIKE ? OR a.table_name LIKE ? OR u.username LIKE ? OR a.ip_address LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if ($filters['action'] !== '') {
            $where[] = 'a.action = ?';
            $params[] = $filters['action'];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT a.*, u.username, r.name AS role_name,
                       COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), u.username, 'System') AS actor_name
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN pds_personal_info pi ON pi.employee_id = u.employee_id";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT 250';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $this->view('admin', 'activity', [
            'pageTitle' => 'Activity Logs',
            'logs' => $stmt->fetchAll(),
            'filters' => $filters,
            'actions' => $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN),
            'summary' => [
                'today' => (int) $pdo->query('SELECT COUNT(*) FROM audit_logs WHERE created_at >= CURDATE()')->fetchColumn(),
                'week' => (int) $pdo->query('SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
                'security' => (int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('login','logout','login_failed','login_blocked','two_factor_failed','enable_2fa','disable_2fa','approval_password_failed') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
            ],
        ]);
    }

    public function dashboard(): void
    {
        Auth::requirePermission('report.view');
        $pdo = Database::getInstance();

        $summary = [
            'employees' => (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
            'activeUsers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'departments' => (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
            'openTasks' => (int) $pdo->query("SELECT COUNT(*) FROM task_assignments WHERE status NOT IN ('Done', 'Cancelled')")->fetchColumn(),
            'pendingAccomplishments' => (int) $pdo->query("SELECT COUNT(*) FROM accomplishments WHERE status = 'For Review'")->fetchColumn(),
        ];

        $personnelCounts = $pdo->query(
            "SELECT
                COALESCE(SUM(w.personnel_type = 'Teaching'), 0) AS teaching,
                COALESCE(SUM(w.personnel_type = 'Non-Teaching'), 0) AS non_teaching,
                COALESCE(SUM(w.employee_id IS NULL), 0) AS unclassified
             FROM employees e
             LEFT JOIN employee_work_profiles w ON w.employee_id = e.id"
        )->fetch();
        $summary['teaching'] = (int) ($personnelCounts['teaching'] ?? 0);
        $summary['nonTeaching'] = (int) ($personnelCounts['non_teaching'] ?? 0);
        $summary['unclassifiedPersonnel'] = (int) ($personnelCounts['unclassified'] ?? 0);

        $genderRows = $pdo->query("SELECT COALESCE(sex, 'Not specified') AS label, COUNT(*) AS total FROM pds_personal_info GROUP BY sex")->fetchAll();
        $gender = ['Male' => 0, 'Female' => 0, 'Not specified' => 0];
        $personalInfoCount = 0;
        foreach ($genderRows as $row) {
            $gender[$row['label']] = ($gender[$row['label']] ?? 0) + (int) $row['total'];
            $personalInfoCount += (int) $row['total'];
        }
        $gender['Not specified'] += max(0, $summary['employees'] - $personalInfoCount);

        $departmentStats = $pdo->query(
            "SELECT COALESCE(d.name, 'Unassigned') AS label, COUNT(e.id) AS total
             FROM employees e LEFT JOIN departments d ON d.id = e.department_id
             GROUP BY e.department_id, d.name ORDER BY total DESC, label"
        )->fetchAll();
        $positionStats = $pdo->query(
            "SELECT p.title AS label, p.salary_grade, COUNT(e.id) AS total
             FROM positions p
             INNER JOIN employees e ON e.position_id = p.id
             GROUP BY p.id, p.title, p.salary_grade
             ORDER BY total DESC, p.title"
        )->fetchAll();
        $summary['occupiedPositions'] = count($positionStats);
        $summary['positionAssignedEmployees'] = array_sum(array_map(
            static fn(array $row): int => (int) $row['total'],
            $positionStats
        ));
        $summary['positionUnassignedEmployees'] = max(0, $summary['employees'] - $summary['positionAssignedEmployees']);
        $employmentStats = $pdo->query('SELECT employment_status AS label, COUNT(*) AS total FROM employees GROUP BY employment_status ORDER BY total DESC')->fetchAll();
        $taskStats = $pdo->query('SELECT status AS label, COUNT(*) AS total FROM task_assignments GROUP BY status')->fetchAll();

        $submissionRows = $pdo->query(
            "SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month_key, COUNT(*) AS total
             FROM accomplishments
             WHERE submitted_at IS NOT NULL AND submitted_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
             GROUP BY month_key ORDER BY month_key"
        )->fetchAll();
        $submissionMap = [];
        foreach ($submissionRows as $row) $submissionMap[$row['month_key']] = (int) $row['total'];
        $monthlySubmissions = [];
        $month = new DateTimeImmutable('first day of this month');
        for ($offset = 11; $offset >= 0; $offset--) {
            $date = $month->modify("-{$offset} months");
            $key = $date->format('Y-m');
            $monthlySubmissions[] = ['label' => $date->format('M'), 'year' => $date->format('Y'), 'total' => $submissionMap[$key] ?? 0];
        }

        $completedSections = (int) $pdo->query('SELECT COALESCE(SUM(is_complete), 0) FROM pds_completion_status')->fetchColumn();
        $summary['pdsAverage'] = $summary['employees'] > 0 ? (int) round(($completedSections / ($summary['employees'] * 14)) * 100) : 0;

        $this->view('admin', 'dashboard', [
            'pageTitle' => 'Admin Analytics',
            'summary' => $summary,
            'gender' => $gender,
            'departmentStats' => $departmentStats,
            'positionStats' => $positionStats,
            'employmentStats' => $employmentStats,
            'taskStats' => $taskStats,
            'monthlySubmissions' => $monthlySubmissions,
        ]);
    }

    // ---- Users ----

    public function users(): void
    {
        Auth::requirePermission('user.manage');
        $pdo = Database::getInstance();
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $stmt = $pdo->prepare(
            'SELECT u.id, u.username, u.email, u.status, u.last_login, u.created_at, u.role_id,
                    u.two_factor_enabled, r.name AS role_name, e.employee_number,
                    pi.first_name, pi.middle_name, pi.surname, pi.name_extension,
                    d.name AS department_name, p.title AS position_title,
                    wp.personnel_type, wp.current_school_station,
                    (SELECT ep.id FROM employee_photos ep WHERE ep.employee_id = e.id ORDER BY ep.uploaded_at DESC LIMIT 1) AS photo_id
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             LEFT JOIN employee_work_profiles wp ON wp.employee_id = e.id
             ORDER BY COALESCE(pi.surname, u.username), pi.first_name, u.username
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();

        $rolesSql = Auth::isDeveloper()
            ? 'SELECT id, name FROM roles ORDER BY name'
            : "SELECT id, name FROM roles WHERE name <> 'Developer' ORDER BY name";
        $this->view('admin', 'users', [
            'pageTitle' => 'Manage Accounts',
            'users' => $users,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'roles' => $pdo->query($rolesSql)->fetchAll(),
            'currentUserId' => (int) Auth::userId(),
            'viewerIsDeveloper' => Auth::isDeveloper(),
        ]);
    }

    public function updateUserStatus(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();

        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['active', 'inactive', 'locked'], true)) {
            $this->json(['success' => false, 'error' => 'Invalid status.'], 422);
            return;
        }

        $pdo = Database::getInstance();
        $userId = (int) $id;
        if ($this->denyProtectedDeveloperChange($userId)) return;
        if ($userId === (int) Auth::userId() && $status !== 'active') {
            $this->json(['success' => false, 'error' => 'You cannot deactivate or lock your own account.'], 422);
            return;
        }
        if ($status !== 'active' && $this->isLastActiveAdmin($userId)) {
            $this->json(['success' => false, 'error' => 'The last active administrator cannot be disabled.'], 422);
            return;
        }
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$status, $userId]);

        AuditLogger::log('update_status', 'users', (int) $id, null, ['status' => $status]);
        $this->json(['success' => true, 'message' => 'User status updated.']);
    }

    public function updateUser(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();
        $userId = (int) $id;
        $username = Validator::sanitizeString($_POST['username'] ?? '');
        $email = Validator::sanitizeString($_POST['email'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $validator = new Validator(['username' => $username, 'email' => $email]);
        $validator->required('username', 'Username')->maxLength('username', 60)->required('email', 'Email')->email('email');
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }
        $pdo = Database::getInstance();
        if ($this->denyProtectedDeveloperChange($userId)) return;
        $current = $pdo->prepare('SELECT role_id FROM users WHERE id = ?');
        $current->execute([$userId]);
        $currentRoleId = $current->fetchColumn();
        if ($currentRoleId === false) {
            $this->json(['success' => false, 'error' => 'Account not found.'], 404);
            return;
        }
        $role = $pdo->prepare('SELECT id, name FROM roles WHERE id = ?');
        $role->execute([$roleId]);
        $newRole = $role->fetch();
        if (!$newRole) {
            $this->json(['success' => false, 'error' => 'Please select a valid role.'], 422);
            return;
        }
        if ($newRole['name'] === ROLE_DEVELOPER && !Auth::isDeveloper()) {
            $this->json(['success' => false, 'error' => 'Only a Developer can assign the Developer role.'], 403);
            return;
        }
        if ($userId === (int) Auth::userId() && $roleId !== (int) $currentRoleId) {
            $this->json(['success' => false, 'error' => 'You cannot change your own administrator role.'], 422);
            return;
        }
        if ($roleId !== (int) $currentRoleId && $this->isLastActiveAdmin($userId)) {
            $this->json(['success' => false, 'error' => 'The last active administrator cannot be assigned another role.'], 422);
            return;
        }
        $duplicate = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
        $duplicate->execute([$username, $email, $userId]);
        if ($duplicate->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'That username or email is already in use.'], 422);
            return;
        }
        $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?');
        $stmt->execute([$username, $email, $roleId, $userId]);
        AuditLogger::log('update', 'users', $userId, null, ['username' => $username, 'email' => $email, 'role_id' => $roleId]);
        $this->json(['success' => true, 'message' => 'Account updated.']);
    }

    public function resetUserPassword(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();
        if ($this->denyProtectedDeveloperChange((int) $id)) return;
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($password) < 8) {
            $this->json(['success' => false, 'error' => 'The temporary password must be at least 8 characters.'], 422);
            return;
        }
        $stmt = Database::getInstance()->prepare('UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_BCRYPT), (int) $id]);
        if (!$stmt->rowCount()) {
            $this->json(['success' => false, 'error' => 'Account not found or password unchanged.'], 404);
            return;
        }
        AuditLogger::log('reset_password', 'users', (int) $id);
        $this->json(['success' => true, 'message' => 'Temporary password set successfully.']);
    }

    public function resetUserTwoFactor(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();
        if ($this->denyProtectedDeveloperChange((int) $id)) return;
        if ((int) $id === (int) Auth::userId()) {
            $this->json(['success' => false, 'error' => 'Use your Security settings to change your own 2FA.'], 422);
            return;
        }
        $stmt = Database::getInstance()->prepare('UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?');
        $stmt->execute([(int) $id]);
        AuditLogger::log('admin_reset_2fa', 'users', (int) $id);
        $this->json(['success' => true, 'message' => 'Two-factor authentication reset.']);
    }

    public function deleteUser(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();
        $userId = (int) $id;
        if ($this->denyProtectedDeveloperChange($userId)) return;
        if ($userId === (int) Auth::userId()) {
            $this->json(['success' => false, 'error' => 'You cannot delete your own account.'], 422);
            return;
        }
        if ($this->isLastActiveAdmin($userId)) {
            $this->json(['success' => false, 'error' => 'The last active administrator cannot be deleted.'], 422);
            return;
        }
        try {
            $stmt = Database::getInstance()->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            if (!$stmt->rowCount()) {
                $this->json(['success' => false, 'error' => 'Account not found.'], 404);
                return;
            }
            AuditLogger::log('delete', 'users', $userId);
            $this->json(['success' => true, 'message' => 'Account deleted. The employee record was retained.']);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'error' => 'This account has activity history and cannot be deleted. Set it to Inactive instead.'], 422);
        }
    }

    private function isLastActiveAdmin(int $userId): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.status = 'active' AND r.name = 'Admin'");
        $stmt->execute([$userId]);
        if (!(int) $stmt->fetchColumn()) return false;
        return (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE u.status = 'active' AND r.name = 'Admin'")->fetchColumn() <= 1;
    }

    private function denyProtectedDeveloperChange(int $userId): bool
    {
        if (Auth::isDeveloper()) return false;
        $stmt = Database::getInstance()->prepare(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.name = 'Developer'"
        );
        $stmt->execute([$userId]);
        if (!(int) $stmt->fetchColumn()) return false;
        $this->json(['success' => false, 'error' => 'Developer accounts can only be managed by another Developer.'], 403);
        return true;
    }

    // ---- Departments ----

    public function departments(): void
    {
        Auth::requirePermission('user.manage');
        $pdo = Database::getInstance();

        $departments = $pdo->query(
            'SELECT d.id, d.name, p.name AS parent_name,
                    (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) AS employee_count
             FROM departments d
             LEFT JOIN departments p ON p.id = d.parent_department_id
             ORDER BY d.name'
        )->fetchAll();

        $this->view('admin', 'departments', [
            'pageTitle' => 'Manage Departments',
            'departments' => $departments,
            'allDepartments' => $departments,
        ]);
    }

    public function storeDepartment(): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();

        $name = Validator::sanitizeString($_POST['name'] ?? '');
        if ($name === '') {
            $this->json(['success' => false, 'error' => 'Department name is required.'], 422);
            return;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO departments (name, parent_department_id) VALUES (?, ?)');
        $stmt->execute([$name, !empty($_POST['parent_department_id']) ? (int) $_POST['parent_department_id'] : null]);

        AuditLogger::log('create', 'departments', (int) $pdo->lastInsertId());
        $this->json(['success' => true, 'message' => 'Department added.']);
    }

    public function deleteDepartment(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
        try {
            $stmt->execute([(int) $id]);
            AuditLogger::log('delete', 'departments', (int) $id);
            $this->json(['success' => true, 'message' => 'Department deleted.']);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'error' => 'Cannot delete: still referenced by employees or tasks.'], 422);
        }
    }

    // ---- Positions ----

    public function positions(): void
    {
        Auth::requirePermission('user.manage');
        $pdo = Database::getInstance();

        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim(Validator::sanitizeString($_GET['q'] ?? ''));
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE p.title LIKE ? OR p.salary_grade LIKE ?';
            $term = '%' . $search . '%';
            $params = [$term, $term];
        }
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM positions p' . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare(
            'SELECT p.id, p.title, p.salary_grade,
                    (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id) AS employee_count
             FROM positions p
             ' . $where . '
             ORDER BY p.title
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute($params);
        $positions = $stmt->fetchAll();

        $this->view('admin', 'positions', [
            'pageTitle' => 'Manage Positions',
            'positions' => $positions,
            'page' => $page,
            'total' => $total,
            'totalPages' => $totalPages,
            'search' => $search,
        ]);
    }

    public function storePosition(): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();

        $title = Validator::sanitizeString($_POST['title'] ?? '');
        if ($title === '') {
            $this->json(['success' => false, 'error' => 'Position title is required.'], 422);
            return;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO positions (title, salary_grade) VALUES (?, ?)');
        $stmt->execute([$title, Validator::sanitizeString($_POST['salary_grade'] ?? '') ?: null]);

        AuditLogger::log('create', 'positions', (int) $pdo->lastInsertId());
        $this->json(['success' => true, 'message' => 'Position added.']);
    }

    public function deletePosition(string $id): void
    {
        Auth::requirePermission('user.manage');
        $this->requireCsrf();

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM positions WHERE id = ?');
        try {
            $stmt->execute([(int) $id]);
            AuditLogger::log('delete', 'positions', (int) $id);
            $this->json(['success' => true, 'message' => 'Position deleted.']);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'error' => 'Cannot delete: still referenced by employees or tasks.'], 422);
        }
    }

    // ---- System releases ----

    public function releases(): void
    {
        Auth::requireDeveloper();
        try {
            $githubStatus = SystemUpdater::status();
            $sourceCount = (int) Database::getInstance()->query('SELECT COUNT(*) FROM system_releases WHERE source_commit IS NOT NULL')->fetchColumn();
            if ($sourceCount === 0 && !$githubStatus['update_available']) {
                $baseline = Database::getInstance()->prepare(
                    'UPDATE system_releases SET source_commit = ? WHERE is_published = 1 ORDER BY released_at DESC, id DESC LIMIT 1'
                );
                $baseline->execute([$githubStatus['remote_sha']]);
            }
            $announced = Database::getInstance()->prepare('SELECT COUNT(*) FROM system_releases WHERE source_commit = ?');
            $announced->execute([$githubStatus['remote_sha']]);
            $githubStatus['notification_needed'] = !(int) $announced->fetchColumn();
            $githubError = null;
        } catch (Throwable $e) {
            $githubStatus = null;
            $githubError = $e->getMessage();
        }
        $releases = Database::getInstance()->query(
            'SELECT sr.*, u.username AS creator,
                    (SELECT COUNT(*) FROM user_release_views urv WHERE urv.release_id = sr.id) AS view_count
             FROM system_releases sr
             LEFT JOIN users u ON u.id = sr.created_by
             ORDER BY sr.released_at DESC, sr.id DESC'
        )->fetchAll();
        $this->view('admin', 'releases', [
            'pageTitle' => 'System Updates', 'releases' => $releases,
            'githubStatus' => $githubStatus, 'githubError' => $githubError,
            'deployments' => SystemUpdater::history(),
        ]);
    }

    public function storeRelease(): void
    {
        Auth::requireDeveloper();
        $this->requireCsrf();

        $version = trim((string) ($_POST['version'] ?? ''));
        $title = Validator::sanitizeString($_POST['title'] ?? '');
        $changes = trim((string) ($_POST['changes'] ?? ''));
        $publish = isset($_POST['is_published']) ? 1 : 0;
        try {
            $githubStatus = SystemUpdater::status();
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => 'Unable to verify the new GitHub update: ' . $e->getMessage()], 502);
        }
        $alreadyAnnounced = Database::getInstance()->prepare('SELECT COUNT(*) FROM system_releases WHERE source_commit = ?');
        $alreadyAnnounced->execute([$githubStatus['remote_sha']]);
        if ((int) $alreadyAnnounced->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'This GitHub update has already been announced.'], 422);
        }
        if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
            $this->json(['success' => false, 'error' => 'Use a version such as 1.2.0.'], 422);
        }
        if (version_compare($version, SystemRelease::currentVersion(), '<=')) {
            $this->json(['success' => false, 'error' => 'The new version must be higher than the current system version.'], 422);
        }
        if ($title === '' || $changes === '') {
            $this->json(['success' => false, 'error' => 'Title and changes are required.'], 422);
        }

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare(
                'INSERT INTO system_releases (version, title, changes, released_at, is_published, created_by, release_url, source_commit)
                 VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)'
            );
            $commitUrl = 'https://github.com/' . GITHUB_REPOSITORY . '/commit/' . $githubStatus['remote_sha'];
            $stmt->execute([$version, $title, $changes, $publish, Auth::userId(), $commitUrl, $githubStatus['remote_sha']]);
            $id = (int) $pdo->lastInsertId();
            AuditLogger::log('create', 'system_releases', $id, null, ['version' => $version, 'published' => (bool) $publish]);
            $this->json(['success' => true, 'message' => $publish ? 'Update published.' : 'Draft saved.']);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'error' => 'That system version already exists.'], 422);
        }
    }

    public function publishRelease(string $id): void
    {
        Auth::requireDeveloper();
        $this->requireCsrf();
        $releaseId = (int) $id;
        $stmt = Database::getInstance()->prepare('UPDATE system_releases SET is_published = 1, released_at = NOW() WHERE id = ?');
        $stmt->execute([$releaseId]);
        if (!$stmt->rowCount()) $this->json(['success' => false, 'error' => 'Release not found or already published.'], 404);
        AuditLogger::log('publish', 'system_releases', $releaseId);
        $this->json(['success' => true, 'message' => 'Update published. Users will see it on their dashboard.']);
    }

    public function syncGitHubReleases(): void
    {
        Auth::requireDeveloper();
        $this->requireCsrf();
        try {
            $result = GitHubReleaseSync::sync((int) Auth::userId());
            AuditLogger::log('github_sync', 'system_releases', null, null, $result);
            $this->json(['success' => true, 'message' => "GitHub sync complete: {$result['imported']} release(s) imported or updated."]);
        } catch (Throwable $e) {
            error_log('GitHub release sync failed: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'GitHub sync failed: ' . $e->getMessage()], 502);
        }
    }
}
