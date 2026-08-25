INSERT IGNORE INTO roles (name, description)
VALUES ('Developer', 'Protected system owner with unrestricted access');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Developer';
