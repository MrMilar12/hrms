<?php
// Accomplishment & Evidence controller: draft/submit workflow, photo evidence, HR/Supervisor review.

class AccomplishmentController extends Controller
{
    private Accomplishment $model;

    public function __construct()
    {
        $this->model = new Accomplishment();
    }

    public function index(): void
    {
        Auth::requirePermission('accomplishment.create');

        $canReviewAll = Auth::can('accomplishment.view_all');
        $status = $this->input('status') ?: null;
        $pdo = Database::getInstance();

        $departments = [];
        $employees = [];
        $notSubmitted = [];
        $submissionPeriod = null;

        if ($canReviewAll) {
            $filters = [
                'status' => $status,
                'department_id' => $this->input('department_id') ?: null,
                'employee_id' => $this->input('employee_id') ?: null,
                'search' => $this->input('q') ?: null,
            ];
            $accomplishments = $this->model->listFiltered($filters);
            $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
            $employees = $pdo->query(
                "SELECT e.id, e.employee_number,
                        COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), e.employee_number) AS employee_name
                 FROM employees e
                 LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
                 ORDER BY e.employee_number"
            )->fetchAll();
            $today = new DateTimeImmutable('today');
            $day = (int) $today->format('j');
            $lastDay = (int) $today->format('t');

            if ($day === 16) {
                $periodStart = $today->modify('first day of this month');
                $periodEnd = $today->setDate((int) $today->format('Y'), (int) $today->format('n'), 15);
                $submissionPeriod = '1st–15th of ' . $today->format('F Y');
            } elseif ($day === $lastDay) {
                $periodStart = $today->setDate((int) $today->format('Y'), (int) $today->format('n'), 16);
                $periodEnd = $today;
                $submissionPeriod = '16th–' . $today->format('jS') . ' of ' . $today->format('F Y');
            }

            if ($submissionPeriod !== null) {
                $notSubmitted = $this->model->employeesWithoutSubmission(
                    $periodStart->format('Y-m-d'),
                    $periodEnd->format('Y-m-d')
                );
            }
        } else {
            $accomplishments = $this->model->listForEmployee((int) Auth::employeeId());
            if ($status !== null) {
                $accomplishments = array_values(array_filter($accomplishments, fn($a) => $a['status'] === $status));
            }
        }

        $this->view('accomplishments', 'index', [
            'pageTitle' => 'Accomplishments',
            'accomplishments' => $accomplishments,
            'canReviewAll' => $canReviewAll,
            'statuses' => Accomplishment::STATUSES,
            'activeStatus' => $status,
            'departments' => $departments,
            'employees' => $employees,
            'notSubmitted' => $notSubmitted,
            'submissionPeriod' => $submissionPeriod,
            'filterDepartment' => $this->input('department_id') ?: '',
            'filterEmployee' => $this->input('employee_id') ?: '',
            'filterSearch' => $this->input('q') ?: '',
        ]);
    }

    public function gallery(): void
    {
        Auth::requirePermission('accomplishment.create');

        $employeeId = (int) Auth::employeeId();
        $status = $this->input('status') ?: 'Approved';
        $items = $this->model->listForEmployee($employeeId);
        if ($status !== 'All') {
            $items = array_values(array_filter($items, fn($a) => $a['status'] === $status));
        }

        $this->view('accomplishments', 'gallery', [
            'pageTitle' => 'My Accomplishment Gallery',
            'items' => $items,
            'statuses' => Accomplishment::STATUSES,
            'activeStatus' => $status,
        ]);
    }

    /** Printable/downloadable record: form details + evidence photos in one page (browser Print/Save as PDF). */
    public function printView(string $id): void
    {
        Auth::requirePermission('accomplishment.create');
        $accomplishmentId = (int) $id;
        $accomplishment = $this->model->findWithDetails($accomplishmentId);

        if (!$accomplishment) {
            http_response_code(404);
            echo 'Accomplishment not found.';
            return;
        }
        if ($accomplishment['employee_id'] != Auth::employeeId() && !Auth::can('accomplishment.view_all')) {
            http_response_code(403);
            echo 'Not authorized.';
            return;
        }
        if ($accomplishment['status'] !== 'Approved') {
            http_response_code(403);
            echo 'Download and printing are available only after approval.';
            return;
        }

        $qrPayload = implode("\n", [
            'PROJECT PUNLA HRMS',
            'APPROVED ACCOMPLISHMENT',
            'Employee: ' . $accomplishment['employee_name'],
            'Employee No: ' . $accomplishment['employee_number'],
            'Title: ' . $accomplishment['title'],
            'Completion Date: ' . $accomplishment['accomplishment_date'],
            'Status: Approved',
        ]);
        $qrCode = new Endroid\QrCode\QrCode(
            data: $qrPayload,
            errorCorrectionLevel: Endroid\QrCode\ErrorCorrectionLevel::Medium,
            size: 220,
            margin: 8
        );
        $qrDataUri = (new Endroid\QrCode\Writer\PngWriter())->write($qrCode)->getDataUri();
        AuditLogger::log('print_accessed', 'accomplishments', $accomplishmentId, null, ['status' => 'Approved']);

        $this->view('accomplishments', 'print', [
            'accomplishment' => $accomplishment,
            'attachments' => $this->model->attachments($accomplishmentId),
            'reviews' => $this->model->reviews($accomplishmentId),
            'qrDataUri' => $qrDataUri,
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('accomplishment.create');
        $canCreateForOthers = Auth::can('accomplishment.view_all');

        $pdo = Database::getInstance();

        if ($canCreateForOthers) {
            // HR/Admin/Supervisor can log an accomplishment on behalf of any employee.
            $employees = $pdo->query(
                "SELECT e.id, e.employee_number,
                        COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), e.employee_number) AS employee_name
                 FROM employees e
                 LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
                 ORDER BY e.employee_number"
            )->fetchAll();
            $tasks = $pdo->query(
                'SELECT t.id, t.title, e.id AS employee_id, e.employee_number FROM tasks t
                 JOIN task_assignments ta ON ta.task_id = t.id
                 JOIN employees e ON e.id = ta.employee_id
                 ORDER BY e.employee_number, t.title'
            )->fetchAll();
        } else {
            $employees = [];
            $stmt = $pdo->prepare(
                'SELECT t.id, t.title FROM tasks t
                 JOIN task_assignments ta ON ta.task_id = t.id
                 WHERE ta.employee_id = ? ORDER BY t.title'
            );
            $stmt->execute([Auth::employeeId()]);
            $tasks = $stmt->fetchAll();
        }

        $this->view('accomplishments', 'create', [
            'pageTitle' => 'New Accomplishment',
            'tasks' => $tasks,
            'employees' => $employees,
            'canCreateForOthers' => $canCreateForOthers,
            'accomplishment' => null,
            'attachments' => [],
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requirePermission('accomplishment.create');
        $accomplishmentId = (int) $id;
        $accomplishment = $this->model->find($accomplishmentId);
        if (!$accomplishment) {
            http_response_code(404);
            echo 'Accomplishment not found.';
            return;
        }
        if ($accomplishment['employee_id'] != Auth::employeeId() || Auth::can('accomplishment.review')) {
            http_response_code(403);
            echo 'Only the employee who submitted this accomplishment can edit it.';
            return;
        }
        if (!in_array($accomplishment['status'], ['Draft', 'Returned'], true)) {
            $this->redirect('/accomplishments/' . $accomplishmentId);
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT t.id, t.title FROM tasks t
             JOIN task_assignments ta ON ta.task_id = t.id
             WHERE ta.employee_id = ? ORDER BY t.title'
        );
        $stmt->execute([Auth::employeeId()]);

        $this->view('accomplishments', 'create', [
            'pageTitle' => 'Edit Accomplishment',
            'tasks' => $stmt->fetchAll(),
            'employees' => [],
            'canCreateForOthers' => false,
            'accomplishment' => $accomplishment,
            'attachments' => $this->model->attachments($accomplishmentId),
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('accomplishment.create');
        $this->requireCsrf();

        $validator = new Validator($_POST);
        $validator->required('title', 'Title')->maxLength('title', 200)
            ->required('accomplishment_date', 'Date')->date('accomplishment_date')
            ->maxLength('description', 2000);

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }

        $employeeId = $this->resolveTargetEmployeeId();
        if (!$employeeId) {
            $this->json(['success' => false, 'error' => Auth::can('accomplishment.view_all') ? 'Please select an employee.' : 'No employee record linked to this account.'], 422);
            return;
        }

        $employeeStmt = Database::getInstance()->prepare('SELECT id FROM employees WHERE id = ?');
        $employeeStmt->execute([$employeeId]);
        if (!$employeeStmt->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'Please select a valid employee.'], 422);
            return;
        }

        $taskId = !empty($_POST['task_id']) ? (int) $_POST['task_id'] : null;
        if ($taskId && !$this->taskBelongsToEmployee($taskId, $employeeId)) {
            $this->json(['success' => false, 'error' => 'The selected task is not assigned to this employee.'], 422);
            return;
        }

        $accomplishmentId = $this->model->insert([
            'employee_id' => $employeeId,
            'task_id' => $taskId,
            'title' => Validator::sanitizeString($_POST['title']),
            'description' => Validator::sanitizeString($_POST['description'] ?? ''),
            'accomplishment_date' => $_POST['accomplishment_date'],
            'status' => 'Draft',
        ]);

        if ($employeeId !== (int) Auth::employeeId() && Auth::can('accomplishment.view_all')) {
            $_SESSION['staff_created_accomplishments'][$accomplishmentId] = time();
        }

        AuditLogger::log('create', 'accomplishments', $accomplishmentId, null, ['employee_id' => $employeeId, 'task_id' => $taskId, 'status' => 'Draft']);
        $this->json(['success' => true, 'message' => 'Draft saved.', 'accomplishment_token' => UrlId::encode($accomplishmentId)]);
    }

    /** Autosave endpoint used while the employee is still editing a draft. */
    public function saveDraft(string $id): void
    {
        Auth::requirePermission('accomplishment.create');
        $this->requireCsrf();

        $accomplishmentId = (int) $id;
        $accomplishment = $this->authorizeOwner($accomplishmentId);
        if (!$accomplishment) {
            return;
        }
        if (!in_array($accomplishment['status'], ['Draft', 'Returned'], true)) {
            $this->json(['success' => false, 'error' => 'Only drafts or returned accomplishments can be edited.'], 422);
            return;
        }

        $validator = new Validator($_POST);
        $validator->required('title', 'Title')->maxLength('title', 200)
            ->required('accomplishment_date', 'Date')->date('accomplishment_date')
            ->maxLength('description', 2000);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }

        $taskId = !empty($_POST['task_id']) ? (int) $_POST['task_id'] : null;
        if ($taskId && !$this->taskBelongsToEmployee($taskId, (int) $accomplishment['employee_id'])) {
            $this->json(['success' => false, 'error' => 'The selected task is not assigned to this employee.'], 422);
            return;
        }

        $this->model->update($accomplishmentId, [
            'title' => Validator::sanitizeString($_POST['title'] ?? $accomplishment['title']),
            'description' => Validator::sanitizeString($_POST['description'] ?? ''),
            'accomplishment_date' => $_POST['accomplishment_date'] ?? $accomplishment['accomplishment_date'],
            'task_id' => $taskId,
        ]);

        AuditLogger::log('update', 'accomplishments', $accomplishmentId, ['status' => $accomplishment['status']], ['changed_fields' => ['title', 'description', 'accomplishment_date', 'task_id']]);

        $this->json(['success' => true, 'message' => 'Draft saved.']);
    }

    public function submit(string $id): void
    {
        Auth::requirePermission('accomplishment.create');
        $this->requireCsrf();

        $accomplishmentId = (int) $id;
        $accomplishment = $this->authorizeOwner($accomplishmentId);
        if (!$accomplishment) {
            return;
        }

        if (!in_array($accomplishment['status'], ['Draft', 'Returned'], true)) {
            $this->json(['success' => false, 'error' => 'This accomplishment has already been submitted.'], 422);
            return;
        }

        $this->model->submit($accomplishmentId);
        unset($_SESSION['staff_created_accomplishments'][$accomplishmentId]);
        AuditLogger::log('submit', 'accomplishments', $accomplishmentId, null, ['status' => 'For Review']);
        Notification::permission('accomplishment.review', 'Accomplishment submitted for review: ' . $accomplishment['title'], BASE_URL . '/accomplishments/' . UrlId::encode($accomplishmentId));
        $this->json(['success' => true, 'message' => 'Accomplishment submitted for review.']);
    }

    public function show(string $id): void
    {
        Auth::requirePermission('accomplishment.create');
        $accomplishmentId = (int) $id;
        $accomplishment = $this->model->findWithDetails($accomplishmentId);

        if (!$accomplishment) {
            http_response_code(404);
            echo 'Accomplishment not found.';
            return;
        }
        if ($accomplishment['employee_id'] != Auth::employeeId() && !Auth::can('accomplishment.view_all')) {
            http_response_code(403);
            echo 'Not authorized to view this accomplishment.';
            return;
        }

        $this->view('accomplishments', 'show', [
            'pageTitle' => $accomplishment['title'],
            'accomplishment' => $accomplishment,
            'attachments' => $this->model->attachments($accomplishmentId),
            'reviews' => $this->model->reviews($accomplishmentId),
            'isOwner' => $accomplishment['employee_id'] == Auth::employeeId(),
            'canReview' => Auth::can('accomplishment.review'),
        ]);
    }

    public function review(string $id): void
    {
        Auth::requirePermission('accomplishment.review');
        $this->requireCsrf();

        $accomplishmentId = (int) $id;
        $accomplishment = $this->model->find($accomplishmentId);
        if (!$accomplishment) {
            $this->json(['success' => false, 'error' => 'Accomplishment not found.'], 404);
            return;
        }
        if ($accomplishment['status'] !== 'For Review') {
            $this->json(['success' => false, 'error' => 'Only items For Review can be approved or returned.'], 422);
            return;
        }

        $decision = $_POST['decision'] ?? '';
        if (!in_array($decision, ['Approved', 'Returned'], true)) {
            $this->json(['success' => false, 'error' => 'Invalid decision.'], 422);
            return;
        }

        if ($decision === 'Approved') {
            $password = (string) ($_POST['approval_password'] ?? '');
            if ($password === '') {
                $this->json(['success' => false, 'error' => 'Enter your password to confirm this approval.'], 422);
                return;
            }
            $stmt = Database::getInstance()->prepare('SELECT password_hash FROM users WHERE id = ? AND status = \'active\' LIMIT 1');
            $stmt->execute([Auth::userId()]);
            $passwordHash = $stmt->fetchColumn();
            if (!$passwordHash || !password_verify($password, $passwordHash)) {
                AuditLogger::log('approval_password_failed', 'accomplishments', $accomplishmentId);
                $this->json(['success' => false, 'error' => 'Incorrect password. Approval was not completed.'], 403);
                return;
            }
        }

        $comments = Validator::sanitizeString($_POST['comments'] ?? '');
        $reviewId = $this->model->review($accomplishmentId, Auth::userId(), $decision, $comments ?: null);

        AuditLogger::log('update_status', 'accomplishments', $accomplishmentId, ['status' => 'For Review'], ['status' => $decision]);
        AuditLogger::log('create', 'accomplishment_reviews', $reviewId, null, ['accomplishment_id' => $accomplishmentId, 'status' => $decision]);
        $notificationMessage = $decision === 'Approved'
            ? 'Your accomplishment “' . $accomplishment['title'] . '” was approved and is ready to print.'
            : 'Your accomplishment “' . $accomplishment['title'] . '” was returned for revision.';
        Notification::employees([(int) $accomplishment['employee_id']], $notificationMessage, BASE_URL . '/accomplishments/' . UrlId::encode($accomplishmentId));
        $this->json(['success' => true, 'message' => "Accomplishment {$decision}."]);
    }

    public function uploadAttachment(string $id): void
    {
        Auth::requirePermission('accomplishment.create');
        $this->requireCsrf();

        $accomplishmentId = (int) $id;
        $accomplishment = $this->authorizeOwner($accomplishmentId);
        if (!$accomplishment) {
            return;
        }
        if (!in_array($accomplishment['status'], ['Draft', 'Returned'], true)) {
            $this->json(['success' => false, 'error' => 'Photos can only be changed on drafts or returned accomplishments.'], 422);
            return;
        }

        if (empty($_FILES['file'])) {
            $this->json(['success' => false, 'error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $dir = UPLOADS_PATH . "/accomplishments/{$accomplishmentId}";
            $result = Uploader::handleImage($_FILES['file'], $dir);
            $caption = Validator::sanitizeString($this->input('caption', ''));

            $attachmentId = $this->model->addAttachment(
                $accomplishmentId,
                Auth::userId(),
                $result['file_path'],
                $result['thumbnail_path'],
                $caption,
                $result['file_type'],
                $result['file_size']
            );

            AuditLogger::log('create', 'accomplishment_attachments', $attachmentId, null, ['accomplishment_id' => $accomplishmentId, 'file_type' => $result['file_type']]);
            $this->json(['success' => true, 'message' => 'Photo uploaded.']);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function deleteAttachment(string $accomplishmentId, string $attachmentId): void
    {
        Auth::requirePermission('accomplishment.create');
        $this->requireCsrf();

        $id = (int) $accomplishmentId;
        $accomplishment = $this->authorizeOwner($id);
        if (!$accomplishment) {
            return;
        }
        if (!in_array($accomplishment['status'], ['Draft', 'Returned'], true)) {
            $this->json(['success' => false, 'error' => 'Photos can only be changed on drafts or returned accomplishments.'], 422);
            return;
        }

        $this->model->deleteAttachment((int) $attachmentId, $id);
        AuditLogger::log('delete_attachment', 'accomplishment_attachments', (int) $attachmentId, null, ['accomplishment_id' => $id]);
        $this->json(['success' => true, 'message' => 'Photo removed.']);
    }

    /** Loads the accomplishment and ensures only its employee owner may mutate it. */
    private function authorizeOwner(int $accomplishmentId): ?array
    {
        $accomplishment = $this->model->find($accomplishmentId);
        if (!$accomplishment) {
            $this->json(['success' => false, 'error' => 'Accomplishment not found.'], 404);
            return null;
        }
        $createdAt = (int) ($_SESSION['staff_created_accomplishments'][$accomplishmentId] ?? 0);
        $createdInSession = $createdAt > 0
            && $createdAt >= time() - 7200
            && Auth::can('accomplishment.view_all');
        if ($accomplishment['employee_id'] != Auth::employeeId() && !$createdInSession) {
            $this->json(['success' => false, 'error' => 'Not authorized.'], 403);
            return null;
        }
        return $accomplishment;
    }

    private function taskBelongsToEmployee(int $taskId, int $employeeId): bool
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT 1 FROM task_assignments WHERE task_id = ? AND employee_id = ? LIMIT 1'
        );
        $stmt->execute([$taskId, $employeeId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Resolves which employee the new accomplishment belongs to: an explicit employee_id (HR/Admin/Supervisor only) or the current user's own record. */
    private function resolveTargetEmployeeId(): ?int
    {
        if (!empty($_POST['employee_id']) && Auth::can('accomplishment.view_all')) {
            return (int) $_POST['employee_id'];
        }
        return Auth::employeeId();
    }
}
