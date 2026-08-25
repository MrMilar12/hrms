<?php
// Task model: task CRUD + assignments, attachments, comments, and status history.

class Task extends Model
{
    protected string $table = 'tasks';

    public function listWithDetails(?int $employeeId = null, int $limit = 50, int $offset = 0): array
    {
        if ($employeeId !== null) {
            $stmt = $this->db->prepare(
                'SELECT DISTINCT t.*, d.name AS department_name
                 FROM tasks t
                 LEFT JOIN departments d ON d.id = t.department_id
                 JOIN task_assignments ta ON ta.task_id = t.id
                 WHERE ta.employee_id = ?
                 ORDER BY t.due_date IS NULL, t.due_date ASC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bindValue(1, $employeeId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare(
                'SELECT t.*, d.name AS department_name
                 FROM tasks t LEFT JOIN departments d ON d.id = t.department_id
                 ORDER BY t.due_date IS NULL, t.due_date ASC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, d.name AS department_name
             FROM tasks t LEFT JOIN departments d ON d.id = t.department_id
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function assignees(int $taskId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.employee_number FROM task_assignments ta
             JOIN employees e ON e.id = ta.employee_id
             WHERE ta.task_id = ?'
        );
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    public function isAssignee(int $taskId, int $employeeId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM task_assignments WHERE task_id = ? AND employee_id = ?');
        $stmt->execute([$taskId, $employeeId]);
        return (bool) $stmt->fetchColumn();
    }

    public function assign(int $taskId, array $employeeIds): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO task_assignments (task_id, employee_id, assigned_at) VALUES (?, ?, NOW())'
        );
        foreach ($employeeIds as $employeeId) {
            $stmt->execute([$taskId, (int) $employeeId]);
        }
    }

    public function updateStatus(int $taskId, string $newStatus, int $changedBy): void
    {
        $current = $this->find($taskId);
        if (!$current) {
            throw new RuntimeException('Task not found.');
        }
        $oldStatus = $current['status'];

        $this->db->beginTransaction();
        try {
            $this->update($taskId, ['status' => $newStatus]);
            $stmt = $this->db->prepare(
                'INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, changed_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$taskId, $oldStatus, $newStatus, $changedBy]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function statusHistory(int $taskId): array
    {
        $stmt = $this->db->prepare(
            'SELECT h.*, u.username AS changed_by_username FROM task_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.task_id = ? ORDER BY h.changed_at DESC'
        );
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    public function addComment(int $taskId, int $userId, string $comment): int
    {
        $stmt = $this->db->prepare('INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$taskId, $userId, $comment]);
        return (int) $this->db->lastInsertId();
    }

    public function comments(int $taskId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.username FROM task_comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.task_id = ? ORDER BY c.created_at ASC'
        );
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    public function addAttachment(int $taskId, int $uploadedBy, string $filePath, string $thumbnailPath, ?string $caption, string $fileType, int $fileSize): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO task_attachments (task_id, uploaded_by, file_path, thumbnail_path, caption, file_type, file_size, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$taskId, $uploadedBy, $filePath, $thumbnailPath, $caption, $fileType, $fileSize]);
        return (int) $this->db->lastInsertId();
    }

    public function attachments(int $taskId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM task_attachments WHERE task_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }
}
