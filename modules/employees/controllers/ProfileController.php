<?php

class ProfileController extends Controller
{
    private Pds $pdsModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->pdsModel = new Pds();
        $this->employeeModel = new Employee();
    }

    public function show(): void
    {
        Auth::requireLogin();
        $employeeId = Auth::employeeId();
        if (!$employeeId) {
            http_response_code(403);
            echo 'No employee record is linked to this account.';
            return;
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT u.username, u.email AS account_email, u.last_login, u.two_factor_enabled,
                    e.employee_number, e.position_id, e.date_hired, e.employment_status,
                    d.name AS department_name, p.title AS position_title
             FROM users u
             JOIN employees e ON e.id = u.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE u.id = ? AND e.id = ? LIMIT 1'
        );
        $stmt->execute([Auth::userId(), $employeeId]);
        $account = $stmt->fetch();

        $photoStmt = Database::getInstance()->prepare('SELECT id FROM employee_photos WHERE employee_id = ? ORDER BY uploaded_at DESC LIMIT 1');
        $photoStmt->execute([$employeeId]);
        $workStmt = Database::getInstance()->prepare('SELECT * FROM employee_work_profiles WHERE employee_id = ?');
        $workStmt->execute([$employeeId]);
        $educationStmt = Database::getInstance()->prepare("SELECT level, degree_course FROM pds_educational_background WHERE employee_id = ? ORDER BY FIELD(level, 'Graduate Studies','College','Vocational','Secondary','Elementary') LIMIT 1");
        $educationStmt->execute([$employeeId]);
        $eligibilityStmt = Database::getInstance()->prepare('SELECT eligibility_name FROM pds_civil_service_eligibility WHERE employee_id = ? ORDER BY id');
        $eligibilityStmt->execute([$employeeId]);

        $personalInfo = $this->pdsModel->getSingleRow('personal_info', $employeeId);
        $qrPayload = implode("\n", [
            'HRMS EMPLOYEE ID',
            'Employee No: ' . $account['employee_number'],
            'Name: ' . trim(($personalInfo['first_name'] ?? '') . ' ' . ($personalInfo['surname'] ?? '')),
            'Department: ' . ($account['department_name'] ?? 'Not assigned'),
            'Position: ' . ($account['position_title'] ?? 'Not assigned'),
        ]);
        $qrCode = new Endroid\QrCode\QrCode(
            data: $qrPayload,
            errorCorrectionLevel: Endroid\QrCode\ErrorCorrectionLevel::Medium,
            size: 260,
            margin: 8
        );
        $qrResult = (new Endroid\QrCode\Writer\PngWriter())->write($qrCode);
        $this->view('employees', 'profile', [
            'pageTitle' => 'My Profile',
            'account' => $account,
            'personalInfo' => $personalInfo,
            'photo' => $photoStmt->fetch() ?: null,
            'workProfile' => $workStmt->fetch() ?: ['personnel_type' => 'Non-Teaching'],
            'addresses' => $this->pdsModel->getAddresses($employeeId),
            'questionnaire' => $this->pdsModel->getSingleRow('questionnaire', $employeeId),
            'positions' => Database::getInstance()->query('SELECT id, title FROM positions ORDER BY title')->fetchAll(),
            'highestEducation' => $educationStmt->fetch() ?: null,
            'eligibilities' => $eligibilityStmt->fetchAll(PDO::FETCH_COLUMN),
            'pdsPercent' => $this->pdsModel->completionPercent($employeeId),
            'qrDataUri' => $qrResult->getDataUri(),
            'saved' => isset($_GET['saved']),
            'isUnlocked' => Auth::isRecordUnlocked('profile'),
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        if (!Auth::isRecordUnlocked('profile')) {
            $this->json(['success' => false, 'error' => 'Your profile is locked. Confirm your password before editing.'], 423);
        }
        $employeeId = Auth::employeeId();
        if (!$employeeId) {
            $this->json(['success' => false, 'error' => 'No employee record is linked to this account.'], 403);
        }

        $validator = new Validator($_POST);
        $validator->required('username', 'Username')->maxLength('username', 60)
            ->required('account_email', 'Account email')->email('account_email')
            ->required('first_name', 'First name')->maxLength('first_name', 100)
            ->required('surname', 'Surname')->maxLength('surname', 100)
            ->required('employee_number', 'Employee number')->maxLength('employee_number', 30)
            ->required('personnel_type', 'Personnel classification')
            ->in('personnel_type', ['Teaching', 'Non-Teaching'])
            ->in('employment_status', ['Regular', 'Casual', 'Contractual', 'Job Order', 'Probationary'])
            ->email('email')->date('birth_date')
            ->date('date_hired')
            ->in('sex', ['Male', 'Female'])->in('civil_status', ['Single', 'Married', 'Widowed', 'Separated', 'Others']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstFieldErrors = reset($errors);
            $this->json(['success' => false, 'error' => $firstFieldErrors[0] ?? 'Please check the form.'], 422);
        }
        $birthDateInput = trim((string) ($_POST['birth_date'] ?? ''));
        $dateHiredInput = trim((string) ($_POST['date_hired'] ?? ''));
        if ($dateHiredInput !== '' && $dateHiredInput > date('Y-m-d')) {
            $this->json(['success' => false, 'error' => 'Date hired cannot be in the future.'], 422);
        }
        if ($birthDateInput !== '' && $dateHiredInput !== '') {
            $minimumHireDate = (new DateTimeImmutable($birthDateInput))->modify('+15 years')->format('Y-m-d');
            if ($dateHiredInput < $minimumHireDate) {
                $this->json(['success' => false, 'error' => 'Date hired must be based on the employee appointment date, not the birth date.'], 422);
            }
        }

        $pdo = Database::getInstance();
        $username = Validator::sanitizeString($_POST['username']);
        $accountEmail = Validator::sanitizeString($_POST['account_email']);
        $duplicate = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
        $duplicate->execute([$username, $accountEmail, Auth::userId()]);
        if ($duplicate->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'That username or account email is already in use.'], 422);
        }

        $employeeNumber = Validator::sanitizeString($_POST['employee_number']);
        $duplicateEmployee = $pdo->prepare('SELECT id FROM employees WHERE employee_number = ? AND id <> ? LIMIT 1');
        $duplicateEmployee->execute([$employeeNumber, $employeeId]);
        if ($duplicateEmployee->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'That employee number is already in use.'], 422);
        }
        if (!empty($_POST['position_id'])) {
            $positionStmt = $pdo->prepare('SELECT id FROM positions WHERE id = ?');
            $positionStmt->execute([(int) $_POST['position_id']]);
            if (!$positionStmt->fetchColumn()) {
                $this->json(['success' => false, 'error' => 'Select a valid position or designation.'], 422);
            }
        }

        $personnelType = $_POST['personnel_type'] ?? '';
        if ($personnelType === 'Teaching') {
            foreach (['school_id_code' => 'School ID code', 'plantilla_school_station' => 'Plantilla school station', 'current_school_station' => 'Current school station'] as $field => $label) {
                if (trim((string) ($_POST[$field] ?? '')) === '') {
                    $this->json(['success' => false, 'error' => $label . ' is required for teaching personnel.'], 422);
                }
            }
        }

        $personal = [];
        foreach (['surname','first_name','middle_name','name_extension','birth_date','birth_place','sex','civil_status','citizenship','telephone_no','mobile_no','email'] as $field) {
            $personal[$field] = Validator::sanitizeString($_POST[$field] ?? '');
            if ($personal[$field] === '') {
                $personal[$field] = null;
            }
        }
        $personal['surname'] = Validator::sanitizeString($_POST['surname']);
        $personal['first_name'] = Validator::sanitizeString($_POST['first_name']);

        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?')->execute([$username, $accountEmail, Auth::userId()]);
            $pdo->prepare('UPDATE employees SET employee_number = ?, position_id = ?, employment_status = ?, date_hired = ? WHERE id = ?')->execute([
                $employeeNumber,
                !empty($_POST['position_id']) ? (int) $_POST['position_id'] : null,
                $_POST['employment_status'] ?: 'Probationary',
                !empty($_POST['date_hired']) ? $_POST['date_hired'] : null,
                $employeeId,
            ]);
            $this->pdsModel->saveSingleRow('personal_info', $employeeId, $personal);
            $residential = $_POST['residential'] ?? [];
            foreach (['house_block_lot','street','subdivision_village','barangay','city_municipality','province','zip_code'] as $field) {
                $residential[$field] = Validator::sanitizeString($residential[$field] ?? '') ?: null;
            }
            $this->pdsModel->saveAddress($employeeId, 'Residential', $residential);
            $this->pdsModel->saveSingleRow('questionnaire', $employeeId, ['q40_pwd' => ($_POST['pwd_status'] ?? '0') === '1' ? 1 : 0]);
            $workFields = ['school_id_code','item_number','salary_grade','plantilla_school_station','current_school_station','district','grade_levels_taught','specialization','subjects_taught'];
            $workValues = [];
            foreach ($workFields as $field) $workValues[$field] = Validator::sanitizeString($_POST[$field] ?? '') ?: null;
            $pdo->prepare(
                'INSERT INTO employee_work_profiles (employee_id, personnel_type, school_id_code, item_number, salary_grade, plantilla_school_station, current_school_station, district, grade_levels_taught, specialization, subjects_taught)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE personnel_type = VALUES(personnel_type), school_id_code = VALUES(school_id_code), item_number = VALUES(item_number), salary_grade = VALUES(salary_grade), plantilla_school_station = VALUES(plantilla_school_station), current_school_station = VALUES(current_school_station), district = VALUES(district), grade_levels_taught = VALUES(grade_levels_taught), specialization = VALUES(specialization), subjects_taught = VALUES(subjects_taught)'
            )->execute([$employeeId, $personnelType, ...array_values($workValues)]);
            $pdo->commit();

            $_SESSION['username'] = $username;
            $_SESSION['display_name'] = trim($personal['first_name'] . ' ' . $personal['surname']);
            AuditLogger::log('update', 'profile', $employeeId, null, ['changed_fields' => array_values(array_diff(array_keys($_POST), ['csrf_token']))]);
            $this->json(['success' => true, 'message' => 'Profile updated.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['success' => false, 'error' => 'Unable to update your profile.'], 500);
        }
    }

    public function uploadPhoto(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        if (!Auth::isRecordUnlocked('profile')) {
            $this->json(['success' => false, 'error' => 'Your profile is locked. Confirm your password before changing the photo.'], 423);
        }
        $employeeId = Auth::employeeId();
        if (!$employeeId) {
            $this->json(['success' => false, 'error' => 'No employee record is linked to this account.'], 403);
        }
        if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->json(['success' => false, 'error' => 'Choose a profile picture to upload.'], 400);
        }

        try {
            $directory = UPLOADS_PATH . "/photos/{$employeeId}";
            $result = Uploader::handleImage($_FILES['photo'], $directory);
            $photoId = $this->employeeModel->savePhoto($employeeId, $result['file_path'], $result['thumbnail_path']);
            AuditLogger::log('upload_photo', 'employee_photos', $photoId);
            $this->json([
                'success' => true,
                'message' => 'Profile picture updated.',
                'photo_url' => BASE_URL . '/photo/' . UrlId::encode($photoId),
            ]);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function security(?string $error = null): void
    {
        Auth::requireLogin();
        $stmt = Database::getInstance()->prepare('SELECT username, email, password_hash, two_factor_enabled FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::userId()]);
        $account = $stmt->fetch();
        if (!$account) {
            http_response_code(404);
            echo 'Account not found.';
            return;
        }

        $qrDataUri = null;
        $secret = null;
        if (!(bool) $account['two_factor_enabled']) {
            if (empty($_SESSION['pending_2fa_secret'])) {
                $_SESSION['pending_2fa_secret'] = TwoFactor::generateSecret();
            }
            $secret = $_SESSION['pending_2fa_secret'];
            $uri = TwoFactor::provisioningUri($secret, $account['email'] ?: $account['username'], 'Project PUNLA HRMS');
            $qrCode = new Endroid\QrCode\QrCode(
                data: $uri,
                errorCorrectionLevel: Endroid\QrCode\ErrorCorrectionLevel::Medium,
                size: 280,
                margin: 10
            );
            $qrDataUri = (new Endroid\QrCode\Writer\PngWriter())->write($qrCode)->getDataUri();
        } else {
            unset($_SESSION['pending_2fa_secret']);
        }

        $this->view('employees', 'security', [
            'pageTitle' => 'Account Security',
            'account' => $account,
            'secret' => $secret,
            'qrDataUri' => $qrDataUri,
            'error' => $error,
            'enabledMessage' => isset($_GET['enabled']),
            'disabledMessage' => isset($_GET['disabled']),
        ]);
    }

    public function enableTwoFactor(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $password = (string) ($_POST['password'] ?? '');
        $code = (string) ($_POST['code'] ?? '');
        $secret = (string) ($_SESSION['pending_2fa_secret'] ?? '');

        $stmt = Database::getInstance()->prepare('SELECT password_hash, two_factor_enabled FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::userId()]);
        $account = $stmt->fetch();
        if (!$account || !password_verify($password, $account['password_hash'])) {
            $this->security('Your current password is incorrect.');
            return;
        }
        if ((bool) $account['two_factor_enabled']) {
            $this->redirect('/profile/security');
        }
        if ($secret === '' || !TwoFactor::verify($secret, $code)) {
            $this->security('The authenticator code is invalid or expired. Try the newest code.');
            return;
        }

        Database::getInstance()->prepare('UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?')->execute([$secret, Auth::userId()]);
        unset($_SESSION['pending_2fa_secret']);
        AuditLogger::log('enable_2fa', 'users', Auth::userId(), null, ['two_factor_enabled' => true]);
        $this->redirect('/profile/security?enabled=1');
    }

    public function disableTwoFactor(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $password = (string) ($_POST['password'] ?? '');
        $stmt = Database::getInstance()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::userId()]);
        $passwordHash = $stmt->fetchColumn();
        if (!$passwordHash || !password_verify($password, $passwordHash)) {
            $this->security('Your current password is incorrect. Two-factor authentication remains enabled.');
            return;
        }

        Database::getInstance()->prepare('UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?')->execute([Auth::userId()]);
        unset($_SESSION['pending_2fa_secret']);
        AuditLogger::log('disable_2fa', 'users', Auth::userId(), null, ['two_factor_enabled' => false]);
        $this->redirect('/profile/security?disabled=1');
    }
}
