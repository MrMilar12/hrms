<?php
// Employee 201-file controller: list + profile view (with photo upload).

class EmployeeController extends Controller
{
    private Employee $employeeModel;
    private Pds $pdsModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->pdsModel = new Pds();
    }

    public function index(): void
    {
        Auth::requirePermission('employee.view');
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $this->employeeModel->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $employees = $this->employeeModel->listWithDetails($perPage, ($page - 1) * $perPage);

        $this->view('employees', 'index', [
            'pageTitle' => 'Employees (201 File)',
            'employees' => $employees,
            'canManage' => Auth::can('employee.manage'),
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('employee.manage');

        $pdo = Database::getInstance();
        $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $positions = $pdo->query('SELECT id, title FROM positions ORDER BY title')->fetchAll();
        $roles = $pdo->query("SELECT id, name FROM roles WHERE name <> 'Developer' ORDER BY name")->fetchAll();
        if (Auth::isDeveloper()) {
            $roles = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
        }

        $this->view('employees', 'create', [
            'pageTitle' => 'Add Employee',
            'departments' => $departments,
            'positions' => $positions,
            'roles' => $roles,
            'statuses' => ['Regular', 'Casual', 'Contractual', 'Job Order', 'Probationary'],
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('employee.manage');
        $this->requireCsrf();

        $validator = new Validator($_POST);
        $validator->required('employee_number', 'Employee number')->maxLength('employee_number', 30)
            ->required('username', 'Username')->maxLength('username', 60)
            ->required('email', 'Email')->email('email')
            ->required('password', 'Password')
            ->in('employment_status', ['Regular', 'Casual', 'Contractual', 'Job Order', 'Probationary'])
            ->date('date_hired');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }

        $employeeNumber = Validator::sanitizeString($_POST['employee_number']);
        if ($this->employeeModel->where('employee_number', $employeeNumber)) {
            $this->json(['success' => false, 'error' => 'That employee number is already in use.'], 422);
            return;
        }

        if (mb_strlen((string) $_POST['password']) < 8) {
            $this->json(['success' => false, 'error' => 'Password must be at least 8 characters.'], 422);
            return;
        }

        $pdo = Database::getInstance();
        $username = Validator::sanitizeString($_POST['username']);
        $email = Validator::sanitizeString($_POST['email']);
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $existingUser = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $existingUser->execute([$username, $email]);
        if ($existingUser->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'That username or email is already in use.'], 422);
            return;
        }

        $roleExists = $pdo->prepare('SELECT id, name FROM roles WHERE id = ?');
        $roleExists->execute([$roleId]);
        $selectedRole = $roleExists->fetch();
        if (!$selectedRole) {
            $this->json(['success' => false, 'error' => 'Please select a valid role.'], 422);
            return;
        }
        if ($selectedRole['name'] === ROLE_DEVELOPER && !Auth::isDeveloper()) {
            $this->json(['success' => false, 'error' => 'Only a Developer can create another Developer account.'], 403);
            return;
        }

        try {
            $pdo->beginTransaction();
            $employeeId = $this->employeeModel->insert([
                'employee_number' => $employeeNumber,
                'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
                'position_id' => !empty($_POST['position_id']) ? (int) $_POST['position_id'] : null,
                'date_hired' => !empty($_POST['date_hired']) ? $_POST['date_hired'] : null,
                'employment_status' => $_POST['employment_status'] ?? 'Probationary',
            ]);

            $account = $pdo->prepare(
                'INSERT INTO users (employee_id, username, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, "active")'
            );
            $account->execute([$employeeId, $username, $email, password_hash($_POST['password'], PASSWORD_BCRYPT), $roleId]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['success' => false, 'error' => 'Unable to create the employee account. Please try again.'], 500);
            return;
        }

        AuditLogger::log('create', 'employees', $employeeId);
        AuditLogger::log('create', 'users', $userId);
        $this->json(['success' => true, 'message' => 'Employee and account created.', 'employee_token' => UrlId::encode($employeeId)]);
    }

    public function show(string $id): void
    {
        Auth::requirePermission('employee.view');
        $employee = $this->employeeModel->findWithDetails((int) $id);
        if (!$employee) {
            http_response_code(404);
            echo 'Employee not found.';
            return;
        }
        $photo = $this->employeeModel->latestPhoto((int) $id);
        $pdsPercent = $this->pdsModel->completionPercent((int) $id);
        $snapshot = $this->employeeModel->profileSnapshot((int) $id);
        $relatedSummary = $this->employeeModel->relatedSummary((int) $id);
        $recentRecords = $this->employeeModel->recentRelatedRecords((int) $id);

        $educationStmt = Database::getInstance()->prepare(
            "SELECT level, school_name, degree_course FROM pds_educational_background
             WHERE employee_id = ? ORDER BY FIELD(level, 'Graduate Studies','College','Vocational','Secondary','Elementary') LIMIT 1"
        );
        $educationStmt->execute([(int) $id]);
        $eligibilityStmt = Database::getInstance()->prepare(
            'SELECT eligibility_name FROM pds_civil_service_eligibility WHERE employee_id = ? ORDER BY id LIMIT 3'
        );
        $eligibilityStmt->execute([(int) $id]);

        $this->view('employees', 'show', [
            'pageTitle' => 'Employee Profile',
            'employee' => $employee,
            'photo' => $photo,
            'pdsPercent' => $pdsPercent,
            'snapshot' => $snapshot,
            'relatedSummary' => $relatedSummary,
            'recentRecords' => $recentRecords,
            'highestEducation' => $educationStmt->fetch() ?: null,
            'eligibilities' => $eligibilityStmt->fetchAll(),
        ]);
    }

    public function uploadPhoto(string $id): void
    {
        Auth::requirePermission('employee.manage');
        $this->requireCsrf();

        $employeeId = (int) $id;
        if (!$this->employeeModel->find($employeeId)) {
            $this->json(['success' => false, 'error' => 'Employee not found.'], 404);
            return;
        }

        if (empty($_FILES['photo'])) {
            $this->json(['success' => false, 'error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $dir = UPLOADS_PATH . "/photos/{$employeeId}";
            $result = Uploader::handleImage($_FILES['photo'], $dir);
            $photoId = $this->employeeModel->savePhoto($employeeId, $result['file_path'], $result['thumbnail_path']);
            AuditLogger::log('create', 'employee_photos', $photoId, null, ['employee_id' => $employeeId]);
            $this->json(['success' => true, 'message' => 'Photo uploaded.']);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
