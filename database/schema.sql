-- HRIS Phase 1 schema (MySQL / InnoDB, 3NF)
-- Run this against an empty `hris` database.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================================
-- 1. Core / system tables
-- ==========================================================

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE, -- e.g. task.create, pds.approve
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    parent_department_id INT UNSIGNED NULL,
    FOREIGN KEY (parent_department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE positions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    salary_grade VARCHAR(10) NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 2. Employee 201 file (core identity)
-- ==========================================================

CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(30) NOT NULL UNIQUE,
    department_id INT UNSIGNED NULL,
    position_id INT UNSIGNED NULL,
    date_hired DATE NULL,
    employment_status ENUM('Regular','Casual','Contractual','Job Order','Probationary') NOT NULL DEFAULT 'Probationary',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    two_factor_secret VARCHAR(64) NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    role_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE employee_work_profiles (
    employee_id INT UNSIGNED PRIMARY KEY,
    personnel_type ENUM('Teaching','Non-Teaching') NOT NULL DEFAULT 'Non-Teaching',
    school_id_code VARCHAR(30) NULL,
    item_number VARCHAR(60) NULL,
    salary_grade VARCHAR(30) NULL,
    plantilla_school_station VARCHAR(180) NULL,
    current_school_station VARCHAR(180) NULL,
    district VARCHAR(120) NULL,
    grade_levels_taught VARCHAR(180) NULL,
    specialization VARCHAR(180) NULL,
    subjects_taught TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INT UNSIGNED NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE system_releases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    changes TEXT NOT NULL,
    released_at DATETIME NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    github_release_id BIGINT UNSIGNED NULL UNIQUE,
    release_url VARCHAR(500) NULL,
    source_commit CHAR(40) NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_system_releases_published (is_published, released_at)
) ENGINE=InnoDB;

CREATE TABLE user_release_views (
    user_id INT UNSIGNED NOT NULL,
    release_id INT UNSIGNED NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, release_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (release_id) REFERENCES system_releases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE schema_migrations (
    migration VARCHAR(190) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE system_deployments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NULL,
    from_version VARCHAR(30) NULL,
    to_version VARCHAR(30) NULL,
    from_commit CHAR(40) NULL,
    to_commit CHAR(40) NULL,
    status ENUM('success','failed') NOT NULL,
    details TEXT NULL,
    backup_files VARCHAR(500) NULL,
    backup_database VARCHAR(500) NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_system_deployments_started (started_at)
) ENGINE=InnoDB;

CREATE TABLE system_update_state (
    id TINYINT UNSIGNED PRIMARY KEY,
    deployed_commit CHAR(40) NULL,
    deployed_version VARCHAR(30) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
    bucket_hash CHAR(64) NOT NULL,
    window_start DATETIME NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (bucket_hash, window_start),
    KEY idx_rate_limits_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 3. PDS — CS Form No. 212
-- ==========================================================

CREATE TABLE pds_personal_info (
    employee_id INT UNSIGNED PRIMARY KEY,
    surname VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    name_extension VARCHAR(20) NULL, -- Jr., Sr., III
    birth_date DATE NULL,
    birth_place VARCHAR(150) NULL,
    sex ENUM('Male','Female') NULL,
    civil_status ENUM('Single','Married','Widowed','Separated','Others') NULL,
    height_m DECIMAL(4,2) NULL,
    weight_kg DECIMAL(5,2) NULL,
    blood_type VARCHAR(5) NULL,
    citizenship VARCHAR(50) NULL,
    dual_citizenship_country VARCHAR(100) NULL,
    dual_citizenship_type ENUM('By Birth','By Naturalization') NULL,
    gsis_no VARCHAR(30) NULL,
    pagibig_no VARCHAR(30) NULL,
    philhealth_no VARCHAR(30) NULL,
    sss_no VARCHAR(30) NULL,
    philsys_card_no VARCHAR(50) NULL,
    tin_no VARCHAR(30) NULL,
    agency_employee_no VARCHAR(30) NULL,
    telephone_no VARCHAR(30) NULL,
    mobile_no VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    government_issued_id VARCHAR(100) NULL,
    government_id_number VARCHAR(100) NULL,
    government_id_issuance VARCHAR(150) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    address_type ENUM('Residential','Permanent') NOT NULL,
    house_block_lot VARCHAR(150) NULL,
    street VARCHAR(150) NULL,
    subdivision_village VARCHAR(150) NULL,
    barangay VARCHAR(100) NULL,
    city_municipality VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    zip_code VARCHAR(10) NULL,
    UNIQUE KEY uq_employee_address_type (employee_id, address_type),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_family_background (
    employee_id INT UNSIGNED PRIMARY KEY,
    spouse_surname VARCHAR(100) NULL,
    spouse_first_name VARCHAR(100) NULL,
    spouse_middle_name VARCHAR(100) NULL,
    spouse_name_extension VARCHAR(20) NULL,
    spouse_occupation VARCHAR(150) NULL,
    spouse_employer VARCHAR(150) NULL,
    spouse_business_address VARCHAR(255) NULL,
    spouse_telephone_no VARCHAR(30) NULL,
    father_surname VARCHAR(100) NULL,
    father_first_name VARCHAR(100) NULL,
    father_middle_name VARCHAR(100) NULL,
    father_name_extension VARCHAR(20) NULL,
    mother_maiden_surname VARCHAR(100) NULL,
    mother_first_name VARCHAR(100) NULL,
    mother_middle_name VARCHAR(100) NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_children (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    birth_date DATE NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_educational_background (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    level ENUM('Elementary','Secondary','Vocational','College','Graduate Studies') NOT NULL,
    school_name VARCHAR(200) NULL,
    degree_course VARCHAR(200) NULL,
    period_from YEAR NULL,
    period_to YEAR NULL,
    highest_units_earned VARCHAR(100) NULL,
    year_graduated YEAR NULL,
    scholarship_honors VARCHAR(200) NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_civil_service_eligibility (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    eligibility_name VARCHAR(200) NOT NULL,
    rating VARCHAR(20) NULL,
    exam_date DATE NULL,
    exam_place VARCHAR(150) NULL,
    license_number VARCHAR(50) NULL,
    license_validity DATE NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_work_experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    date_from DATE NULL,
    date_to DATE NULL,
    position_title VARCHAR(200) NULL,
    department_agency VARCHAR(200) NULL,
    monthly_salary DECIMAL(12,2) NULL,
    salary_grade_step VARCHAR(20) NULL,
    appointment_status VARCHAR(100) NULL,
    is_government TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_voluntary_work (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    organization_name VARCHAR(200) NULL,
    organization_address VARCHAR(200) NULL,
    date_from DATE NULL,
    date_to DATE NULL,
    number_of_hours INT UNSIGNED NULL,
    position_nature_of_work VARCHAR(200) NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_learning_development (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    date_from DATE NULL,
    date_to DATE NULL,
    number_of_hours INT UNSIGNED NULL,
    type_of_ld ENUM('Managerial','Supervisory','Technical','Others') NULL,
    conducted_by VARCHAR(200) NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_other_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    category ENUM('Skill','Hobby','Recognition','Membership') NOT NULL,
    description VARCHAR(255) NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_non_academic_distinctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_memberships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    organization_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_questionnaire (
    employee_id INT UNSIGNED PRIMARY KEY,
    q34a_related_by_consanguinity TINYINT(1) NULL,
    q34a_details VARCHAR(255) NULL,
    q34b_related_to_appointing_authority TINYINT(1) NULL,
    q34b_details VARCHAR(255) NULL,
    q35a_found_guilty_admin_case TINYINT(1) NULL,
    q35a_details VARCHAR(255) NULL,
    q35b_criminal_charged TINYINT(1) NULL,
    q35b_details VARCHAR(255) NULL,
    q35b_date_filed DATE NULL,
    q35b_status_cases VARCHAR(150) NULL,
    q35c_convicted TINYINT(1) NULL,
    q35c_details VARCHAR(255) NULL,
    q35d_separated_from_service TINYINT(1) NULL,
    q35d_details VARCHAR(255) NULL,
    q36_candidate_last_election TINYINT(1) NULL,
    q36_details VARCHAR(255) NULL,
    q37_resigned_to_avoid_campaign TINYINT(1) NULL,
    q37_details VARCHAR(255) NULL,
    q38a_immigrant_status TINYINT(1) NULL,
    q38a_details VARCHAR(255) NULL,
    q39_indigenous_group TINYINT(1) NULL,
    q39_details VARCHAR(255) NULL,
    q40_pwd TINYINT(1) NULL,
    q40_details VARCHAR(255) NULL,
    q41_solo_parent TINYINT(1) NULL,
    q41_details VARCHAR(255) NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_character_references (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NULL,
    telephone_no VARCHAR(30) NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pds_completion_status (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    section VARCHAR(50) NOT NULL,
    is_complete TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employee_section (employee_id, section),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================================
-- 4. Task management
-- ==========================================================

CREATE TABLE tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    department_id INT UNSIGNED NULL,
    priority ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    status ENUM('Open','In Progress','For Review','Done','Cancelled') NOT NULL DEFAULT 'Open',
    due_date DATE NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FULLTEXT KEY ft_tasks_title (title),
    KEY idx_tasks_status_due (status, due_date)
) ENGINE=InnoDB;

CREATE TABLE task_assignments (
    task_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    status ENUM('Open','In Progress','For Review','Done','Cancelled') NOT NULL DEFAULT 'Open',
    submitted_at DATETIME NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, employee_id),
    KEY idx_task_assignments_employee_task (employee_id, task_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE task_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NULL,
    caption VARCHAR(255) NULL,
    file_type VARCHAR(50) NULL,
    file_size INT UNSIGNED NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE task_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE task_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 5. Accomplishments & Evidence
-- ==========================================================

CREATE TABLE accomplishments (
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

CREATE TABLE accomplishment_attachments (
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

CREATE TABLE accomplishment_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accomplishment_id INT UNSIGNED NOT NULL,
    reviewed_by INT UNSIGNED NOT NULL,
    status ENUM('Approved','Returned') NOT NULL,
    comments TEXT NULL,
    reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accomplishment_id) REFERENCES accomplishments(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
