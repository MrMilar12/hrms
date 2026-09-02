-- District- and office-scoped management accounts.
-- Safe to run repeatedly through the system migration runner.

INSERT INTO roles (name, description) VALUES
    ('PSDS', 'Views and manages personnel within an assigned district'),
    ('SDC', 'Views and manages personnel within an assigned district')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('PSDS', 'SDC')
  AND p.code IN ('employee.view','pds.edit_own','pds.view_all','task.view','task.create','task.assign','task.update_status','accomplishment.create','accomplishment.view_all','accomplishment.review','report.view');

ALTER TABLE users ADD COLUMN IF NOT EXISTS scope_district VARCHAR(120) NULL AFTER role_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS scope_department_id INT UNSIGNED NULL AFTER scope_district;

