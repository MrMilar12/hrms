<?php
// Employee 201-file model.

class Employee extends Model
{
    protected string $table = 'employees';

    private function viewerScope(): ?array
    {
        if (!in_array(Auth::roleName(), ['PSDS', 'SDC', 'Principal', ROLE_UNIT_HEAD], true)) return null;
        $stmt = $this->db->prepare('SELECT scope_district, scope_department_id, scope_school_id_code FROM users WHERE id = ?');
        $stmt->execute([Auth::userId()]);
        return $stmt->fetch() ?: ['scope_district' => null, 'scope_department_id' => null];
    }

    private function visibilityClause(array &$params): string
    {
        $scope = $this->viewerScope();
        if (!$scope) return '';
        if (Auth::roleName() === 'PSDS') {
            $params[] = (string) ($scope['scope_district'] ?? '');
            return ' WHERE UPPER(TRIM(COALESCE(wp.district, ""))) = UPPER(TRIM(?)) AND wp.personnel_type = "Teaching"';
        }
        if (Auth::roleName() === 'SDC') {
            $params[] = (string) ($scope['scope_district'] ?? '');
            return ' WHERE UPPER(TRIM(COALESCE(wp.district, ""))) = UPPER(TRIM(?))';
        }
        if (Auth::roleName() === 'Principal') {
            $params[] = (string) ($scope['scope_school_id_code'] ?? '');
            return ' WHERE TRIM(COALESCE(wp.school_id_code, "")) = TRIM(?)';
        }
        $params[] = (int) ($scope['scope_department_id'] ?? 0);
        return ' WHERE e.department_id = ?';
    }

    private function filteredClause(array &$params, array $filters = []): string
    {
        $where = $this->visibilityClause($params);
        $conditions = [];

        if (($filters['q'] ?? '') !== '') {
            $term = '%' . $filters['q'] . '%';
            $conditions[] = '(e.employee_number LIKE ? OR CONCAT_WS(" ", pi.first_name, pi.middle_name, pi.surname, pi.name_extension) LIKE ? OR d.name LIKE ? OR p.title LIKE ? OR wp.current_school_station LIKE ?)';
            array_push($params, $term, $term, $term, $term, $term);
        }
        if (($filters['personnel_type'] ?? '') !== '') {
            $conditions[] = 'wp.personnel_type = ?';
            $params[] = $filters['personnel_type'];
        }
        if (($filters['employment_status'] ?? '') !== '') {
            $conditions[] = 'e.employment_status = ?';
            $params[] = $filters['employment_status'];
        }
        if (($filters['department_id'] ?? 0) > 0) {
            $conditions[] = 'e.department_id = ?';
            $params[] = (int) $filters['department_id'];
        }
        if (($filters['position_id'] ?? 0) > 0) {
            $conditions[] = 'e.position_id = ?';
            $params[] = (int) $filters['position_id'];
        }
        if (($filters['district'] ?? '') !== '') {
            $conditions[] = 'UPPER(TRIM(COALESCE(wp.district, ""))) = UPPER(TRIM(?))';
            $params[] = $filters['district'];
        }

        if (!$conditions) return $where;
        return $where . ($where === '' ? ' WHERE ' : ' AND ') . implode(' AND ', $conditions);
    }

    public function countVisible(array $filters = []): int
    {
        $params = [];
        $where = $this->filteredClause($params, $filters);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM employees e LEFT JOIN departments d ON d.id=e.department_id LEFT JOIN positions p ON p.id=e.position_id LEFT JOIN pds_personal_info pi ON pi.employee_id=e.id LEFT JOIN employee_work_profiles wp ON wp.employee_id=e.id' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function isVisibleToCurrentUser(int $employeeId): bool
    {
        if (Auth::employeeId() === $employeeId) return true;
        $params = [];
        $where = $this->visibilityClause($params);
        if ($where === '') return true;
        $params[] = $employeeId;
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM employees e LEFT JOIN employee_work_profiles wp ON wp.employee_id = e.id' . $where . ' AND e.id = ?');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function listWithDetails(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $params = [];
        $where = $this->filteredClause($params, $filters);
        $order = ($filters['q'] ?? '') !== '' ? 'ORDER BY COALESCE(pi.surname, e.employee_number), pi.first_name, e.employee_number' : 'ORDER BY e.id';
        $stmt = $this->db->prepare(
            'SELECT e.id, e.employee_number, e.date_hired, e.employment_status,
                    d.name AS department_name, p.title AS position_title,
                    pi.first_name, pi.middle_name, pi.surname, pi.name_extension,
                    wp.personnel_type, wp.current_school_station, wp.district,
                    (SELECT ep.id FROM employee_photos ep WHERE ep.employee_id = e.id ORDER BY ep.uploaded_at DESC LIMIT 1) AS photo_id,
                    ROUND((COALESCE((SELECT SUM(pcs.is_complete) FROM pds_completion_status pcs WHERE pcs.employee_id=e.id), 0) / 14) * 100) AS pds_percent
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             LEFT JOIN employee_work_profiles wp ON wp.employee_id = e.id' . $where . '
             ' . $order . '
             LIMIT ? OFFSET ?'
        );
        foreach ($params as $index => $param) $stmt->bindValue($index + 1, $param);
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, d.name AS department_name, p.title AS position_title
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function profileSnapshot(int $employeeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pi.*, wp.personnel_type, wp.school_id_code, wp.item_number, wp.salary_grade,
                    wp.plantilla_school_station, wp.current_school_station, wp.district,
                    wp.grade_levels_taught, wp.specialization, wp.subjects_taught,
                    a.house_block_lot, a.street, a.subdivision_village, a.barangay,
                    a.city_municipality, a.province, a.zip_code,
                    u.status AS account_status, u.last_login, u.two_factor_enabled,
                    r.name AS role_name
             FROM employees e
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             LEFT JOIN employee_work_profiles wp ON wp.employee_id = e.id
             LEFT JOIN pds_addresses a ON a.employee_id = e.id AND a.address_type = "Residential"
             LEFT JOIN users u ON u.employee_id = e.id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE e.id = ? LIMIT 1'
        );
        $stmt->execute([$employeeId]);
        return $stmt->fetch() ?: [];
    }

    public function relatedSummary(int $employeeId): array
    {
        $taskStmt = $this->db->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(ta.status = "Done") AS done,
                    SUM(ta.status NOT IN ("Done", "Cancelled") AND t.due_date < CURDATE()) AS overdue
             FROM task_assignments ta JOIN tasks t ON t.id = ta.task_id
             WHERE ta.employee_id = ?'
        );
        $taskStmt->execute([$employeeId]);
        $accomplishmentStmt = $this->db->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Approved") AS approved,
                    SUM(status IN ("Submitted", "For Review")) AS pending,
                    SUM(status = "Returned") AS returned_count
             FROM accomplishments WHERE employee_id = ?'
        );
        $accomplishmentStmt->execute([$employeeId]);

        return [
            'tasks' => $taskStmt->fetch() ?: [],
            'accomplishments' => $accomplishmentStmt->fetch() ?: [],
        ];
    }

    public function recentRelatedRecords(int $employeeId, int $limit = 5): array
    {
        $tasks = $this->db->prepare(
            'SELECT t.id, t.title, t.priority, t.due_date, ta.status
             FROM task_assignments ta JOIN tasks t ON t.id = ta.task_id
             WHERE ta.employee_id = ?
             ORDER BY COALESCE(t.due_date, "9999-12-31") DESC, t.id DESC LIMIT ?'
        );
        $tasks->bindValue(1, $employeeId, PDO::PARAM_INT);
        $tasks->bindValue(2, $limit, PDO::PARAM_INT);
        $tasks->execute();

        $accomplishments = $this->db->prepare(
            'SELECT id, title, accomplishment_date, status
             FROM accomplishments WHERE employee_id = ?
             ORDER BY accomplishment_date DESC, id DESC LIMIT ?'
        );
        $accomplishments->bindValue(1, $employeeId, PDO::PARAM_INT);
        $accomplishments->bindValue(2, $limit, PDO::PARAM_INT);
        $accomplishments->execute();

        return ['tasks' => $tasks->fetchAll(), 'accomplishments' => $accomplishments->fetchAll()];
    }

    public function latestPhoto(int $employeeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM employee_photos WHERE employee_id = ? ORDER BY uploaded_at DESC LIMIT 1'
        );
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function savePhoto(int $employeeId, string $filePath, string $thumbnailPath): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employee_photos (employee_id, file_path, thumbnail_path, uploaded_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$employeeId, $filePath, $thumbnailPath]);
        return (int) $this->db->lastInsertId();
    }
}
