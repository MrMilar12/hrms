-- Adds the Unit Head role and repairs role-to-feature access.
-- Safe to run repeatedly.

INSERT INTO roles (name, description)
VALUES ('Unit Head', 'Manages unit employees, assignments, reviews, and reports')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Unit Head'
  AND p.code IN (
      'employee.view', 'pds.edit_own', 'task.view', 'task.create', 'task.assign',
      'task.update_status', 'accomplishment.create', 'accomplishment.view_all',
      'accomplishment.review', 'report.view'
  );

-- Ensure HR has its complete operational permission set even on databases
-- created before later modules were added.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'HR'
  AND p.code IN (
      'employee.view', 'employee.manage', 'pds.edit_own', 'pds.view_all',
      'pds.approve', 'task.view', 'task.create', 'task.assign',
      'task.update_status', 'accomplishment.create', 'accomplishment.view_all',
      'accomplishment.review', 'report.view'
  );
