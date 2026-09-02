<?php
// Employee 201-file controller: list + profile view (with photo upload).

class EmployeeController extends Controller
{
    private Employee $employeeModel;
    private Pds $pdsModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->pdsModel = new Pds();
    }

    private function requirePositionActionPassword(): bool
    {
        $password = (string) ($_POST['confirmation_password'] ?? '');
        $stmt = Database::getInstance()->prepare('SELECT password_hash FROM users WHERE id=? AND status="active"');
        $stmt->execute([Auth::userId()]);
        $hash = $stmt->fetchColumn();
        if ($password === '' || !$hash || !password_verify($password, $hash)) {
            AuditLogger::log('approval_password_failed', 'personnel_movements', null);
            $this->json(['success'=>false,'error'=>'Incorrect password. No position or personnel record was changed.'],403);
            return false;
        }
        return true;
    }

    public function index(): void
    {
        Auth::requirePermission('employee.view');
        $validStatuses = ['Regular', 'Casual', 'Contractual', 'Job Order', 'Probationary'];
        $filters = [
            'q' => trim(Validator::sanitizeString($_GET['q'] ?? '')),
            'personnel_type' => in_array($_GET['personnel_type'] ?? '', ['Teaching', 'Non-Teaching'], true) ? $_GET['personnel_type'] : '',
            'employment_status' => in_array($_GET['employment_status'] ?? '', $validStatuses, true) ? $_GET['employment_status'] : '',
            'department_id' => max(0, (int) ($_GET['department_id'] ?? 0)),
            'position_id' => max(0, (int) ($_GET['position_id'] ?? 0)),
            'district' => trim(Validator::sanitizeString($_GET['district'] ?? '')),
        ];
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $this->employeeModel->countVisible($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $employees = $this->employeeModel->listWithDetails($perPage, ($page - 1) * $perPage, $filters);
        $pdo = Database::getInstance();

        $this->view('employees', 'index', [
            'pageTitle' => 'Employees (201 File)',
            'employees' => $employees,
            'canManage' => Auth::can('employee.manage'),
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'filters' => $filters,
            'departments' => $pdo->query('SELECT id,name FROM departments ORDER BY name')->fetchAll(),
            'positions' => $pdo->query('SELECT id,title FROM positions ORDER BY title')->fetchAll(),
            'districts' => $pdo->query('SELECT DISTINCT district FROM employee_work_profiles WHERE district IS NOT NULL AND TRIM(district)<>"" ORDER BY district')->fetchAll(PDO::FETCH_COLUMN),
            'statuses' => $validStatuses,
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('employee.manage');

        $pdo = Database::getInstance();
        $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $positions = $pdo->query('SELECT id, title FROM positions ORDER BY title')->fetchAll();
        $roles = $pdo->query("SELECT id, name FROM roles WHERE name <> 'Developer' ORDER BY name")->fetchAll();
        if (Auth::isDeveloper()) {
            $roles = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
        }

        $this->view('employees', 'create', [
            'pageTitle' => 'Add Employee',
            'departments' => $departments,
            'positions' => $positions,
            'roles' => $roles,
            'statuses' => ['Regular', 'Casual', 'Contractual', 'Job Order', 'Probationary'],
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('employee.manage');
        $this->requireCsrf();

        $validator = new Validator($_POST);
        $validator->required('employee_number', 'Employee number')->maxLength('employee_number', 30)
            ->required('username', 'Username')->maxLength('username', 60)
            ->required('email', 'Email')->email('email')
            ->required('password', 'Password')
            ->in('employment_status', ['Regular', 'Casual', 'Contractual', 'Job Order', 'Probationary'])
            ->date('date_hired');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            return;
        }

        $employeeNumber = Validator::sanitizeString($_POST['employee_number']);
        if ($this->employeeModel->where('employee_number', $employeeNumber)) {
            $this->json(['success' => false, 'error' => 'That employee number is already in use.'], 422);
            return;
        }

        if (mb_strlen((string) $_POST['password']) < 8) {
            $this->json(['success' => false, 'error' => 'Password must be at least 8 characters.'], 422);
            return;
        }

        $pdo = Database::getInstance();
        $username = Validator::sanitizeString($_POST['username']);
        $email = Validator::sanitizeString($_POST['email']);
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $existingUser = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $existingUser->execute([$username, $email]);
        if ($existingUser->fetchColumn()) {
            $this->json(['success' => false, 'error' => 'That username or email is already in use.'], 422);
            return;
        }

        $roleExists = $pdo->prepare('SELECT id, name FROM roles WHERE id = ?');
        $roleExists->execute([$roleId]);
        $selectedRole = $roleExists->fetch();
        if (!$selectedRole) {
            $this->json(['success' => false, 'error' => 'Please select a valid role.'], 422);
            return;
        }
        if ($selectedRole['name'] === ROLE_DEVELOPER && !Auth::isDeveloper()) {
            $this->json(['success' => false, 'error' => 'Only a Developer can create another Developer account.'], 403);
            return;
        }
        $districts = ['BALER','CASIGURAN','DILASAG','DINALUNGAN','DINGALAN','DIPACULAO NORTH','DIPACULAO SOUTH','MARIA AURORA EAST','MARIA AURORA WEST','SAN LUIS'];
        $scopeDistrict = null;
        $scopeDepartmentId = null;
        $scopeSchoolId = null;
        if (in_array($selectedRole['name'], ['PSDS', 'SDC'], true)) {
            $scopeDistrict = strtoupper(trim((string) ($_POST['scope_district'] ?? '')));
            if (!in_array($scopeDistrict, $districts, true)) {
                $this->json(['success' => false, 'error' => 'Please select a valid assigned district.'], 422);
                return;
            }
        } elseif ($selectedRole['name'] === ROLE_UNIT_HEAD) {
            $scopeDepartmentId = (int) ($_POST['scope_department_id'] ?? 0);
            $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
            $departmentCheck->execute([$scopeDepartmentId]);
            if (!$departmentCheck->fetchColumn()) {
                $this->json(['success' => false, 'error' => 'Please select a valid assigned office.'], 422);
                return;
            }
        } elseif ($selectedRole['name'] === 'Principal') {
            $scopeSchoolId = trim((string) ($_POST['scope_school_id_code'] ?? ''));
            $directoryPath = BASE_PATH . '/public/assets/data/deped-schools.json';
            $schoolDirectory = is_file($directoryPath) ? json_decode((string) file_get_contents($directoryPath), true) : [];
            $validSchool = array_filter(is_array($schoolDirectory) ? $schoolDirectory : [], static fn(array $school): bool => (string) ($school['i'] ?? '') === $scopeSchoolId);
            if ($scopeSchoolId === '' || !$validSchool) {
                $this->json(['success' => false, 'error' => 'Please search and select a valid assigned school for the Principal.'], 422);
                return;
            }
        }

        try {
            $pdo->beginTransaction();
            $employeeId = $this->employeeModel->insert([
                'employee_number' => $employeeNumber,
                'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
                'position_id' => !empty($_POST['position_id']) ? (int) $_POST['position_id'] : null,
                'date_hired' => !empty($_POST['date_hired']) ? $_POST['date_hired'] : null,
                'employment_status' => $_POST['employment_status'] ?? 'Probationary',
            ]);

            $account = $pdo->prepare(
                'INSERT INTO users (employee_id, username, email, password_hash, role_id, scope_district, scope_department_id, scope_school_id_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "active")'
            );
            $account->execute([$employeeId, $username, $email, password_hash($_POST['password'], PASSWORD_BCRYPT), $roleId, $scopeDistrict, $scopeDepartmentId, $scopeSchoolId]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['success' => false, 'error' => 'Unable to create the employee account. Please try again.'], 500);
            return;
        }

        AuditLogger::log('create', 'employees', $employeeId);
        AuditLogger::log('create', 'users', $userId);
        $this->json(['success' => true, 'message' => 'Employee and account created.', 'employee_token' => UrlId::encode($employeeId)]);
    }

    public function show(string $id): void
    {
        Auth::requirePermission('employee.view');
        if (!$this->employeeModel->isVisibleToCurrentUser((int) $id)) {
            http_response_code(403);
            echo '403 Forbidden: this employee is outside your assigned district or office.';
            return;
        }
        $employee = $this->employeeModel->findWithDetails((int) $id);
        if (!$employee) {
            http_response_code(404);
            echo 'Employee not found.';
            return;
        }
        $photo = $this->employeeModel->latestPhoto((int) $id);
        $pdsPercent = $this->pdsModel->completionPercent((int) $id);
        $snapshot = $this->employeeModel->profileSnapshot((int) $id);
        $relatedSummary = $this->employeeModel->relatedSummary((int) $id);
        $recentRecords = $this->employeeModel->recentRelatedRecords((int) $id);

        $educationStmt = Database::getInstance()->prepare(
            "SELECT level, school_name, degree_course FROM pds_educational_background
             WHERE employee_id = ? ORDER BY FIELD(level, 'Graduate Studies','College','Vocational','Secondary','Elementary') LIMIT 1"
        );
        $educationStmt->execute([(int) $id]);
        $eligibilityStmt = Database::getInstance()->prepare(
            'SELECT eligibility_name FROM pds_civil_service_eligibility WHERE employee_id = ? ORDER BY id LIMIT 3'
        );
        $eligibilityStmt->execute([(int) $id]);
        $movementStmt = Database::getInstance()->prepare('SELECT pm.*, u.username AS processed_by_name FROM personnel_movements pm LEFT JOIN users u ON u.id = pm.processed_by WHERE pm.employee_id = ? ORDER BY pm.effective_date DESC, pm.id DESC');
        $movementStmt->execute([(int) $id]);
        $positions = Auth::can('employee.manage') ? Database::getInstance()->query('SELECT id, title, salary_grade FROM positions ORDER BY title')->fetchAll() : [];

        $this->view('employees', 'show', [
            'pageTitle' => 'Employee Profile',
            'employee' => $employee,
            'photo' => $photo,
            'pdsPercent' => $pdsPercent,
            'snapshot' => $snapshot,
            'relatedSummary' => $relatedSummary,
            'recentRecords' => $recentRecords,
            'highestEducation' => $educationStmt->fetch() ?: null,
            'eligibilities' => $eligibilityStmt->fetchAll(),
            'movementHistory' => $movementStmt->fetchAll(),
            'positions' => $positions,
            'canManage' => Auth::can('employee.manage'),
        ]);
    }

    public function recordMovement(string $id): void
    {
        Auth::requirePermission('employee.manage');
        $this->requireCsrf();
        if (!$this->requirePositionActionPassword()) return;
        $employeeId = (int) $id;
        $employee = $this->employeeModel->findWithDetails($employeeId);
        $snapshot = $this->employeeModel->profileSnapshot($employeeId);
        if (!$employee) { $this->json(['success' => false, 'error' => 'Employee not found.'], 404); return; }
        $type = (string) ($_POST['movement_type'] ?? '');
        $effectiveDate = (string) ($_POST['effective_date'] ?? '');
        if (!in_array($type, ['School Transfer', 'Promotion', 'Historical Appointment'], true) || !DateTimeImmutable::createFromFormat('Y-m-d', $effectiveDate)) {
            $this->json(['success' => false, 'error' => 'Select a valid movement type and effective date.'], 422); return;
        }
        if ($type === 'School Transfer' && ($snapshot['personnel_type'] ?? '') !== 'Teaching') {
            $this->json(['success' => false, 'error' => 'School transfer is only available for Teaching personnel.'], 422); return;
        }
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            if ($type === 'Historical Appointment') {
                $historicalPositionId = (int) ($_POST['historical_position_id'] ?? 0);
                $positionStmt = $pdo->prepare('SELECT id,title,salary_grade FROM positions WHERE id=?'); $positionStmt->execute([$historicalPositionId]); $historicalPosition = $positionStmt->fetch();
                if (!$historicalPosition) throw new InvalidArgumentException('Select the employee’s previous position.');
                $endDate = trim((string) ($_POST['historical_end_date'] ?? ''));
                if ($endDate !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', $endDate)) throw new InvalidArgumentException('Enter a valid end date.');
                if ($endDate !== '' && $endDate < $effectiveDate) throw new InvalidArgumentException('The end date cannot be earlier than the start date.');
                $previous = [];
                $new = ['position_id'=>$historicalPositionId,'position_title'=>$historicalPosition['title'],'item_number'=>trim((string)($_POST['historical_item_number']??'')) ?: null,'salary_grade'=>trim((string)($_POST['historical_salary_grade']??$historicalPosition['salary_grade']??'')) ?: null,'station'=>trim((string)($_POST['historical_station']??'')) ?: null,'end_date'=>$endDate ?: null];
                if (($_POST['historical_mark_vacant'] ?? '0') === '1') {
                    $pdo->prepare('INSERT INTO vacant_positions (former_employee_id,position_id,item_number,salary_grade,department_id,station,vacated_on,reason) VALUES (?,?,?,?,?,?,?,"Historical appointment ended")')->execute([$employeeId,$historicalPositionId,$new['item_number'],$new['salary_grade'],$employee['department_id'],$new['station'],$endDate ?: $effectiveDate]);
                }
            } elseif ($type === 'School Transfer') {
                $new = [
                    'school_id_code' => trim((string) ($_POST['school_id_code'] ?? '')),
                    'district' => trim((string) ($_POST['district'] ?? '')),
                    'plantilla_school_station' => trim((string) ($_POST['plantilla_school_station'] ?? '')),
                    'current_school_station' => trim((string) ($_POST['current_school_station'] ?? '')),
                ];
                if ($new['school_id_code'] === '' || $new['current_school_station'] === '') throw new InvalidArgumentException('Select a valid destination school.');
                $previous = array_intersect_key($snapshot, array_flip(['school_id_code','district','plantilla_school_station','current_school_station','grade_levels_taught','specialization','subjects_taught']));
                $new['grade_levels_taught'] = $snapshot['grade_levels_taught'] ?? null;
                $new['specialization'] = $snapshot['specialization'] ?? null;
                $new['subjects_taught'] = $snapshot['subjects_taught'] ?? null;
                $pdo->prepare('UPDATE employee_work_profiles SET school_id_code=?, district=?, plantilla_school_station=?, current_school_station=? WHERE employee_id=?')->execute([$new['school_id_code'],$new['district'],$new['plantilla_school_station'],$new['current_school_station'],$employeeId]);
            } else {
                $newPositionId = (int) ($_POST['position_id'] ?? 0);
                $positionStmt = $pdo->prepare('SELECT id, title, salary_grade FROM positions WHERE id = ?'); $positionStmt->execute([$newPositionId]); $newPosition = $positionStmt->fetch();
                if (!$newPosition) throw new InvalidArgumentException('Select a valid new position.');
                $new = ['position_id' => $newPositionId, 'position_title' => $newPosition['title'], 'item_number' => trim((string) ($_POST['item_number'] ?? '')), 'salary_grade' => trim((string) ($_POST['salary_grade'] ?? $newPosition['salary_grade'] ?? ''))];
                $previous = ['position_id' => $employee['position_id'], 'position_title' => $employee['position_title'], 'item_number' => $snapshot['item_number'] ?? null, 'salary_grade' => $snapshot['salary_grade'] ?? null];
                if (!empty($previous['item_number']) || !empty($previous['position_id'])) {
                    $pdo->prepare('INSERT INTO vacant_positions (former_employee_id,position_id,item_number,salary_grade,department_id,school_id_code,station,vacated_on,reason) VALUES (?,?,?,?,?,?,?,?,"Promotion")')->execute([$employeeId,$previous['position_id'],$previous['item_number'],$previous['salary_grade'],$employee['department_id'],$snapshot['school_id_code'] ?? null,$snapshot['current_school_station'] ?? null,$effectiveDate]);
                }
                $pdo->prepare('UPDATE employees SET position_id=? WHERE id=?')->execute([$newPositionId,$employeeId]);
                $pdo->prepare('UPDATE employee_work_profiles SET item_number=?, salary_grade=? WHERE employee_id=?')->execute([$new['item_number'] ?: null,$new['salary_grade'] ?: null,$employeeId]);
            }
            $pdo->prepare('INSERT INTO personnel_movements (employee_id,movement_type,effective_date,previous_data,new_data,remarks,processed_by) VALUES (?,?,?,?,?,?,?)')->execute([$employeeId,$type,$effectiveDate,json_encode($previous),json_encode($new),trim((string) ($_POST['remarks'] ?? '')) ?: null,Auth::userId()]);
            $pdo->commit();
            Notification::permission('employee.manage', $type . ' recorded for employee ' . $employee['employee_number'] . ($type === 'Promotion' ? '. A previous position is now listed as vacant.' : '.'), BASE_URL . (($type === 'Promotion' || ($_POST['historical_mark_vacant'] ?? '0') === '1') ? '/vacant-positions' : '/employees/' . UrlId::encode($employeeId)));
            AuditLogger::log('create', 'personnel_movements', $employeeId, $previous, $new);
            $this->json(['success' => true, 'message' => $type . ' saved. Previous assignment details were preserved.']);
        } catch (InvalidArgumentException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack(); $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack(); $this->json(['success' => false, 'error' => 'Unable to save personnel movement.'], 500);
        }
    }

    public function vacancies(): void
    {
        Auth::requirePermission('employee.manage');
        $pdo = Database::getInstance();
        $filters = ['q'=>trim(Validator::sanitizeString($_GET['q']??'')),'status'=>in_array(($_GET['status']??''),['Vacant','Filled','Cancelled'],true)?$_GET['status']:'','department_id'=>(int)($_GET['department_id']??0)];
        $where=[];$params=[];
        if($filters['q']!==''){ $where[]='(p.title LIKE ? OR v.item_number LIKE ? OR e.employee_number LIKE ? OR CONCAT_WS(" ",pi.first_name,pi.middle_name,pi.surname) LIKE ? OR v.station LIKE ?)';$term='%'.$filters['q'].'%';array_push($params,$term,$term,$term,$term,$term); }
        if($filters['status']!==''){ $where[]='v.status=?';$params[]=$filters['status']; }
        if($filters['department_id']>0){ $where[]='v.department_id=?';$params[]=$filters['department_id']; }
        $sql='SELECT v.*, p.title AS position_title, d.name AS department_name, e.employee_number AS former_employee_number, COALESCE(NULLIF(TRIM(CONCAT_WS(" ",pi.first_name,pi.middle_name,pi.surname,pi.name_extension)),""),e.employee_number,"Not available") AS former_employee_name, holder.employee_number AS holder_employee_number, COALESCE(NULLIF(TRIM(CONCAT_WS(" ",holder_pi.first_name,holder_pi.middle_name,holder_pi.surname,holder_pi.name_extension)),""),holder.employee_number) AS holder_name FROM vacant_positions v LEFT JOIN positions p ON p.id=v.position_id LEFT JOIN departments d ON d.id=v.department_id LEFT JOIN employees e ON e.id=v.former_employee_id LEFT JOIN pds_personal_info pi ON pi.employee_id=e.id LEFT JOIN employees holder ON holder.id=v.filled_by_employee_id LEFT JOIN pds_personal_info holder_pi ON holder_pi.employee_id=holder.id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY FIELD(v.status,"Vacant","Filled","Cancelled"), v.vacated_on DESC';
        $vacancyStmt=$pdo->prepare($sql);$vacancyStmt->execute($params);$rows=$vacancyStmt->fetchAll();
        $departments=$pdo->query('SELECT id,name FROM departments ORDER BY name')->fetchAll();
        $this->view('employees', 'vacancies', ['pageTitle' => 'Vacant Positions', 'vacancies' => $rows, 'departments'=>$departments, 'filters'=>$filters]);
    }

    public function vacancyEmployeeSearch(): void
    {
        Auth::requirePermission('employee.manage');
        $query = trim(Validator::sanitizeString($_GET['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            $this->json(['success' => true, 'employees' => []]);
        }

        $term = '%' . $query . '%';
        $stmt = Database::getInstance()->prepare(
            'SELECT e.id,e.employee_number,
                    COALESCE(NULLIF(TRIM(CONCAT_WS(" ",pi.first_name,pi.middle_name,pi.surname,pi.name_extension)),""),e.employee_number) employee_name,
                    COALESCE(p.title,"Unassigned") position_title
             FROM employees e
             LEFT JOIN pds_personal_info pi ON pi.employee_id=e.id
             LEFT JOIN positions p ON p.id=e.position_id
             WHERE e.employee_number LIKE ?
                OR pi.first_name LIKE ? OR pi.middle_name LIKE ? OR pi.surname LIKE ?
                OR p.title LIKE ?
             ORDER BY e.employee_number
             LIMIT 30'
        );
        $stmt->execute([$term,$term,$term,$term,$term]);
        $this->json(['success' => true, 'employees' => $stmt->fetchAll()]);
    }

    public function fillVacancy(string $id): void
    {
        Auth::requirePermission('employee.manage');
        $this->requireCsrf();
        if (!$this->requirePositionActionPassword()) return;
        $vacancyId = (int) $id;
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $effectiveDate = (string) ($_POST['effective_date'] ?? date('Y-m-d'));
        if ($employeeId <= 0 || !DateTimeImmutable::createFromFormat('Y-m-d', $effectiveDate)) { $this->json(['success'=>false,'error'=>'Select an employee and a valid effective date.'],422); return; }
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            $vacancyStmt = $pdo->prepare('SELECT * FROM vacant_positions WHERE id=? FOR UPDATE'); $vacancyStmt->execute([$vacancyId]); $vacancy = $vacancyStmt->fetch();
            if (!$vacancy || $vacancy['status'] !== 'Vacant') throw new InvalidArgumentException('This position is no longer vacant.');
            $employeeStmt = $pdo->prepare('SELECT e.*,p.title AS position_title,wp.item_number,wp.salary_grade,wp.school_id_code,wp.current_school_station FROM employees e LEFT JOIN positions p ON p.id=e.position_id LEFT JOIN employee_work_profiles wp ON wp.employee_id=e.id WHERE e.id=?'); $employeeStmt->execute([$employeeId]); $employee = $employeeStmt->fetch();
            if (!$employee) throw new InvalidArgumentException('Select a valid employee.');
            $previous = ['position_id'=>$employee['position_id'],'position_title'=>$employee['position_title'],'item_number'=>$employee['item_number'],'salary_grade'=>$employee['salary_grade']];
            $new = ['position_id'=>$vacancy['position_id'],'item_number'=>$vacancy['item_number'],'salary_grade'=>$vacancy['salary_grade']];
            if ((!empty($previous['item_number']) || !empty($previous['position_id'])) && ((int)$previous['position_id'] !== (int)$new['position_id'] || (string)$previous['item_number'] !== (string)$new['item_number'])) {
                $pdo->prepare('INSERT INTO vacant_positions (former_employee_id,position_id,item_number,salary_grade,department_id,school_id_code,station,vacated_on,reason) VALUES (?,?,?,?,?,?,?,?,"Appointment to another position")')->execute([$employeeId,$previous['position_id'],$previous['item_number'],$previous['salary_grade'],$employee['department_id'],$employee['school_id_code'],$employee['current_school_station'],$effectiveDate]);
            }
            $pdo->prepare('UPDATE employees SET position_id=?,department_id=COALESCE(?,department_id) WHERE id=?')->execute([$vacancy['position_id'],$vacancy['department_id'],$employeeId]);
            $pdo->prepare('INSERT INTO employee_work_profiles (employee_id,item_number,salary_grade) VALUES (?,?,?) ON DUPLICATE KEY UPDATE item_number=VALUES(item_number),salary_grade=VALUES(salary_grade)')->execute([$employeeId,$vacancy['item_number'],$vacancy['salary_grade']]);
            $pdo->prepare('UPDATE vacant_positions SET status="Filled",filled_by_employee_id=? WHERE id=?')->execute([$employeeId,$vacancyId]);
            $pdo->prepare('INSERT INTO personnel_movements (employee_id,movement_type,effective_date,previous_data,new_data,remarks,processed_by) VALUES (?,"Promotion",?,?,?,?,?)')->execute([$employeeId,$effectiveDate,json_encode($previous),json_encode($new),'Appointed to previously vacant position',Auth::userId()]);
            $pdo->commit();
            Notification::permission('employee.manage','Vacant position filled by employee '.$employee['employee_number'].'.',BASE_URL.'/vacant-positions');
            AuditLogger::log('fill','vacant_positions',$vacancyId,['status'=>'Vacant'],['status'=>'Filled','employee_id'=>$employeeId]);
            $this->json(['success'=>true,'message'=>'Position assigned successfully. The employee’s previous item was preserved and listed as vacant when applicable.']);
        } catch (InvalidArgumentException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $this->json(['success'=>false,'error'=>$e->getMessage()],422);
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $this->json(['success'=>false,'error'=>'Unable to fill the vacant position.'],500); }
    }

    public function uploadPhoto(string $id): void
    {
        Auth::requirePermission('employee.manage');
        $this->requireCsrf();

        $employeeId = (int) $id;
        if (!$this->employeeModel->find($employeeId)) {
            $this->json(['success' => false, 'error' => 'Employee not found.'], 404);
            return;
        }

        if (empty($_FILES['photo'])) {
            $this->json(['success' => false, 'error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $dir = UPLOADS_PATH . "/photos/{$employeeId}";
            $result = Uploader::handleImage($_FILES['photo'], $dir);
            $photoId = $this->employeeModel->savePhoto($employeeId, $result['file_path'], $result['thumbnail_path']);
            AuditLogger::log('create', 'employee_photos', $photoId, null, ['employee_id' => $employeeId]);
            $this->json(['success' => true, 'message' => 'Photo uploaded.']);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
