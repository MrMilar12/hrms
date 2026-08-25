CREATE DATABASE IF NOT EXISTS hrms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrms;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','hr','employee') DEFAULT 'employee',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNIQUE,
  employee_no VARCHAR(50),
  last_name VARCHAR(100),
  first_name VARCHAR(100),
  middle_name VARCHAR(100),
  name_extension VARCHAR(20),
  birthdate DATE,
  place_of_birth VARCHAR(200),
  sex ENUM('Male','Female'),
  civil_status ENUM('Single','Married','Widowed','Separated','Others'),
  citizenship VARCHAR(100) DEFAULT 'Filipino',
  dual_citizenship TINYINT(1) DEFAULT 0,
  dual_citizenship_type VARCHAR(50),
  dual_country VARCHAR(100),
  height VARCHAR(10),
  weight VARCHAR(10),
  blood_type VARCHAR(5),
  gsis VARCHAR(50),
  pagibig VARCHAR(50),
  philhealth VARCHAR(50),
  sss VARCHAR(50),
  tin VARCHAR(50),
  agency_employee_no VARCHAR(50),
  telephone VARCHAR(50),
  mobile VARCHAR(50),
  email_address VARCHAR(100),
  residential_house VARCHAR(100),
  residential_street VARCHAR(100),
  residential_subdivision VARCHAR(100),
  residential_barangay VARCHAR(100),
  residential_city VARCHAR(100),
  residential_province VARCHAR(100),
  residential_zip VARCHAR(10),
  permanent_same TINYINT(1) DEFAULT 0,
  permanent_house VARCHAR(100),
  permanent_street VARCHAR(100),
  permanent_subdivision VARCHAR(100),
  permanent_barangay VARCHAR(100),
  permanent_city VARCHAR(100),
  permanent_province VARCHAR(100),
  permanent_zip VARCHAR(10),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS family_background (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNIQUE,
  spouse_surname VARCHAR(100),
  spouse_firstname VARCHAR(100),
  spouse_middlename VARCHAR(100),
  spouse_extension VARCHAR(20),
  spouse_occupation VARCHAR(100),
  spouse_employer VARCHAR(200),
  spouse_business_address TEXT,
  spouse_telephone VARCHAR(50),
  father_surname VARCHAR(100),
  father_firstname VARCHAR(100),
  father_middlename VARCHAR(100),
  father_extension VARCHAR(20),
  mother_surname VARCHAR(100),
  mother_firstname VARCHAR(100),
  mother_middlename VARCHAR(100),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS children (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  child_name VARCHAR(200),
  date_of_birth DATE,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS education (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  level ENUM('Elementary','Secondary','Vocational','College','Graduate Studies'),
  school VARCHAR(200),
  degree VARCHAR(150),
  from_year VARCHAR(4),
  to_year VARCHAR(4),
  units_earned VARCHAR(50),
  year_graduated VARCHAR(4),
  honors VARCHAR(200),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS eligibility (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  career_service VARCHAR(200),
  rating VARCHAR(20),
  date_of_exam DATE,
  place_of_exam VARCHAR(200),
  license_no VARCHAR(100),
  license_validity DATE,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS work_experience (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  start_date DATE,
  end_date DATE,
  is_present TINYINT(1) DEFAULT 0,
  position_title VARCHAR(150),
  department VARCHAR(200),
  monthly_salary VARCHAR(50),
  salary_grade VARCHAR(20),
  status_appointment VARCHAR(100),
  is_government ENUM('Y','N'),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS voluntary_work (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  organization VARCHAR(200),
  org_address TEXT,
  from_date DATE,
  to_date DATE,
  hours_count INT,
  position_nature VARCHAR(200),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS learning_development (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  title VARCHAR(250),
  from_date DATE,
  to_date DATE,
  hours_count INT,
  ld_type ENUM('Managerial','Supervisory','Technical','Foundation'),
  conducted_by VARCHAR(200),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS other_info (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNIQUE,
  special_skills TEXT,
  non_academic_distinctions TEXT,
  org_memberships TEXT,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pds_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNIQUE,
  q34a ENUM('Yes','No') DEFAULT 'No',
  q34a_details TEXT,
  q34b ENUM('Yes','No') DEFAULT 'No',
  q34b_details TEXT,
  q35a ENUM('Yes','No') DEFAULT 'No',
  q35a_details TEXT,
  q35b ENUM('Yes','No') DEFAULT 'No',
  q35b_details TEXT,
  q36 ENUM('Yes','No') DEFAULT 'No',
  q36_details TEXT,
  q37 ENUM('Yes','No') DEFAULT 'No',
  q37_details TEXT,
  q38a ENUM('Yes','No') DEFAULT 'No',
  q38a_details TEXT,
  q38b ENUM('Yes','No') DEFAULT 'No',
  q38b_details TEXT,
  q39 ENUM('Yes','No') DEFAULT 'No',
  q39_details TEXT,
  q40a ENUM('Yes','No') DEFAULT 'No',
  q40a_details TEXT,
  q40b ENUM('Yes','No') DEFAULT 'No',
  q40b_details TEXT,
  q40c ENUM('Yes','No') DEFAULT 'No',
  q40c_details TEXT,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS references_info (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  ref_name VARCHAR(200),
  ref_address TEXT,
  ref_tel VARCHAR(50),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pds_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNIQUE,
  is_submitted TINYINT(1) DEFAULT 0,
  submitted_at TIMESTAMP NULL,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
