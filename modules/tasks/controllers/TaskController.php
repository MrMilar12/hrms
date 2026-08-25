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

    public function create(): void
    {
        Auth::requirePermission('task.create');

        $pdo = Database::getInstance();
        $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $employees = $pdo->query('SELECT id, employee_number FROM employees ORDER BY employee_number')->fetchAll();

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

        $taskId = $this->taskModel->insert([
            'title' => Validator::sanitizeString($_POST['title']),
            'description' => Validator::sanitizeString($_POST['description'] ?? ''),
            'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
            'priority' => $_POST['priority'] ?? 'Medium',
            'status' => 'Open',
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'created_by' => Auth::userId(),
        ]);

        $employeeIds = array_map('intval', $_POST['assignees'] ?? []);
        if ($employeeIds) {
            $this->taskModel->assign($taskId, $employeeIds);
        }

        AuditLogger::log('create', 'tasks', $taskId);
        $this->json(['success' => true, 'message' => 'Task created.', 'task_id' => $taskId]);
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

        $this->view('tasks', 'show', [
            'pageTitle' => $task['title'],
            'task' => $task,
            'assignees' => $this->taskModel->assignees($taskId),
            'attachments' => $this->taskModel->attachments($taskId),
            'comments' => $this->taskModel->comments($taskId),
            'history' => $this->taskModel->statusHistory($taskId),
            'statuses' => self::VALID_STATUSES,
            'canUpdateStatus' => Auth::can('task.update_status'),
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

        if (!Auth::can('task.create') && !$this->taskModel->isAssignee($taskId, (int) Auth::employeeId())) {
            $this->json(['success' => false, 'error' => 'Not authorized.'], 403);
            return;
        }

        $this->taskModel->updateStatus($taskId, $status, Auth::userId());
        AuditLogger::log('update_status', 'tasks', $taskId, null, ['status' => $status]);

        $this->json(['success' => true, 'message' => 'Status updated to ' . $status . '.']);
    }

    public function addComment(string $id): void
    {
        Auth::requirePermission('task.view');
        $this->requireCsrf();

        $taskId = (int) $id;
        $comment = Validator::sanitizeString($this->input('comment', ''));
        if ($comment === '') {
            $this->json(['success' => false, 'error' => 'Comment cannot be empty.'], 422);
            return;
        }

        $this->taskModel->addComment($taskId, Auth::userId(), $comment);
        $this->json(['success' => true, 'message' => 'Comment added.']);
    }

    public function uploadAttachment(string $id): void
    {
        Auth::requirePermission('task.view');
        $this->requireCsrf();

        $taskId = (int) $id;
        if (empty($_FILES['file'])) {
            $this->json(['success' => false, 'error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $dir = UPLOADS_PATH . "/tasks/{$taskId}";
            $result = Uploader::handleImage($_FILES['file'], $dir);
            $caption = Validator::sanitizeString($this->input('caption', ''));

            $this->taskModel->addAttachment(
                $taskId,
                Auth::userId(),
                $result['file_path'],
                $result['thumbnail_path'],
                $caption,
                $result['file_type'],
                $result['file_size']
            );

            AuditLogger::log('upload_attachment', 'task_attachments', $taskId);
            $this->json(['success' => true, 'message' => 'Attachment uploaded.']);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
