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

        // Make natural-language searches useful: "show me tasks about onboarding"
        // becomes a focused keyword search while preserving the original query in the UI.
        $cleanQuery = preg_replace('/\b(find|show|search|look|looking|for|me|my|the|all|who|what|where|about|please|can|you|is|are|in|on)\b/iu', ' ', $query);
        $keywords = preg_split('/\s+/u', trim((string) $cleanQuery), -1, PREG_SPLIT_NO_EMPTY);
        $searchTerm = $keywords ? end($keywords) : $query;
        if (mb_strlen($searchTerm) < 2) $searchTerm = $query;

        $pdo = Database::getInstance();
        $like = '%' . $searchTerm . '%';
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

        if (Auth::can('employee.view')) {
            $scopeParams = [];
            $scopeWhere = '';
            if (Auth::roleName() === 'PSDS' || Auth::roleName() === 'SDC') {
                $scopeStmt = $pdo->prepare('SELECT scope_district FROM users WHERE id=?'); $scopeStmt->execute([Auth::userId()]); $scopeDistrict = (string) $scopeStmt->fetchColumn();
                $scopeWhere = ' AND UPPER(TRIM(COALESCE(wp.district,"")))=UPPER(TRIM(?))' . (Auth::roleName() === 'PSDS' ? ' AND wp.personnel_type="Teaching"' : ''); $scopeParams[] = $scopeDistrict;
            } elseif (Auth::roleName() === 'Principal') {
                $scopeStmt = $pdo->prepare('SELECT scope_school_id_code FROM users WHERE id=?'); $scopeStmt->execute([Auth::userId()]); $scopeWhere = ' AND TRIM(COALESCE(wp.school_id_code,""))=TRIM(?)'; $scopeParams[] = (string) $scopeStmt->fetchColumn();
            } elseif (Auth::roleName() === ROLE_UNIT_HEAD) {
                $scopeStmt = $pdo->prepare('SELECT scope_department_id FROM users WHERE id=?'); $scopeStmt->execute([Auth::userId()]); $scopeWhere = ' AND e.department_id=?'; $scopeParams[] = (int) $scopeStmt->fetchColumn();
            }
            $stmt = $pdo->prepare(
                "SELECT e.id, e.employee_number, d.name department_name,
                        COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, ' ', pi.surname)), ''), e.employee_number) employee_name
                 FROM employees e LEFT JOIN departments d ON d.id = e.department_id
                 LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
                 LEFT JOIN employee_work_profiles wp ON wp.employee_id = e.id
                 WHERE (e.employee_number LIKE ? OR pi.first_name LIKE ? OR pi.surname LIKE ?)
                 $scopeWhere
                 ORDER BY e.employee_number LIMIT 6"
            );
            $stmt->execute(array_merge([$like, $like, $like], $scopeParams));
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Employee', 'title' => $row['employee_name'], 'subtitle' => $row['employee_number'] . ($row['department_name'] ? ' · ' . $row['department_name'] : ''), 'url' => BASE_URL . '/employees/' . UrlId::encode((int) $row['id'])];
            }
        }

        if (Auth::can('user.manage')) {
            $stmt = $pdo->prepare('SELECT id,title,salary_grade FROM positions WHERE title LIKE ? OR salary_grade LIKE ? ORDER BY title LIMIT 6');
            $stmt->execute([$like, $like]);
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Position', 'title' => $row['title'], 'subtitle' => $row['salary_grade'] ? 'Salary grade ' . $row['salary_grade'] : 'Plantilla position', 'url' => BASE_URL . '/admin/positions/' . UrlId::encode((int) $row['id'])];
            }
        }
        if (Auth::can('employee.manage')) {
            $stmt = $pdo->prepare('SELECT v.id,p.title,v.item_number,v.status FROM vacant_positions v LEFT JOIN positions p ON p.id=v.position_id WHERE p.title LIKE ? OR v.item_number LIKE ? ORDER BY v.vacated_on DESC LIMIT 6');
            $stmt->execute([$like, $like]);
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Vacancy', 'title' => $row['title'] ?: 'Vacant position', 'subtitle' => ($row['item_number'] ?: 'No item number') . ' · ' . $row['status'], 'url' => BASE_URL . '/vacant-positions'];
            }
        }

        if (Auth::can('report.view')) {
            $stmt = $pdo->prepare('SELECT id,name FROM departments WHERE name LIKE ? ORDER BY name LIMIT 6');
            $stmt->execute([$like]);
            foreach ($stmt->fetchAll() as $row) {
                $results[] = ['type' => 'Department', 'title' => $row['name'], 'subtitle' => 'Organization department', 'url' => BASE_URL . '/admin/departments'];
            }
        }

        $this->json(['success' => true, 'results' => array_slice($results, 0, 14)]);
    }
}
