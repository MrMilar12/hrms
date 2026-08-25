<?php

class SearchController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $query = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            $this->json(['success' => true, 'results' => []]);
        }

        $pdo = Database::getInstance();
        $like = '%' . $query . '%';
        $results = [];

        if (Auth::can('task.view')) {
            if (Auth::can('task.create')) {
                $stmt = $pdo->prepare('SELECT id, title, status, due_date FROM tasks WHERE title LIKE ? OR description LIKE ? ORDER BY updated_at DESC LIMIT 6');
                $stmt->execute([$like, $like]);
            } else {
                $stmt = $pdo->prepare(
                    'SELECT DISTINCT t.id, t.title, ta.status, t.due_date FROM tasks t
                     JOIN task_assignments ta ON ta.task_id = t.id
                     WHERE ta.employee_id = ? AND (t.title LIKE ? OR t.description LIKE ?)
                     ORDER BY t.updated_at DESC LIMIT 6'
                );
                $stmt->execute([Auth::employeeId(), $like, $like]);
            }
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Task', 'title' => $row['title'], 'subtitle' => $row['status'] . ($row['due_date'] ? ' · Due ' . $row['due_date'] : ''), 'url' => BASE_URL . '/tasks/' . UrlId::encode((int) $row['id'])];
            }
        }

        if (Auth::can('accomplishment.create')) {
            if (Auth::can('accomplishment.view_all')) {
                $stmt = $pdo->prepare(
                    "SELECT a.id, a.title, a.status, COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), e.employee_number) employee_name
                     FROM accomplishments a JOIN employees e ON e.id = a.employee_id
                     LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
                     WHERE a.title LIKE ? OR a.description LIKE ? ORDER BY a.updated_at DESC LIMIT 6"
                );
                $stmt->execute([$like, $like]);
            } else {
                $stmt = $pdo->prepare("SELECT id, title, status, NULL employee_name FROM accomplishments WHERE employee_id = ? AND (title LIKE ? OR description LIKE ?) ORDER BY updated_at DESC LIMIT 6");
                $stmt->execute([Auth::employeeId(), $like, $like]);
            }
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Accomplishment', 'title' => $row['title'], 'subtitle' => $row['status'] . ($row['employee_name'] ? ' · ' . $row['employee_name'] : ''), 'url' => BASE_URL . '/accomplishments/' . UrlId::encode((int) $row['id'])];
            }
        }

        if (Auth::roleName() === ROLE_ADMIN || Auth::isDeveloper()) {
            $stmt = $pdo->prepare(
                "SELECT e.id, e.employee_number, d.name department_name,
                        COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), e.employee_number) employee_name
                 FROM employees e LEFT JOIN departments d ON d.id = e.department_id
                 LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
                 WHERE e.employee_number LIKE ? OR pi.first_name LIKE ? OR pi.surname LIKE ?
                 ORDER BY employee_name LIMIT 6"
            );
            $stmt->execute([$like, $like, $like]);
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Employee', 'title' => $row['employee_name'], 'subtitle' => $row['employee_number'] . ($row['department_name'] ? ' · ' . $row['department_name'] : ''), 'url' => BASE_URL . '/employees/' . UrlId::encode((int) $row['id'])];
            }
        }

        $this->json(['success' => true, 'results' => array_slice($results, 0, 14)]);
    }
}
