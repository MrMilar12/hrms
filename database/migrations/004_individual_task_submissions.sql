-- Each assignee tracks and submits their own task progress independently.
ALTER TABLE task_assignments
    ADD COLUMN status ENUM('Open','In Progress','For Review','Done','Cancelled') NOT NULL DEFAULT 'Open' AFTER employee_id,
    ADD COLUMN submitted_at DATETIME NULL AFTER status,
    ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER assigned_at;

-- Preserve the current task state for existing assignments during migration.
UPDATE task_assignments ta
JOIN tasks t ON t.id = ta.task_id
SET ta.status = t.status,
    ta.submitted_at = CASE WHEN t.status IN ('For Review', 'Done') THEN NOW() ELSE NULL END;

ALTER TABLE task_status_history
    ADD COLUMN employee_id INT UNSIGNED NULL AFTER changed_by,
    ADD KEY idx_task_history_employee (employee_id),
    ADD CONSTRAINT fk_task_history_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL;
