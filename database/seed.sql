-- Seed data: roles, permissions, RBAC mapping, and reference lookups.
-- Default admin account is created separately by running `php scripts/seed_admin.php`.

INSERT INTO roles (name, description) VALUES
    ('Admin', 'Full system access'),
    ('HR', 'Manages employees, PDS, and reports'),
    ('Supervisor', 'Manages department tasks'),
    ('Unit Head', 'Manages unit employees, assignments, reviews, and reports'),
    ('Employee', 'Self-service: own PDS and assigned tasks');

INSERT INTO permissions (code, description) VALUES
    ('employee.view', 'View employee 201 records'),
    ('employee.manage', 'Create/edit/delete employee records'),
    ('pds.edit_own', 'Edit own PDS'),
    ('pds.view_all', 'View all employees'' PDS'),
    ('pds.approve', 'Approve/lock PDS records'),
    ('task.view', 'View tasks'),
    ('task.create', 'Create tasks'),
    ('task.assign', 'Assign tasks to employees'),
    ('task.update_status', 'Update task status'),
    ('accomplishment.create', 'Create/submit own accomplishments'),
    ('accomplishment.view_all', 'View all employees'' accomplishments'),
    ('accomplishment.review', 'Approve/return submitted accomplishments'),
    ('report.view', 'View reports/dashboards'),
    ('user.manage', 'Manage user accounts and roles');

-- Admin: all permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Admin';

-- HR: employee + PDS + reports
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'HR' AND p.code IN ('employee.view','employee.manage','pds.edit_own','pds.view_all','pds.approve','task.view','task.create','task.assign','task.update_status','accomplishment.create','accomplishment.view_all','accomplishment.review','report.view');

-- Supervisor: task management + view employees
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Supervisor' AND p.code IN ('employee.view','pds.edit_own','task.view','task.create','task.assign','task.update_status','accomplishment.create','accomplishment.view_all','accomplishment.review','report.view');

-- Unit Head: unit-level task, accomplishment review, employee view, and reports
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Unit Head' AND p.code IN ('employee.view','pds.edit_own','task.view','task.create','task.assign','task.update_status','accomplishment.create','accomplishment.view_all','accomplishment.review','report.view');

-- Employee: self-service only
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Employee' AND p.code IN ('pds.edit_own','task.view','task.update_status','accomplishment.create');

INSERT INTO departments (name, parent_department_id) VALUES
    ('Office of the Director', NULL),
    ('Human Resources', 1),
    ('Information Technology', 1);

INSERT INTO positions (title, salary_grade) VALUES
    ('Administrative Officer', 'SG-15'),
    ('IT Officer', 'SG-15'),
    ('HR Assistant', 'SG-8');

INSERT INTO employees (employee_number, department_id, position_id, date_hired, employment_status)
VALUES ('EMP-0001', 2, 3, CURDATE(), 'Regular');

-- Default admin user account is created by running `php scripts/seed_admin.php`
-- (this generates a real bcrypt hash instead of a hardcoded one).
