<?php

// CLI utility for generating and visually checking an employee's official PDS PDF.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/constants.php';
require_once BASE_PATH . '/vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    foreach ([CORE_PATH, CONFIG_PATH, MODULES_PATH . '/employees/models'] as $directory) {
        $file = $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$employeeId = max(1, (int) ($argv[1] ?? 1));
$output = $argv[2] ?? (BASE_PATH . '/output/pdf/CS_Form_212_PDS_preview.pdf');
$pds = new Pds();
$employeeModel = new Employee();
$employee = $employeeModel->findWithDetails($employeeId);
if (!$employee) {
    fwrite(STDERR, "Employee not found.\n");
    exit(1);
}

$data = [
    'employee' => $employee,
    'personalInfo' => $pds->getSingleRow('personal_info', $employeeId),
    'addresses' => $pds->getAddresses($employeeId),
    'familyBackground' => $pds->getSingleRow('family_background', $employeeId),
    'children' => $pds->getRows('children', $employeeId),
    'education' => $pds->getRows('educational_background', $employeeId),
    'eligibility' => $pds->getRows('civil_service_eligibility', $employeeId),
    'workExperience' => $pds->getRows('work_experience', $employeeId),
    'voluntaryWork' => $pds->getRows('voluntary_work', $employeeId),
    'learningDevelopment' => $pds->getRows('learning_development', $employeeId),
    'otherInfo' => $pds->getRows('other_info', $employeeId),
    'distinctions' => $pds->getRows('non_academic_distinctions', $employeeId),
    'memberships' => $pds->getRows('memberships', $employeeId),
    'questionnaire' => $pds->getSingleRow('questionnaire', $employeeId),
    'characterReferences' => $pds->getRows('character_references', $employeeId),
    'photo' => $employeeModel->latestPhoto($employeeId),
];

if (($argv[3] ?? '') === '--qa') {
    $data['eligibility'] = [[
        'eligibility_name' => 'CAREER SERVICE PROFESSIONAL', 'rating' => '88.50',
        'exam_date' => '2025-03-14', 'exam_place' => 'QUEZON CITY',
        'license_number' => 'LIC-123456', 'license_validity' => '2028-03-14',
    ]];
    $data['workExperience'] = [[
        'date_from' => '2020-01-01', 'date_to' => '2026-08-25', 'position_title' => 'MASTER TEACHER I',
        'department_agency' => 'DEPARTMENT OF EDUCATION', 'monthly_salary' => '45000',
        'salary_grade_step' => 'SG-18-1', 'appointment_status' => 'PERMANENT', 'is_government' => 1,
    ]];
    $data['learningDevelopment'] = [[
        'title' => 'SCHOOL LEADERSHIP AND MANAGEMENT TRAINING', 'date_from' => '2026-05-01',
        'date_to' => '2026-05-03', 'number_of_hours' => 24, 'type_of_ld' => 'Managerial',
        'conducted_by' => 'DEPARTMENT OF EDUCATION',
    ]];
    $data['voluntaryWork'] = [[
        'organization_name' => 'PHILIPPINE RED CROSS', 'organization_address' => 'AURORA CHAPTER',
        'date_from' => '2025-01-10', 'date_to' => '2025-12-10', 'number_of_hours' => 40,
        'position_nature_of_work' => 'COMMUNITY VOLUNTEER',
    ]];
    $data['otherInfo'] = [
        ['category' => 'Skill', 'description' => 'PUBLIC SPEAKING'],
        ['category' => 'Recognition', 'description' => 'OUTSTANDING EMPLOYEE'],
        ['category' => 'Membership', 'description' => 'TEACHERS ASSOCIATION'],
    ];
    $data['characterReferences'] = [[
        'full_name' => 'MARIA SANTOS', 'address' => 'BALER, AURORA', 'telephone_no' => '09171234567',
    ]];
}

$directory = dirname($output);
if (!is_dir($directory)) mkdir($directory, 0755, true);
(new PdsPdfGenerator())->render($data, 'F:' . $output);
echo $output . PHP_EOL;
