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
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => 'Failed to save section.'], 500);
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
