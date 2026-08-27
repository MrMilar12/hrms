<?php
// Onboarding controller: mandatory welcome screen shown before a new user's PDS is complete.

class OnboardingController extends Controller
{
    public function personnelSetup(?string $error = null): void
    {
        Auth::requireLogin();
        if (!Auth::needsPersonnelSetup()) {
            $this->redirect('/dashboard');
        }
        $this->view('onboarding', 'personnel_setup', ['error' => $error]);
    }

    public function savePersonnelSetup(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $type = $_POST['personnel_type'] ?? '';
        if (!in_array($type, ['Teaching', 'Non-Teaching'], true)) {
            $this->personnelSetup('Please choose Teaching or Non-Teaching.');
            return;
        }
        if ($type === 'Teaching') {
            foreach (['school_id_code' => 'School ID code', 'plantilla_school_station' => 'Plantilla school station', 'current_school_station' => 'Current school station'] as $field => $label) {
                if (trim((string) ($_POST[$field] ?? '')) === '') {
                    $this->personnelSetup($label . ' is required for teaching personnel.');
                    return;
                }
            }
        }

        $fields = ['school_id_code','plantilla_school_station','current_school_station','district','grade_levels_taught','specialization','subjects_taught'];
        $values = [];
        foreach ($fields as $field) $values[] = Validator::sanitizeString($_POST[$field] ?? '') ?: null;
        Database::getInstance()->prepare(
            'INSERT INTO employee_work_profiles (employee_id, personnel_type, school_id_code, plantilla_school_station, current_school_station, district, grade_levels_taught, specialization, subjects_taught)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE personnel_type = VALUES(personnel_type), school_id_code = VALUES(school_id_code), plantilla_school_station = VALUES(plantilla_school_station), current_school_station = VALUES(current_school_station), district = VALUES(district), grade_levels_taught = VALUES(grade_levels_taught), specialization = VALUES(specialization), subjects_taught = VALUES(subjects_taught)'
        )->execute([Auth::employeeId(), $type, ...$values]);
        Auth::completePersonnelSetup();
        AuditLogger::log('complete_personnel_setup', 'employee_work_profiles', Auth::employeeId(), null, ['personnel_type' => $type]);
        $this->redirect('/personal-details-setup');
    }

    public function personalDetailsSetup(?string $error = null, array $submitted = []): void
    {
        Auth::requireLogin();
        if (Auth::needsPersonnelSetup()) {
            $this->redirect('/personnel-setup');
        }
        if (!Auth::needsPersonalDetailsSetup()) {
            $this->redirect('/dashboard');
        }

        $employeeId = Auth::employeeId();
        $pds = new Pds();
        $personal = $pds->getSingleRow('personal_info', $employeeId) ?? [];
        $address = $pds->getAddresses($employeeId)['Residential'] ?? [];
        $stmt = Database::getInstance()->prepare('SELECT employee_number FROM employees WHERE id = ?');
        $stmt->execute([$employeeId]);
        $storedEmployeeNumber = (string) $stmt->fetchColumn();

        $this->view('onboarding', 'personal_details_setup', [
            'error' => $error,
            'values' => array_merge($personal, $address, $submitted),
            'employeeNumber' => (string) ($submitted['employee_number'] ?? $storedEmployeeNumber),
        ]);
    }

    public function savePersonalDetailsSetup(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        if (Auth::needsPersonnelSetup()) {
            $this->redirect('/personnel-setup');
        }

        $required = [
            'employee_number' => 'Employee number', 'first_name' => 'First name', 'surname' => 'Last name', 'birth_date' => 'Date of birth',
            'sex' => 'Gender', 'civil_status' => 'Civil status', 'mobile_no' => 'Contact number',
            'pwd_status' => 'PWD status', 'email' => 'Email address', 'house_block_lot' => 'House / lot / street address',
            'barangay' => 'Barangay', 'city_municipality' => 'City / municipality', 'province' => 'Province',
            'privacy_consent' => 'Data privacy consent',
        ];
        $validator = new Validator($_POST);
        foreach ($required as $field => $label) $validator->required($field, $label);
        $validator->maxLength('employee_number', 30)->date('birth_date')->email('email')
            ->in('sex', ['Male', 'Female'])
            ->in('civil_status', ['Single', 'Married', 'Widowed', 'Separated', 'Others'])
            ->in('pwd_status', ['0', '1'])
            ->in('privacy_consent', ['1']);

        $birthDate = (string) ($_POST['birth_date'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate) && $birthDate > date('Y-m-d')) {
            $this->personalDetailsSetup('Date of birth cannot be in the future. Please select your actual birth date.', $_POST);
            return;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate) && $birthDate < '1900-01-01') {
            $this->personalDetailsSetup('Date of birth must be January 1, 1900 or later.', $_POST);
            return;
        }

        if ($validator->fails()) {
            $messages = [];
            foreach ($validator->errors() as $errors) $messages[] = $errors[0];
            $this->personalDetailsSetup(implode(' ', $messages), $_POST);
            return;
        }

        $employeeNumber = Validator::sanitizeString($_POST['employee_number']);
        $duplicate = Database::getInstance()->prepare('SELECT id FROM employees WHERE employee_number = ? AND id <> ? LIMIT 1');
        $duplicate->execute([$employeeNumber, Auth::employeeId()]);
        if ($duplicate->fetchColumn()) {
            $this->personalDetailsSetup('That employee number is already assigned to another account.', $_POST);
            return;
        }

        $clean = fn(string $field) => Validator::sanitizeString($_POST[$field] ?? '') ?: null;
        $personal = [];
        foreach (['surname','first_name','middle_name','name_extension','birth_place','sex','civil_status','mobile_no','email'] as $field) {
            $personal[$field] = $clean($field);
        }
        $personal['birth_date'] = $_POST['birth_date'];
        $address = [];
        foreach (['house_block_lot','street','subdivision_village','barangay','city_municipality','province','zip_code'] as $field) {
            $address[$field] = $clean($field);
        }

        $pdo = Database::getInstance();
        $pds = new Pds();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE employees SET employee_number = ? WHERE id = ?')->execute([$employeeNumber, Auth::employeeId()]);
            $pds->saveSingleRow('personal_info', Auth::employeeId(), $personal);
            $pds->saveAddress(Auth::employeeId(), 'Residential', $address);
            $pds->saveSingleRow('questionnaire', Auth::employeeId(), ['q40_pwd' => (int) $_POST['pwd_status']]);
            $pds->markSectionComplete(Auth::employeeId(), 'personal_info', true);
            $pds->markSectionComplete(Auth::employeeId(), 'addresses', true);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        $_SESSION['display_name'] = trim(($personal['first_name'] ?? '') . ' ' . ($personal['surname'] ?? ''));
        Auth::completePersonalDetailsSetup();
        AuditLogger::log('complete_personal_details_setup', 'pds_personal_info', Auth::employeeId(), null, [
            'completed' => true,
            'employee_number' => $employeeNumber,
            'privacy_consent' => true,
            'privacy_notice' => 'RA 10173 onboarding consent v1',
        ]);
        $this->redirect('/onboarding');
    }

    public function index(): void
    {
        Auth::requireLogin();

        $percent = Auth::employeeId() ? (new Pds())->completionPercent(Auth::employeeId()) : 0;

        $this->view('onboarding', 'welcome', [
            'completionPercent' => $percent,
        ]);
    }
}
