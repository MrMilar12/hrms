-- Migration: Accomplishments & Evidence module (run against an existing installed database).
-- Safe to run once; uses IF NOT EXISTS / INSERT IGNORE so it won't duplicate on re-run.

CREATE TABLE IF NOT EXISTS accomplishments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    task_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    accomplishment_date DATE NOT NULL,
    status ENUM('Draft','Submitted','For Review','Approved','Returned') NOT NULL DEFAULT 'Draft',
    submitted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    KEY idx_accomplishments_employee_status (employee_id, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS accomplishment_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accomplishment_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NULL,
    caption VARCHAR(255) NULL,
    file_type VARCHAR(50) NULL,
    file_size INT UNSIGNED NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accomplishment_id) REFERENCES accomplishments(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS accomplishment_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accomplishment_id INT UNSIGNED NOT NULL,
    reviewed_by INT UNSIGNED NOT NULL,
    status ENUM('Approved','Returned') NOT NULL,
    comments TEXT NULL,
    reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accomplishment_id) REFERENCES accomplishments(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO permissions (code, description) VALUES
    ('accomplishment.create', 'Create/submit own accomplishments'),
    ('accomplishment.view_all', 'View all employees'' accomplishments'),
    ('accomplishment.review', 'Approve/return submitted accomplishments');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Admin' AND p.code IN ('accomplishment.create','accomplishment.view_all','accomplishment.review');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'HR' AND p.code IN ('accomplishment.create','accomplishment.view_all','accomplishment.review');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Supervisor' AND p.code IN ('accomplishment.create','accomplishment.view_all','accomplishment.review');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Employee' AND p.code IN ('accomplishment.create');
