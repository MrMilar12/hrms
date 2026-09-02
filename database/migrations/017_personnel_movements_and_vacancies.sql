-- Auditable school transfers, promotions, and vacated plantilla items.

CREATE TABLE IF NOT EXISTS personnel_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    movement_type ENUM('School Transfer','Promotion') NOT NULL,
    effective_date DATE NOT NULL,
    previous_data JSON NOT NULL,
    new_data JSON NOT NULL,
    remarks VARCHAR(500) NULL,
    processed_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_movements_employee_date (employee_id, effective_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vacant_positions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    former_employee_id INT UNSIGNED NULL,
    position_id INT UNSIGNED NULL,
    item_number VARCHAR(60) NULL,
    salary_grade VARCHAR(30) NULL,
    department_id INT UNSIGNED NULL,
    school_id_code VARCHAR(30) NULL,
    station VARCHAR(180) NULL,
    vacated_on DATE NOT NULL,
    reason VARCHAR(100) NOT NULL DEFAULT 'Promotion',
    status ENUM('Vacant','Filled','Cancelled') NOT NULL DEFAULT 'Vacant',
    filled_by_employee_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_vacancies_status_date (status, vacated_on),
    FOREIGN KEY (former_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (filled_by_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

