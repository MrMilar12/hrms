-- Scalability foundation: shared request rate limits and high-value query indexes.

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket_hash CHAR(64) NOT NULL,
    window_start DATETIME NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (bucket_hash, window_start),
    KEY idx_rate_limits_expiry (expires_at)
) ENGINE=InnoDB;

ALTER TABLE audit_logs
    ADD INDEX IF NOT EXISTS idx_audit_created (created_at),
    ADD INDEX IF NOT EXISTS idx_audit_action_created (action, created_at),
    ADD INDEX IF NOT EXISTS idx_audit_user_created (user_id, created_at);

ALTER TABLE notifications
    ADD INDEX IF NOT EXISTS idx_notifications_user_read_created (user_id, is_read, created_at);

ALTER TABLE accomplishments
    ADD INDEX IF NOT EXISTS idx_accomplishments_status_submitted (status, submitted_at),
    ADD INDEX IF NOT EXISTS idx_accomplishments_date (accomplishment_date),
    ADD FULLTEXT INDEX IF NOT EXISTS ft_accomplishments_search (title, description);

ALTER TABLE accomplishment_attachments
    ADD INDEX IF NOT EXISTS idx_accomplishment_attachments_record_date (accomplishment_id, uploaded_at);

ALTER TABLE task_assignments
    ADD INDEX IF NOT EXISTS idx_task_assignments_employee_status (employee_id, status);

ALTER TABLE tasks
    ADD INDEX IF NOT EXISTS idx_tasks_updated (updated_at);

ALTER TABLE users
    ADD INDEX IF NOT EXISTS idx_users_status (status);
