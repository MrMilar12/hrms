-- Position-history extension for database/seed_10k_complete_employees.sql.
-- Safe to rerun: generated records are identified by employee prefix and remarks.
SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS seed_position_pool;
CREATE TEMPORARY TABLE seed_position_pool (
    sequence_no INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    position_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    salary_grade VARCHAR(30) NULL
) ENGINE=MEMORY;

INSERT INTO seed_position_pool (position_id, title, salary_grade)
SELECT id, title, salary_grade
FROM positions
ORDER BY id;

SET @seed_position_count := (SELECT COUNT(*) FROM seed_position_pool);
SET @seed_actor_id := (SELECT MIN(id) FROM users);

-- Distribute the load-test employees across the available current positions.
UPDATE employees employee
JOIN seed_position_pool current_position
  ON current_position.sequence_no =
     (MOD(CAST(RIGHT(employee.employee_number, 5) AS UNSIGNED) - 1, @seed_position_count) + 1)
SET employee.position_id = current_position.position_id
WHERE employee.employee_number LIKE 'LOAD-%';

UPDATE employee_work_profiles work_profile
JOIN employees employee ON employee.id = work_profile.employee_id
JOIN seed_position_pool current_position ON current_position.position_id = employee.position_id
SET work_profile.item_number = CONCAT('CUR-', RIGHT(employee.employee_number, 5)),
    work_profile.salary_grade = COALESCE(NULLIF(current_position.salary_grade, ''), 'SG-11'),
    work_profile.plantilla_school_station = CONCAT('DEPED AURORA STATION ', LPAD(MOD(employee.id, 25) + 1, 2, '0')),
    work_profile.current_school_station = CONCAT('DEPED AURORA STATION ', LPAD(MOD(employee.id, 25) + 1, 2, '0'))
WHERE employee.employee_number LIKE 'LOAD-%';

-- Record a previous appointment lasting five years.
INSERT INTO personnel_movements
    (employee_id, movement_type, effective_date, previous_data, new_data, remarks, processed_by)
SELECT employee.id,
       'Historical Appointment',
       employee.date_hired,
       JSON_OBJECT(),
       JSON_OBJECT(
           'position_id', previous_position.position_id,
           'position_title', previous_position.title,
           'item_number', CONCAT('PREV-', RIGHT(employee.employee_number, 5)),
           'salary_grade', COALESCE(NULLIF(previous_position.salary_grade, ''), 'SG-9'),
           'station', CONCAT('DEPED AURORA FORMER STATION ', LPAD(MOD(employee.id, 15) + 1, 2, '0')),
           'end_date', DATE_FORMAT(DATE_ADD(employee.date_hired, INTERVAL 5 YEAR), '%Y-%m-%d')
       ),
       '10K seed: previous appointment',
       @seed_actor_id
FROM employees employee
JOIN seed_position_pool previous_position
  ON previous_position.sequence_no =
     (MOD(CAST(RIGHT(employee.employee_number, 5) AS UNSIGNED) + 36, @seed_position_count) + 1)
WHERE employee.employee_number LIKE 'LOAD-%'
  AND @seed_actor_id IS NOT NULL
  AND @seed_position_count > 1
  AND NOT EXISTS (
      SELECT 1
      FROM personnel_movements existing
      WHERE existing.employee_id = employee.id
        AND existing.remarks = '10K seed: previous appointment'
  );

-- Record the move into the employee's current position.
INSERT INTO personnel_movements
    (employee_id, movement_type, effective_date, previous_data, new_data, remarks, processed_by)
SELECT employee.id,
       'Promotion',
       DATE_ADD(employee.date_hired, INTERVAL 5 YEAR),
       JSON_OBJECT(
           'position_id', previous_position.position_id,
           'position_title', previous_position.title,
           'item_number', CONCAT('PREV-', RIGHT(employee.employee_number, 5)),
           'salary_grade', COALESCE(NULLIF(previous_position.salary_grade, ''), 'SG-9')
       ),
       JSON_OBJECT(
           'position_id', current_position.position_id,
           'position_title', current_position.title,
           'item_number', CONCAT('CUR-', RIGHT(employee.employee_number, 5)),
           'salary_grade', COALESCE(NULLIF(current_position.salary_grade, ''), 'SG-11')
       ),
       '10K seed: current appointment',
       @seed_actor_id
FROM employees employee
JOIN seed_position_pool current_position ON current_position.position_id = employee.position_id
JOIN seed_position_pool previous_position
  ON previous_position.sequence_no =
     (MOD(CAST(RIGHT(employee.employee_number, 5) AS UNSIGNED) + 36, @seed_position_count) + 1)
WHERE employee.employee_number LIKE 'LOAD-%'
  AND @seed_actor_id IS NOT NULL
  AND @seed_position_count > 1
  AND NOT EXISTS (
      SELECT 1
      FROM personnel_movements existing
      WHERE existing.employee_id = employee.id
        AND existing.remarks = '10K seed: current appointment'
  );

DROP TEMPORARY TABLE seed_position_pool;
