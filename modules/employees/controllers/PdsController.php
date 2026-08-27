<?php
// PDS (CS Form 212) controller: tabbed AJAX section editing + completion tracking.

class PdsController extends Controller
{
    private Pds $pdsModel;
    private Employee $employeeModel;

    private const SINGLE_SECTIONS = ['personal_info', 'family_background', 'questionnaire'];
    private const REPEATING_SECTIONS = [
        'children', 'educational_background', 'civil_service_eligibility', 'work_experience',
        'voluntary_work', 'learning_development', 'other_info', 'non_academic_distinctions',
        'memberships', 'character_references',
    ];

    private const FIELD_LABELS = [
        'surname' => 'Surname', 'first_name' => 'First name', 'birth_date' => 'Birth date',
        'height_m' => 'Height', 'weight_kg' => 'Weight', 'email' => 'Email address',
        'province' => 'Province', 'city_municipality' => 'City/Municipality', 'barangay' => 'Barangay',
        'zip_code' => 'ZIP code', 'period_from' => 'Period from', 'period_to' => 'Period to',
        'date_from' => 'Date from', 'date_to' => 'Date to', 'exam_date' => 'Examination date',
        'monthly_salary' => 'Monthly salary', 'number_of_hours' => 'Number of hours',
    ];

    private function saveErrorDetails(Throwable $error): array
    {
        $message = $error->getMessage();
        $field = null;
        if (preg_match("/column ['`]([^'`]+)['`]/i", $message, $match)) $field = $match[1];
        if (!$field && stripos($message, 'height') !== false) $field = 'height_m';
        $label = self::FIELD_LABELS[$field] ?? ($field ? ucwords(str_replace('_', ' ', $field)) : 'Current section');

        if (stripos($message, 'out of range') !== false) {
            $suggestion = $field === 'height_m'
                ? 'Enter height as 1.65 meters or 165 centimeters.'
                : 'Enter a smaller numeric value and check that the unit is correct.';
            $reason = 'The entered number is outside the allowed range.';
        } elseif (stripos($message, 'data too long') !== false) {
            $reason = 'The entered text is longer than this field allows.';
            $suggestion = 'Shorten the value, remove unnecessary spaces, and try again.';
        } elseif (stripos($message, 'date') !== false) {
            $reason = 'The date is missing or has an invalid format.';
            $suggestion = 'Choose a valid date using the date picker.';
        } elseif (stripos($message, 'decimal') !== false || stripos($message, 'integer') !== false) {
            $reason = 'This field requires a numeric value.';
            $suggestion = 'Use numbers only; remove letters, commas, and unit labels.';
        } elseif (stripos($message, 'cannot be null') !== false) {
            $reason = 'A required field was left blank.';
            $suggestion = 'Complete the highlighted field before saving.';
        } else {
            $reason = $error instanceof InvalidArgumentException ? $message : 'The value could not be stored in the required format.';
            $suggestion = 'Review the highlighted field and use the example or choices shown in the form.';
        }

        return ['field' => $field, 'fieldLabel' => $label, 'error' => $reason, 'suggestion' => $suggestion];
    }

    public function __construct()
    {
        $this->pdsModel = new Pds();
        $this->employeeModel = new Employee();
    }

    /** Resolves which employee's PDS is being worked on, enforcing self-service vs HR access. */
    private function resolveEmployeeId(): ?int
    {
        $requestedToken = $this->input('employee_id');
        $requested = $requestedToken !== null ? UrlId::decode((string) $requestedToken) : null;
        if ($requestedToken !== null && $requested === null) return null;
        $own = Auth::employeeId();

        if ($requested !== null && (int) $requested !== $own) {
            if (!Auth::can('pds.view_all')) {
                return null;
            }
            return (int) $requested;
        }

        return $own;
    }

    public function edit(): void
    {
        Auth::requireLogin();
        $employeeId = $this->resolveEmployeeId();
        if (!$employeeId) {
            http_response_code(403);
            echo 'No employee record linked to this account.';
            return;
        }

        $data = [
            'pageTitle' => 'Personal Data Sheet (CS Form 212)',
            'employeeId' => $employeeId,
            'personalInfo' => $this->pdsModel->getSingleRow('personal_info', $employeeId),
            'addresses' => $this->pdsModel->getAddresses($employeeId),
            'familyBackground' => $this->pdsModel->getSingleRow('family_background', $employeeId),
            'children' => $this->pdsModel->getRows('children', $employeeId),
            'education' => $this->pdsModel->getRows('educational_background', $employeeId),
            'eligibility' => $this->pdsModel->getRows('civil_service_eligibility', $employeeId),
            'workExperience' => $this->pdsModel->getRows('work_experience', $employeeId),
            'voluntaryWork' => $this->pdsModel->getRows('voluntary_work', $employeeId),
            'learningDevelopment' => $this->pdsModel->getRows('learning_development', $employeeId),
            'otherInfo' => $this->pdsModel->getRows('other_info', $employeeId),
            'questionnaire' => $this->pdsModel->getSingleRow('questionnaire', $employeeId),
            'characterReferences' => $this->pdsModel->getRows('character_references', $employeeId),
            'completion' => $this->pdsModel->completionStatus($employeeId),
            'completionPercent' => $this->pdsModel->completionPercent($employeeId),
            'isUnlocked' => Auth::isRecordUnlocked('pds'),
        ];

        $this->view('employees', 'pds_form', $data);
    }

    public function saveSection(string $section): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        if (!Auth::isRecordUnlocked('pds')) {
            $this->json(['success' => false, 'error' => 'Your PDS is locked. Confirm your password before editing.'], 423);
        }

        $employeeId = $this->resolveEmployeeId();
        if (!$employeeId) {
            $this->json(['success' => false, 'error' => 'Not authorized for this employee record.'], 403);
            return;
        }

        try {
            if ($section === 'addresses') {
                $this->pdsModel->saveAddress($employeeId, 'Residential', $_POST['residential'] ?? []);
                $this->pdsModel->saveAddress($employeeId, 'Permanent', $_POST['permanent'] ?? []);
            } elseif (in_array($section, self::SINGLE_SECTIONS, true)) {
                $this->pdsModel->saveSingleRow($section, $employeeId, $_POST);
            } elseif (in_array($section, self::REPEATING_SECTIONS, true)) {
                $rows = json_decode($_POST['rows'] ?? '[]', true) ?: [];
                $this->pdsModel->replaceRows($section, $employeeId, $rows);
            } else {
                $this->json(['success' => false, 'error' => 'Unknown section.'], 400);
                return;
            }

            $this->pdsModel->markSectionComplete($employeeId, $section, true);
            AuditLogger::log('update', 'pds_' . $section, $employeeId, null, ['changed_fields' => array_keys($_POST)]);

            $this->json([
                'success' => true,
                'message' => 'Section saved.',
                'completionPercent' => $this->pdsModel->completionPercent($employeeId),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, ...$this->saveErrorDetails($e)], 400);
        } catch (Throwable $e) {
            error_log('PDS save failed [' . $section . ']: ' . $e->getMessage());
            $this->json(['success' => false, ...$this->saveErrorDetails($e)], 500);
        }
    }

    /** Print-friendly view of the full PDS (browser print / "Save as PDF"). */
    public function print(string $id): void
    {
        Auth::requireLogin();
        $employeeId = (int) $id;
        if ($employeeId !== Auth::employeeId() && !Auth::can('pds.view_all')) {
            http_response_code(403);
            echo 'Not authorized.';
            return;
        }

        $employee = $this->employeeModel->findWithDetails($employeeId);
        $photo = $this->employeeModel->latestPhoto($employeeId);
        $data = [
            'employee' => $employee,
            'personalInfo' => $this->pdsModel->getSingleRow('personal_info', $employeeId),
            'addresses' => $this->pdsModel->getAddresses($employeeId),
            'familyBackground' => $this->pdsModel->getSingleRow('family_background', $employeeId),
            'children' => $this->pdsModel->getRows('children', $employeeId),
            'education' => $this->pdsModel->getRows('educational_background', $employeeId),
            'eligibility' => $this->pdsModel->getRows('civil_service_eligibility', $employeeId),
            'workExperience' => $this->pdsModel->getRows('work_experience', $employeeId),
            'voluntaryWork' => $this->pdsModel->getRows('voluntary_work', $employeeId),
            'learningDevelopment' => $this->pdsModel->getRows('learning_development', $employeeId),
            'otherInfo' => $this->pdsModel->getRows('other_info', $employeeId),
            'distinctions' => $this->pdsModel->getRows('non_academic_distinctions', $employeeId),
            'memberships' => $this->pdsModel->getRows('memberships', $employeeId),
            'questionnaire' => $this->pdsModel->getSingleRow('questionnaire', $employeeId),
            'characterReferences' => $this->pdsModel->getRows('character_references', $employeeId),
            'photo' => $photo,
        ];
        $filename = 'CS_Form_212_' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($employee['employee_number'] ?? $employeeId)) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo (new PdsPdfGenerator())->render($data);
        exit;
    }

    public function completionReport(): void
    {
        Auth::requirePermission('report.view');
        $rows = $this->pdsModel->departmentCompletionReport();
        $this->view('employees', 'pds_completion_report', [
            'pageTitle' => 'PDS Completion Report',
            'rows' => $rows,
        ]);
    }
}
