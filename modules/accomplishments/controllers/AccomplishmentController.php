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
            $notSubmitted = $this->model->employeesWithoutSubmission();
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

        $this->view('accomplishments', 'print', [
            'accomplishment' => $accomplishment,
            'attachments' => $this->model->attachments($accomplishmentId),
            'reviews' => $this->model->reviews($accomplishmentId),
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
                'SELECT t.id, t.title, e.employee_number FROM tasks t
                 JOIN task_assignments ta ON ta.task_id = t.id
                 JOIN employees e ON e.id = ta.employee_id
                 ORDER BY t.title'
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
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('accomplishment.create');
        $this->requireCsrf();

        $validator = new Validator($_POST);
        $validator->required('title', 'Title')->maxLength('title', 200)
            ->required('accomplishment_date', 'Date')->date('accomplishment_date');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }

        $employeeId = $this->resolveTargetEmployeeId();
        if (!$employeeId) {
            $this->json(['success' => false, 'error' => 'No employee record linked to this account.'], 403);
            return;
        }

        $accomplishmentId = $this->model->insert([
            'employee_id' => $employeeId,
            'task_id' => !empty($_POST['task_id']) ? (int) $_POST['task_id'] : null,
            'title' => Validator::sanitizeString($_POST['title']),
            'description' => Validator::sanitizeString($_POST['description'] ?? ''),
            'accomplishment_date' => $_POST['accomplishment_date'],
            'status' => 'Draft',
        ]);

        AuditLogger::log('create', 'accomplishments', $accomplishmentId);
        $this->json(['success' => true, 'message' => 'Draft saved.', 'accomplishment_id' => $accomplishmentId]);
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
        if ($accomplishment['status'] !== 'Draft') {
            $this->json(['success' => false, 'error' => 'Only drafts can be edited.'], 422);
            return;
        }

        $this->model->update($accomplishmentId, [
            'title' => Validator::sanitizeString($_POST['title'] ?? $accomplishment['title']),
            'description' => Validator::sanitizeString($_POST['description'] ?? ''),
            'accomplishment_date' => $_POST['accomplishment_date'] ?? $accomplishment['accomplishment_date'],
            'task_id' => !empty($_POST['task_id']) ? (int) $_POST['task_id'] : null,
        ]);

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
        AuditLogger::log('submit', 'accomplishments', $accomplishmentId, null, ['status' => 'For Review']);
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
            'isOwner' => $accomplishment['employee_id'] == Auth::employeeId() || Auth::can('accomplishment.view_all'),
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

        $comments = Validator::sanitizeString($_POST['comments'] ?? '');
        $this->model->review($accomplishmentId, Auth::userId(), $decision, $comments ?: null);

        AuditLogger::log('review', 'accomplishments', $accomplishmentId, null, ['status' => $decision]);
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

        if (empty($_FILES['file'])) {
            $this->json(['success' => false, 'error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $dir = UPLOADS_PATH . "/accomplishments/{$accomplishmentId}";
            $result = Uploader::handleImage($_FILES['file'], $dir);
            $caption = Validator::sanitizeString($this->input('caption', ''));

            $this->model->addAttachment(
                $accomplishmentId,
                Auth::userId(),
                $result['file_path'],
                $result['thumbnail_path'],
                $caption,
                $result['file_type'],
                $result['file_size']
            );

            AuditLogger::log('upload_attachment', 'accomplishment_attachments', $accomplishmentId);
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

        $this->model->deleteAttachment((int) $attachmentId, $id);
        $this->json(['success' => true, 'message' => 'Photo removed.']);
    }

    /** Loads the accomplishment and ensures the current user may manage it (owner, or HR/Admin/Supervisor with accomplishment.view_all); emits a JSON error and returns null otherwise. */
    private function authorizeOwner(int $accomplishmentId): ?array
    {
        $accomplishment = $this->model->find($accomplishmentId);
        if (!$accomplishment) {
            $this->json(['success' => false, 'error' => 'Accomplishment not found.'], 404);
            return null;
        }
        if ($accomplishment['employee_id'] != Auth::employeeId() && !Auth::can('accomplishment.view_all')) {
            $this->json(['success' => false, 'error' => 'Not authorized.'], 403);
            return null;
        }
        return $accomplishment;
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
