<?php
// Employee 201-file model.

class Employee extends Model
{
    protected string $table = 'employees';

    public function listWithDetails(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.employee_number, e.date_hired, e.employment_status,
                    d.name AS department_name, p.title AS position_title
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             ORDER BY e.employee_number
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
