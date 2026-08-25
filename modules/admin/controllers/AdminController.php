<?php
// Administration controller: user accounts, departments, positions (all gated by user.manage).

class AdminController extends Controller
{
    // ---- Users ----

    public function users(): void
    {
        Auth::requirePermission('user.manage');
        $pdo = Database::getInstance();

        $users = $pdo->query(
            'SELECT u.id, u.username, u.email, u.status, u.last_login, r.name AS role_name,
                    e.employee_number
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             ORDER BY u.username'
        )->fetchAll();

        $this->view('admin', 'users', [
            'pageTitle' => 'Manage Accounts',
            'users' => $users,
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
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$status, (int) $id]);

        AuditLogger::log('update_status', 'users', (int) $id, null, ['status' => $status]);
        $this->json(['success' => true, 'message' => 'User status updated.']);
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

        $positions = $pdo->query(
            'SELECT p.id, p.title, p.salary_grade,
                    (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id) AS employee_count
             FROM positions p
             ORDER BY p.title'
        )->fetchAll();

        $this->view('admin', 'positions', [
            'pageTitle' => 'Manage Positions',
            'positions' => $positions,
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
}
