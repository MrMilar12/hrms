<?php
// PDS (CS Form 212) model: one method group per section table.
// Each "repeating" section is saved with a delete-then-insert-all strategy per employee,
// keeping section saves atomic and simple from the AJAX tab UI.

class Pds extends Model
{
    private const SECTIONS = [
        'personal_info', 'addresses', 'family_background', 'children',
        'educational_background', 'civil_service_eligibility', 'work_experience',
        'voluntary_work', 'learning_development', 'other_info',
        'non_academic_distinctions', 'memberships', 'questionnaire', 'character_references',
    ];

    private array $singleRowFields = [
        'personal_info' => [
            'surname', 'first_name', 'middle_name', 'name_extension', 'birth_date', 'birth_place',
            'sex', 'civil_status', 'height_m', 'weight_kg', 'blood_type', 'citizenship',
            'dual_citizenship_country', 'dual_citizenship_type', 'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no',
            'philsys_card_no', 'tin_no', 'agency_employee_no', 'telephone_no', 'mobile_no', 'email',
            'government_issued_id', 'government_id_number', 'government_id_issuance',
        ],
        'family_background' => [
            'spouse_surname', 'spouse_first_name', 'spouse_middle_name', 'spouse_name_extension', 'spouse_occupation',
            'spouse_employer', 'spouse_business_address', 'spouse_telephone_no',
            'father_surname', 'father_first_name', 'father_middle_name', 'father_name_extension',
            'mother_maiden_surname', 'mother_first_name', 'mother_middle_name',
        ],
        'questionnaire' => [
            'q34a_related_by_consanguinity', 'q34a_details', 'q34b_related_to_appointing_authority', 'q34b_details',
            'q35a_found_guilty_admin_case', 'q35a_details', 'q35b_criminal_charged', 'q35b_details', 'q35b_date_filed', 'q35b_status_cases',
            'q35c_convicted', 'q35c_details', 'q35d_separated_from_service', 'q35d_details',
            'q36_candidate_last_election', 'q36_details', 'q37_resigned_to_avoid_campaign', 'q37_details',
            'q38a_immigrant_status', 'q38a_details', 'q39_indigenous_group', 'q39_details',
            'q40_pwd', 'q40_details', 'q41_solo_parent', 'q41_details',
        ],
    ];

    private array $repeatingRowFields = [
        'children' => ['full_name', 'birth_date'],
        'educational_background' => [
            'level', 'school_name', 'degree_course', 'period_from', 'period_to',
            'highest_units_earned', 'year_graduated', 'scholarship_honors',
        ],
        'civil_service_eligibility' => [
            'eligibility_name', 'rating', 'exam_date', 'exam_place', 'license_number', 'license_validity',
        ],
        'work_experience' => [
            'date_from', 'date_to', 'position_title', 'department_agency', 'monthly_salary',
            'salary_grade_step', 'appointment_status', 'is_government',
        ],
        'voluntary_work' => [
            'organization_name', 'organization_address', 'date_from', 'date_to', 'number_of_hours',
            'position_nature_of_work',
        ],
        'learning_development' => [
            'title', 'date_from', 'date_to', 'number_of_hours', 'type_of_ld', 'conducted_by',
        ],
        'other_info' => ['category', 'description'],
        'non_academic_distinctions' => ['description'],
        'memberships' => ['organization_name'],
        'character_references' => ['full_name', 'address', 'telephone_no'],
    ];

    public function sectionTable(string $section): string
    {
        if (!in_array($section, self::SECTIONS, true)) {
            throw new InvalidArgumentException('Unknown PDS section: ' . $section);
        }
        return 'pds_' . $section;
    }

    public function getSingleRow(string $section, int $employeeId): ?array
    {
        $table = $this->sectionTable($section);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveSingleRow(string $section, int $employeeId, array $data): void
    {
        $allowed = $this->singleRowFields[$section] ?? null;
        if ($allowed === null) {
            throw new InvalidArgumentException('Section is not a single-row section: ' . $section);
        }
        $table = $this->sectionTable($section);
        $data = array_intersect_key($data, array_flip($allowed));
        // HTML forms submit blank controls as empty strings. Nullable date,
        // number and enum columns require NULL when MySQL strict mode is enabled.
        $data = array_map(static fn($value) => is_string($value) && trim($value) === '' ? null : $value, $data);

        if ($section === 'personal_info' && $data['height_m'] !== null) {
            if (!is_numeric($data['height_m'])) {
                throw new InvalidArgumentException('Height must be a number, such as 1.65 meters or 165 centimeters.');
            }
            $height = (float) $data['height_m'];
            if ($height > 3 && $height <= 300) $height /= 100;
            if ($height < 0.5 || $height > 3) {
                throw new InvalidArgumentException('Enter a valid height between 0.50 and 3.00 meters.');
            }
            $data['height_m'] = number_format($height, 2, '.', '');
        }

        $existing = $this->getSingleRow($section, $employeeId);
        if ($existing) {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
            $stmt = $this->db->prepare("UPDATE {$table} SET {$set} WHERE employee_id = ?");
            $stmt->execute([...array_values($data), $employeeId]);
        } else {
            $columns = array_merge(['employee_id'], array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $stmt = $this->db->prepare("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})");
            $stmt->execute([$employeeId, ...array_values($data)]);
        }
    }

    /** Addresses: keyed by address_type (Residential/Permanent) rather than a plain single row. */
    public function getAddresses(int $employeeId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM pds_addresses WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $rows = $stmt->fetchAll();
        $byType = [];
        foreach ($rows as $row) {
            $byType[$row['address_type']] = $row;
        }
        return $byType;
    }

    public function saveAddress(int $employeeId, string $addressType, array $data): void
    {
        $allowed = ['house_block_lot', 'street', 'subdivision_village', 'barangay', 'city_municipality', 'province', 'zip_code'];
        $data = array_intersect_key($data, array_flip($allowed));
        $data = array_map(static function ($value) {
            $value = trim((string) $value);
            return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
        }, $data);

        $stmt = $this->db->prepare('SELECT id FROM pds_addresses WHERE employee_id = ? AND address_type = ?');
        $stmt->execute([$employeeId, $addressType]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
            $stmt = $this->db->prepare("UPDATE pds_addresses SET {$set} WHERE id = ?");
            $stmt->execute([...array_values($data), $existingId]);
        } else {
            $columns = array_merge(['employee_id', 'address_type'], array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $stmt = $this->db->prepare('INSERT INTO pds_addresses (' . implode(', ', $columns) . ") VALUES ({$placeholders})");
            $stmt->execute([$employeeId, $addressType, ...array_values($data)]);
        }
    }

    public function getRows(string $section, int $employeeId): array
    {
        $table = $this->sectionTable($section);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE employee_id = ? ORDER BY id");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll();
    }

    /** Replaces all rows for a repeating section (delete-then-insert), wrapped in a transaction. */
    public function replaceRows(string $section, int $employeeId, array $rows): void
    {
        $allowed = $this->repeatingRowFields[$section] ?? null;
        if ($allowed === null) {
            throw new InvalidArgumentException('Section is not a repeating section: ' . $section);
        }
        $table = $this->sectionTable($section);

        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM {$table} WHERE employee_id = ?");
            $del->execute([$employeeId]);

            foreach ($rows as $row) {
                $row = array_intersect_key($row, array_flip($allowed));
                if (empty($row)) {
                    continue;
                }
                $columns = array_merge(['employee_id'], array_keys($row));
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $stmt = $this->db->prepare("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})");
                $stmt->execute([$employeeId, ...array_values($row)]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function markSectionComplete(int $employeeId, string $section, bool $complete = true): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pds_completion_status (employee_id, section, is_complete, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE is_complete = VALUES(is_complete), updated_at = NOW()'
        );
        $stmt->execute([$employeeId, $section, $complete ? 1 : 0]);
    }

    public function completionStatus(int $employeeId): array
    {
        $stmt = $this->db->prepare('SELECT section, is_complete FROM pds_completion_status WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['section']] = (bool) $row['is_complete'];
        }
        foreach (self::SECTIONS as $section) {
            $result[$section] = $result[$section] ?? false;
        }
        return $result;
    }

    public function completionPercent(int $employeeId): int
    {
        $status = $this->completionStatus($employeeId);
        $total = count($status);
        $done = count(array_filter($status));
        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }

    /** Department-level PDS completion report for HR/Admin. */
    public function departmentCompletionReport(): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.name AS department_name,
                    COUNT(DISTINCT e.id) AS total_employees,
                    COUNT(DISTINCT CASE WHEN pcs.complete_sections = ? THEN e.id END) AS complete_employees
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN (
                 SELECT employee_id, SUM(is_complete) AS complete_sections
                 FROM pds_completion_status
                 GROUP BY employee_id
             ) pcs ON pcs.employee_id = e.id
             GROUP BY d.id, d.name'
        );
        $stmt->execute([count(self::SECTIONS)]);
        return $stmt->fetchAll();
    }

    public static function sections(): array
    {
        return self::SECTIONS;
    }
}
