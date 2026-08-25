<?php
// Application-wide constants: paths, upload limits, roles.

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('MODULES_PATH', BASE_PATH . '/modules');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Base URL of the app (adjust if deployed in a sub-folder under htdocs).
define('BASE_URL', '/HRIS/public');

// Upload limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_IMAGE_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('IMAGE_MAX_DIMENSION', 1600);
define('IMAGE_THUMB_DIMENSION', 300);

// Roles (seeded in database, mirrored here for quick reference in code)
define('ROLE_ADMIN', 'Admin');
define('ROLE_HR', 'HR');
define('ROLE_SUPERVISOR', 'Supervisor');
define('ROLE_EMPLOYEE', 'Employee');

// Session
define('SESSION_NAME', 'HRIS_SESSION');

// Minimum PDS completion (%) required before a user can access anything besides the PDS form itself.
define('PDS_MIN_COMPLETION_PERCENT', 7);
