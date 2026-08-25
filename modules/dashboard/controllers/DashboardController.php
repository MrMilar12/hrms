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

        $accomplishmentModel = new Accomplishment();
        $myAccomplishmentCounts = $employeeId ? $accomplishmentModel->statusCounts($employeeId) : [];
        $accomplishmentCounts = Auth::can('accomplishment.view_all') ? $accomplishmentModel->globalStatusCounts() : null;

        $notifStmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10');
        $notifStmt->execute([Auth::userId()]);

        $this->view('dashboard', 'index', [
            'pageTitle' => 'Dashboard',
            'pdsPercent' => $pdsPercent,
            'completion' => $completion,
            'taskCounts' => $taskCounts,
            'totalEmployees' => $totalEmployees,
            'totalTasks' => $totalTasks,
            'pendingReview' => $pendingReview,
            'myAccomplishmentCounts' => $myAccomplishmentCounts,
            'accomplishmentCounts' => $accomplishmentCounts,
            'notifications' => $notifStmt->fetchAll(),
            'unseenReleases' => SystemRelease::unseenForUser((int) Auth::userId()),
        ]);
    }
}
