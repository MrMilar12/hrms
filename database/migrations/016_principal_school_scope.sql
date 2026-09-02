-- School-scoped Principal accounts.

INSERT INTO roles (name, description)
VALUES ('Principal', 'Views and manages personnel within an assigned school')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Principal'
  AND p.code IN ('employee.view','pds.edit_own','pds.view_all','task.view','task.create','task.assign','task.update_status','accomplishment.create','accomplishment.view_all','accomplishment.review','report.view');

ALTER TABLE users ADD COLUMN IF NOT EXISTS scope_school_id_code VARCHAR(30) NULL AFTER scope_department_id;

