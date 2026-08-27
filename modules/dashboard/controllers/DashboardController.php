<?php
// Dashboard controller: at-a-glance PDS completion and task summary for the logged-in user.

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $pdo = Database::getInstance();
        $pdsModel = new Pds();
        $employeeId = Auth::employeeId();

        $pdsPercent = $employeeId ? $pdsModel->completionPercent($employeeId) : 0;
        $completion = $employeeId ? $pdsModel->completionStatus($employeeId) : [];

        $taskCounts = [];
        if ($employeeId) {
            $stmt = $pdo->prepare(
                'SELECT ta.status, COUNT(*) AS total FROM tasks t
                 JOIN task_assignments ta ON ta.task_id = t.id
                 WHERE ta.employee_id = ? GROUP BY t.status'
            );
            $stmt->execute([$employeeId]);
            foreach ($stmt->fetchAll() as $row) {
                $taskCounts[$row['status']] = (int) $row['total'];
            }
        }

        $totalEmployees = (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
        $totalTasks = (int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
        $pendingReview = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'For Review'")->fetchColumn();
        $profileStmt = $pdo->prepare(
            "SELECT u.username, r.name AS role_name, e.employee_number,
                    COALESCE(NULLIF(TRIM(CONCAT_WS(' ', pi.first_name, pi.middle_name, pi.surname, pi.name_extension)), ''), u.username) AS display_name,
                    COALESCE(d.name, 'Department not assigned') AS department_name,
                    COALESCE(p.title, 'Position not assigned') AS position_title,
                    (SELECT ep.id FROM employee_photos ep WHERE ep.employee_id = u.employee_id ORDER BY ep.uploaded_at DESC LIMIT 1) AS photo_id
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE u.id = ? LIMIT 1"
        );
        $profileStmt->execute([Auth::userId()]);
        $dashboardProfile = $profileStmt->fetch() ?: [];

        $accomplishmentModel = new Accomplishment();
        $myAccomplishmentCounts = $employeeId ? $accomplishmentModel->statusCounts($employeeId) : [];
        $accomplishmentCounts = Auth::can('accomplishment.view_all') ? $accomplishmentModel->globalStatusCounts() : null;

        $notifStmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10');
        $notifStmt->execute([Auth::userId()]);

        $this->view('dashboard', 'index', [
            'pageTitle' => 'Home',
            'pdsPercent' => $pdsPercent,
            'completion' => $completion,
            'taskCounts' => $taskCounts,
            'totalEmployees' => $totalEmployees,
            'totalTasks' => $totalTasks,
            'pendingReview' => $pendingReview,
            'dashboardProfile' => $dashboardProfile,
            'myAccomplishmentCounts' => $myAccomplishmentCounts,
            'accomplishmentCounts' => $accomplishmentCounts,
            'notifications' => $notifStmt->fetchAll(),
            'unseenReleases' => SystemRelease::unseenForUser((int) Auth::userId()),
        ]);
    }
}
