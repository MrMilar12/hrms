-- Stores only profile fields that do not already exist in employees or PDS tables.
CREATE TABLE IF NOT EXISTS employee_work_profiles (
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
