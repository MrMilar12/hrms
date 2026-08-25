<?php
// Accomplishment & Evidence model: draft/submit workflow, attachments, and reviews.

class Accomplishment extends Model
{
    protected string $table = 'accomplishments';

    public const STATUSES = ['Draft', 'Submitted', 'For Review', 'Approved', 'Returned'];

    public function listForEmployee(int $employeeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, t.title AS task_title,
                    (SELECT COUNT(*) FROM accomplishment_attachments att WHERE att.accomplishment_id = a.id) AS attachment_count,
                    (SELECT att2.id FROM accomplishment_attachments att2 WHERE att2.accomplishment_id = a.id ORDER BY att2.uploaded_at LIMIT 1) AS cover_attachment_id
             FROM accomplishments a
             LEFT JOIN tasks t ON t.id = a.task_id
             WHERE a.employee_id = ?
             ORDER BY a.accomplishment_date DESC, a.id DESC'
        );
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array{status?: string, department_id?: int, employee_id?: int, search?: string} $filters
     */
    public function listFiltered(array $filters = []): array
    {
        $sql = 'SELECT a.*, e.employee_number, d.name AS department_name, t.title AS task_title,
                       COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, \' \', pi.surname)), \'\'), e.employee_number) AS employee_name,
                       (SELECT COUNT(*) FROM accomplishment_attachments att WHERE att.accomplishment_id = a.id) AS attachment_count,
                       (SELECT att2.id FROM accomplishment_attachments att2 WHERE att2.accomplishment_id = a.id ORDER BY att2.uploaded_at LIMIT 1) AS cover_attachment_id
                FROM accomplishments a
                JOIN employees e ON e.id = a.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN tasks t ON t.id = a.task_id
                LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id';
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'a.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'e.department_id = ?';
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['employee_id'])) {
            $where[] = 'a.employee_id = ?';
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.accomplishment_date DESC, a.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Employees who have never submitted an accomplishment (no row past the Draft stage). */
    public function employeesWithoutSubmission(): array
    {
        $stmt = $this->db->query(
            'SELECT e.id, e.employee_number, d.name AS department_name, p.title AS position_title,
                    COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, \' \', pi.surname)), \'\'), e.employee_number) AS employee_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             WHERE e.id NOT IN (
                 SELECT DISTINCT employee_id FROM accomplishments WHERE status <> \'Draft\'
             )
             ORDER BY e.employee_number'
        );
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, e.employee_number, t.title AS task_title,
                    COALESCE(NULLIF(TRIM(CONCAT(pi.first_name, \' \', pi.surname)), \'\'), e.employee_number) AS employee_name
             FROM accomplishments a
             JOIN employees e ON e.id = a.employee_id
             LEFT JOIN tasks t ON t.id = a.task_id
             LEFT JOIN pds_personal_info pi ON pi.employee_id = e.id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function statusCounts(int $employeeId): array
    {
        $stmt = $this->db->prepare('SELECT status, COUNT(*) AS total FROM accomplishments WHERE employee_id = ? GROUP BY status');
        $stmt->execute([$employeeId]);
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public function globalStatusCounts(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS total FROM accomplishments GROUP BY status');
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public function submit(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE accomplishments SET status = 'For Review', submitted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function addAttachment(int $accomplishmentId, int $uploadedBy, string $filePath, string $thumbnailPath, ?string $caption, string $fileType, int $fileSize): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO accomplishment_attachments (accomplishment_id, uploaded_by, file_path, thumbnail_path, caption, file_type, file_size, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$accomplishmentId, $uploadedBy, $filePath, $thumbnailPath, $caption, $fileType, $fileSize]);
        return (int) $this->db->lastInsertId();
    }

    public function attachments(int $accomplishmentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM accomplishment_attachments WHERE accomplishment_id = ? ORDER BY uploaded_at');
        $stmt->execute([$accomplishmentId]);
        return $stmt->fetchAll();
    }

    public function deleteAttachment(int $attachmentId, int $accomplishmentId): void
    {
        $stmt = $this->db->prepare('DELETE FROM accomplishment_attachments WHERE id = ? AND accomplishment_id = ?');
        $stmt->execute([$attachmentId, $accomplishmentId]);
    }

    public function review(int $accomplishmentId, int $reviewerId, string $status, ?string $comments): void
    {
        $this->db->beginTransaction();
        try {
            $newStatus = $status === 'Approved' ? 'Approved' : 'Returned';
            $this->update($accomplishmentId, ['status' => $newStatus]);

            $stmt = $this->db->prepare(
                'INSERT INTO accomplishment_reviews (accomplishment_id, reviewed_by, status, comments, reviewed_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$accomplishmentId, $reviewerId, $newStatus, $comments]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function reviews(int $accomplishmentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.username AS reviewer_username FROM accomplishment_reviews r
             JOIN users u ON u.id = r.reviewed_by
             WHERE r.accomplishment_id = ? ORDER BY r.reviewed_at DESC'
        );
        $stmt->execute([$accomplishmentId]);
        return $stmt->fetchAll();
    }
}
