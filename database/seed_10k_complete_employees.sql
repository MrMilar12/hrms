-- Idempotent 10,000-record dataset for performance testing.
-- Sample records are isolated by the LOAD- employee-number prefix and have no login accounts.
SET NAMES utf8mb4;

INSERT IGNORE INTO employees (employee_number, department_id, position_id, date_hired, employment_status)
SELECT CONCAT('LOAD-', LPAD(nums.n, 5, '0')),
       (SELECT MIN(id) FROM departments),
       (SELECT MIN(id) FROM positions),
       DATE_ADD('2010-01-01', INTERVAL (nums.n MOD 5000) DAY),
       'Regular'
FROM (
    SELECT ones.d + tens.d * 10 + hundreds.d * 100 + thousands.d * 1000 + 1 AS n
    FROM (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
    CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
    CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) hundreds
    CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) thousands
) nums;

INSERT INTO employee_work_profiles
    (employee_id, personnel_type, school_id_code, item_number, salary_grade, plantilla_school_station,
     current_school_station, district, grade_levels_taught, specialization, subjects_taught)
SELECT e.id, IF(CAST(RIGHT(e.employee_number, 5) AS UNSIGNED) MOD 2 = 0, 'Teaching', 'Non-Teaching'),
       CONCAT('SCH-', RIGHT(e.employee_number, 5)), CONCAT('ITEM-', RIGHT(e.employee_number, 5)),
       'SG-11', 'Sample Plantilla School', 'Sample Current School', 'District I',
       IF(CAST(RIGHT(e.employee_number, 5) AS UNSIGNED) MOD 2 = 0, 'Grade 6', NULL),
       IF(CAST(RIGHT(e.employee_number, 5) AS UNSIGNED) MOD 2 = 0, 'Mathematics', NULL),
       IF(CAST(RIGHT(e.employee_number, 5) AS UNSIGNED) MOD 2 = 0, 'Mathematics, Science', NULL)
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
ON DUPLICATE KEY UPDATE personnel_type = VALUES(personnel_type);

INSERT IGNORE INTO pds_personal_info
    (employee_id, surname, first_name, middle_name, birth_date, birth_place, sex, civil_status,
     height_m, weight_kg, blood_type, citizenship, gsis_no, pagibig_no, philhealth_no,
     philsys_card_no, tin_no, agency_employee_no, telephone_no, mobile_no, email,
     government_issued_id, government_id_number, government_id_issuance)
SELECT e.id, CONCAT('SAMPLE', RIGHT(e.employee_number, 5)), CONCAT('EMPLOYEE', RIGHT(e.employee_number, 5)),
       'TEST', DATE_ADD('1980-01-01', INTERVAL (CAST(RIGHT(e.employee_number, 5) AS UNSIGNED) MOD 7000) DAY),
       'Baler, Aurora', IF(e.id MOD 2 = 0, 'Male', 'Female'), 'Single', 1.65, 60.00, 'O+',
       'Filipino', CONCAT('GSIS-', RIGHT(e.employee_number, 5)), CONCAT('PAG-', RIGHT(e.employee_number, 5)),
       CONCAT('PHIL-', RIGHT(e.employee_number, 5)), CONCAT('PCN-', RIGHT(e.employee_number, 5)),
       CONCAT('TIN-', RIGHT(e.employee_number, 5)), e.employee_number, '02-8000-0000',
       CONCAT('09', LPAD(CAST(RIGHT(e.employee_number, 5) AS UNSIGNED), 9, '0')),
       CONCAT(LOWER(e.employee_number), '@example.test'), 'Driver''s License',
       CONCAT('DL-', RIGHT(e.employee_number, 5)), '01/01/2026 - Aurora'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%';

INSERT IGNORE INTO pds_addresses
    (employee_id, address_type, house_block_lot, street, subdivision_village, barangay, city_municipality, province, zip_code)
SELECT e.id, types.address_type, 'Lot 1 Block 1', 'Rizal Street', 'Sample Village',
       'Barangay Poblacion', 'Baler', 'Aurora', '3200'
FROM employees e
CROSS JOIN (SELECT 'Residential' address_type UNION ALL SELECT 'Permanent') types
WHERE e.employee_number LIKE 'LOAD-%';

INSERT IGNORE INTO pds_family_background
    (employee_id, spouse_surname, spouse_first_name, spouse_middle_name, spouse_occupation,
     spouse_employer, spouse_business_address, spouse_telephone_no, father_surname,
     father_first_name, father_middle_name, mother_maiden_surname, mother_first_name, mother_middle_name)
SELECT e.id, 'SAMPLE', 'SPOUSE', 'TEST', 'Teacher', 'Sample School', 'Baler, Aurora',
       '09170000000', 'SAMPLE', 'FATHER', 'TEST', 'MAIDEN', 'MOTHER', 'TEST'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%';

INSERT INTO pds_children (employee_id, full_name, birth_date)
SELECT e.id, CONCAT('CHILD ', RIGHT(e.employee_number, 5)), '2010-01-01'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_children p WHERE p.employee_id = e.id);

INSERT INTO pds_educational_background
    (employee_id, level, school_name, degree_course, period_from, period_to, highest_units_earned, year_graduated, scholarship_honors)
SELECT e.id, 'College', 'Sample State University', 'Bachelor of Science in Education', 2000, 2004, 'Graduated', 2004, 'With Honors'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_educational_background p WHERE p.employee_id = e.id);

INSERT INTO pds_civil_service_eligibility
    (employee_id, eligibility_name, rating, exam_date, exam_place, license_number, license_validity)
SELECT e.id, 'Career Service Professional', '85.00', '2005-05-15', 'Baler, Aurora', CONCAT('LIC-', RIGHT(e.employee_number, 5)), '2030-05-15'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_civil_service_eligibility p WHERE p.employee_id = e.id);

INSERT INTO pds_work_experience
    (employee_id, date_from, date_to, position_title, department_agency, monthly_salary, salary_grade_step, appointment_status, is_government)
SELECT e.id, '2015-01-01', CURDATE(), 'Sample Position', 'Department of Education', 35000.00, 'SG-11-1', 'Permanent', 1
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_work_experience p WHERE p.employee_id = e.id);

INSERT INTO pds_voluntary_work
    (employee_id, organization_name, organization_address, date_from, date_to, number_of_hours, position_nature_of_work)
SELECT e.id, 'Philippine Red Cross', 'Aurora Chapter', '2025-01-01', '2025-12-31', 40, 'Community Volunteer'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_voluntary_work p WHERE p.employee_id = e.id);

INSERT INTO pds_learning_development
    (employee_id, title, date_from, date_to, number_of_hours, type_of_ld, conducted_by)
SELECT e.id, 'Professional Development Program', '2025-06-01', '2025-06-03', 24, 'Technical', 'Department of Education'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_learning_development p WHERE p.employee_id = e.id);

INSERT INTO pds_other_info (employee_id, category, description)
SELECT e.id, categories.category, categories.description
FROM employees e CROSS JOIN (
    SELECT 'Skill' category, 'Public Speaking' description
    UNION ALL SELECT 'Hobby', 'Reading'
    UNION ALL SELECT 'Recognition', 'Outstanding Employee'
    UNION ALL SELECT 'Membership', 'Employees Association'
) categories
WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_other_info p WHERE p.employee_id = e.id);

INSERT INTO pds_non_academic_distinctions (employee_id, description)
SELECT e.id, 'Community Service Award' FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_non_academic_distinctions p WHERE p.employee_id = e.id);

INSERT INTO pds_memberships (employee_id, organization_name)
SELECT e.id, 'Professional Employees Association' FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_memberships p WHERE p.employee_id = e.id);

INSERT IGNORE INTO pds_questionnaire
    (employee_id, q34a_related_by_consanguinity, q34b_related_to_appointing_authority,
     q35a_found_guilty_admin_case, q35b_criminal_charged, q35c_convicted,
     q35d_separated_from_service, q36_candidate_last_election, q37_resigned_to_avoid_campaign,
     q38a_immigrant_status, q39_indigenous_group, q40_pwd, q41_solo_parent)
SELECT e.id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
FROM employees e WHERE e.employee_number LIKE 'LOAD-%';

INSERT INTO pds_character_references (employee_id, full_name, address, telephone_no)
SELECT e.id, 'MARIA SANTOS', 'Baler, Aurora', '09171234567'
FROM employees e WHERE e.employee_number LIKE 'LOAD-%'
  AND NOT EXISTS (SELECT 1 FROM pds_character_references p WHERE p.employee_id = e.id);

INSERT INTO pds_completion_status (employee_id, section, is_complete, updated_at)
SELECT e.id, sections.section, 1, NOW()
FROM employees e CROSS JOIN (
    SELECT 'personal_info' section UNION ALL SELECT 'addresses' UNION ALL SELECT 'family_background'
    UNION ALL SELECT 'children' UNION ALL SELECT 'educational_background'
    UNION ALL SELECT 'civil_service_eligibility' UNION ALL SELECT 'work_experience'
    UNION ALL SELECT 'voluntary_work' UNION ALL SELECT 'learning_development'
    UNION ALL SELECT 'other_info' UNION ALL SELECT 'non_academic_distinctions'
    UNION ALL SELECT 'memberships' UNION ALL SELECT 'questionnaire' UNION ALL SELECT 'character_references'
) sections
WHERE e.employee_number LIKE 'LOAD-%'
ON DUPLICATE KEY UPDATE is_complete = 1, updated_at = NOW();
