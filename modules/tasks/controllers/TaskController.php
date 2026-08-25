<?php
// Task management controller: list, create, detail, status updates, attachments, comments.

class TaskController extends Controller
{
    private Task $taskModel;

    private const VALID_STATUSES = ['Open', 'In Progress', 'For Review', 'Done', 'Cancelled'];
    private const VALID_PRIORITIES = ['Low', 'Medium', 'High', 'Urgent'];

    public function __construct()
    {
        $this->taskModel = new Task();
    }

    public function index(): void
    {
        Auth::requirePermission('task.view');

        $employeeId = Auth::can('task.create') ? null : Auth::employeeId();
        $tasks = $this->taskModel->listWithDetails($employeeId);

        $this->view('tasks', 'index', [
            'pageTitle' => 'Tasks',
            'tasks' => $tasks,
            'canCreate' => Auth::can('task.create'),
            'statuses' => self::VALID_STATUSES,
        ]);
    }

    public function calendar(): void
    {
        Auth::requirePermission('task.view');
        $monthInput = (string) ($this->input('month') ?? date('Y-m'));
        $month = DateTimeImmutable::createFromFormat('!Y-m', $monthInput);
        if (!$month || $month->format('Y-m') !== $monthInput) {
            $month = new DateTimeImmutable('first day of this month');
        }
        $firstDay = $month->modify('first day of this month');
        $lastDay = $month->modify('last day of this month');
        $employeeId = Auth::can('task.create') ? null : Auth::employeeId();
        $tasks = $this->taskModel->calendarTasks($firstDay->format('Y-m-d'), $lastDay->format('Y-m-d'), $employeeId);
        $tasksByDate = [];
        foreach ($tasks as $task) $tasksByDate[$task['due_date']][] = $task;

        $this->view('tasks', 'calendar', [
            'pageTitle' => 'Task Calendar',
            'month' => $month,
            'tasksByDate' => $tasksByDate,
            'canCreate' => Auth::can('task.create'),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('task.create');

        $pdo = Database::getInstance();
        $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $employees = $pdo->query(
            "SELECT e.id, e.employee_number, e.department_id, d.name AS department_name,
                    COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), e.employee_number) AS employee_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             ORDER BY employee_name, e.employee_number"
        )->fetchAll();

        $this->view('tasks', 'create', [
            'pageTitle' => 'New Task',
            'departments' => $departments,
            'employees' => $employees,
            'priorities' => self::VALID_PRIORITIES,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('task.create');
        $this->requireCsrf();

        $validator = new Validator($_POST);
        $validator->required('title', 'Title')->maxLength('title', 200)
            ->in('priority', self::VALID_PRIORITIES)
            ->date('due_date');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }

        $employeeIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['assignees'] ?? [])),
            fn(int $id): bool => $id > 0
        )));
        if (!$employeeIds) {
            $this->json(['success' => false, 'error' => 'Select at least one employee.'], 422);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $validEmployeeStmt = Database::getInstance()->prepare("SELECT id FROM employees WHERE id IN ($placeholders)");
        $validEmployeeStmt->execute($employeeIds);
        $validEmployeeIds = array_map('intval', $validEmployeeStmt->fetchAll(PDO::FETCH_COLUMN));
        if (count($validEmployeeIds) !== count($employeeIds)) {
            $this->json(['success' => false, 'error' => 'One or more selected employees are invalid.'], 422);
            return;
        }

        $taskId = $this->taskModel->insert([
            'title' => Validator::sanitizeString($_POST['title']),
            'description' => Validator::sanitizeString($_POST['description'] ?? ''),
            'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
            'priority' => $_POST['priority'] ?? 'Medium',
            'status' => 'Open',
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'created_by' => Auth::userId(),
        ]);

        $this->taskModel->assign($taskId, $validEmployeeIds);

        Notification::employees($validEmployeeIds, 'New task assigned: ' . Validator::sanitizeString($_POST['title']), BASE_URL . '/tasks/' . UrlId::encode($taskId));

        AuditLogger::log('create', 'tasks', $taskId, null, [
            'title' => Validator::sanitizeString($_POST['title']),
            'assignee_ids' => $validEmployeeIds,
            'priority' => $_POST['priority'] ?? 'Medium',
        ]);
        $this->json(['success' => true, 'message' => 'Task created.', 'task_token' => UrlId::encode($taskId)]);
    }

    public function show(string $id): void
    {
        Auth::requirePermission('task.view');
        $taskId = (int) $id;
        $task = $this->taskModel->findWithDetails($taskId);
        if (!$task) {
            http_response_code(404);
            echo 'Task not found.';
            return;
        }

        if (!Auth::can('task.create') && !$this->taskModel->isAssignee($taskId, (int) Auth::employeeId())) {
            http_response_code(403);
            echo 'Not authorized to view this task.';
            return;
        }

        $assignees = $this->taskModel->assignees($taskId);
        $this->view('tasks', 'show', [
            'pageTitle' => $task['title'],
            'task' => $task,
            'assignees' => $assignees,
            'attachments' => $this->taskModel->attachments($taskId),
            'comments' => $this->taskModel->comments($taskId),
            'history' => $this->taskModel->statusHistory($taskId),
            'statuses' => self::VALID_STATUSES,
            'canUpdateStatus' => Auth::can('task.update_status'),
            'currentEmployeeId' => Auth::employeeId(),
            'canManageAssignments' => Auth::can('task.create'),
        ]);
    }

    public function updateStatus(string $id): void
    {
        Auth::requirePermission('task.update_status');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Auth::verifyCsrfToken($token)) {
            $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], 419);
            return;
        }

        $taskId = (int) $id;
        $status = $input['status'] ?? '';
        if (!in_array($status, self::VALID_STATUSES, true)) {
            $this->json(['success' => false, 'error' => 'Invalid status.'], 422);
            return;
        }

        $employeeId = Auth::can('task.create') && !empty($input['employee_id'])
            ? (int) $input['employee_id']
            : (int) Auth::employeeId();
        if (!$employeeId || !$this->taskModel->isAssignee($taskId, $employeeId)) {
            $this->json(['success' => false, 'error' => 'Not authorized.'], 403);
            return;
        }

        $this->taskModel->updateAssignmentStatus($taskId, $employeeId, $status, Auth::userId());
        AuditLogger::log('update_assignment_status', 'task_assignments', $taskId, null, ['employee_id' => $employeeId, 'status' => $status]);
        $task = $this->taskModel->find($taskId);
        $taskUrl = BASE_URL . '/tasks/' . UrlId::encode($taskId);
        Notification::employees([$employeeId], 'Your task “' . ($task['title'] ?? 'Task') . '” is now ' . $status . '.', $taskUrl);
        if (!empty($task['created_by'])) Notification::user((int) $task['created_by'], 'Task update: “' . $task['title'] . '” is now ' . $status . ' for one assignee.', $taskUrl);

        $this->json(['success' => true, 'message' => 'Individual task status updated to ' . $status . '.']);
    }

    public function addComment(string $id): void
    {
        Auth::requirePermission('task.view');
        $this->requireCsrf();

        $taskId = (int) $id;
        if (!$this->canAccessTask($taskId)) {
            $this->json(['success' => false, 'error' => 'Not authorized to access this task.'], 403);
            return;
        }
        $comment = Validator::sanitizeString($this->input('comment', ''));
        if ($comment === '') {
            $this->json(['success' => false, 'error' => 'Comment cannot be empty.'], 422);
            return;
        }

        $commentId = $this->taskModel->addComment($taskId, Auth::userId(), $comment);
        AuditLogger::log('create', 'task_comments', $commentId, null, ['task_id' => $taskId]);
        $task = $this->taskModel->find($taskId);
        $assigneeIds = array_column($this->taskModel->assignees($taskId), 'id');
        $taskUrl = BASE_URL . '/tasks/' . UrlId::encode($taskId);
        Notification::employees($assigneeIds, 'New comment on task: ' . ($task['title'] ?? 'Task'), $taskUrl);
        if (!empty($task['created_by'])) Notification::user((int) $task['created_by'], 'New comment on task: ' . $task['title'], $taskUrl);
        $this->json(['success' => true, 'message' => 'Comment added.']);
    }

    public function uploadAttachment(string $id): void
    {
        Auth::requirePermission('task.view');
        $this->requireCsrf();

        $taskId = (int) $id;
        if (!$this->canAccessTask($taskId)) {
            $this->json(['success' => false, 'error' => 'Not authorized to access this task.'], 403);
            return;
        }
        if (empty($_FILES['file'])) {
            $this->json(['success' => false, 'error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $dir = UPLOADS_PATH . "/tasks/{$taskId}";
            $result = Uploader::handleImage($_FILES['file'], $dir);
            $caption = Validator::sanitizeString($this->input('caption', ''));

            $attachmentId = $this->taskModel->addAttachment(
                $taskId,
                Auth::userId(),
                $result['file_path'],
                $result['thumbnail_path'],
                $caption,
                $result['file_type'],
                $result['file_size']
            );

            AuditLogger::log('create', 'task_attachments', $attachmentId, null, ['task_id' => $taskId, 'file_type' => $result['file_type']]);
            $this->json(['success' => true, 'message' => 'Attachment uploaded.']);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function canAccessTask(int $taskId): bool
    {
        if ($taskId <= 0 || !$this->taskModel->find($taskId)) {
            return false;
        }
        return Auth::can('task.create')
            || (Auth::employeeId() && $this->taskModel->isAssignee($taskId, (int) Auth::employeeId()));
    }
}
