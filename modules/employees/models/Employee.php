<?php
// Employee 201-file model.

class Employee extends Model
{
    protected string $table = 'employees';

    public function listWithDetails(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.employee_number, e.date_hired, e.employment_status,
                    d.name AS department_name, p.title AS position_title,
                    pi.first_name, pi.middle_name, pi.surname, pi.name_extension,
                    wp.personnel_type, wp.current_school_station, wp.district,
                    (SELECT ep.id FROM employee_photos ep WHERE ep.employee_id = e.id ORDER BY ep.uploaded_at DESC LIMIT 1) AS photo_id,
                    ROUND((COALESCE(pcs.completed_sections, 0) / 14) * 100) AS pds_percent
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             LEFT JOIN employee_work_profiles wp ON wp.employee_id = e.id
             LEFT JOIN (
                 SELECT employee_id, SUM(is_complete) AS completed_sections
                 FROM pds_completion_status
                 GROUP BY employee_id
             ) pcs ON pcs.employee_id = e.id
             ORDER BY COALESCE(pi.surname, e.employee_number), pi.first_name, e.employee_number
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
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
