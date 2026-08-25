<?php

use setasign\Fpdi\Fpdi;

/** Creates a print-ready CS Form No. 212 (Revised 2026) using the official PDF as its background. */
class PdsPdfGenerator
{
    private Fpdi $pdf;
    private string $template;

    public function __construct(?string $template = null)
    {
        $this->template = $template ?? STORAGE_PATH . '/templates/cs_form_212_revised_2026.pdf';
        if (!is_file($this->template)) {
            throw new RuntimeException('The official CS Form No. 212 PDF template is missing.');
        }
        $this->pdf = new Fpdi('P', 'mm');
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->SetMargins(0, 0, 0);
    }

    public function render(array $data, string $destination = 'S'): string
    {
        $this->pdf->setSourceFile($this->template);
        for ($page = 1; $page <= 4; $page++) {
            $templateId = $this->pdf->importPage($page);
            $size = $this->pdf->getTemplateSize($templateId);
            $this->pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $this->pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
            match ($page) {
                1 => $this->pageOne($data),
                2 => $this->pageTwo($data),
                3 => $this->pageThree($data),
                4 => $this->pageFour($data),
            };
        }
        if (str_starts_with($destination, 'F:')) {
            return $this->pdf->Output('F', substr($destination, 2));
        }
        return $this->pdf->Output($destination, 'CS_Form_212_PDS.pdf');
    }

    private function pageOne(array $d): void
    {
        $p = $d['personalInfo'] ?? [];
        $family = $d['familyBackground'] ?? [];
        $residential = $d['addresses']['Residential'] ?? [];
        $permanent = $d['addresses']['Permanent'] ?? [];
        $familyTextOffset = 2.4;

        $this->text(61, 55, 145, 5, $p['surname'] ?? '');
        $this->text(61, 63, 145, 5, $p['first_name'] ?? '');
        $this->text(61, 70.5, 145, 5, $p['middle_name'] ?? '');
        $this->text(246, 63, 16, 5, $p['name_extension'] ?? '', 'C');
        $this->text(61, 80, 58, 6, $this->date($p['birth_date'] ?? null), 'C');
        $this->text(61, 89, 58, 6, $p['birth_place'] ?? '');
        $this->choice(65.8, 100.2, $p['sex'] ?? null, 'Male');
        $this->choice(101.8, 100.2, $p['sex'] ?? null, 'Female');
        foreach (['Single' => [65.8, 108.5], 'Married' => [101.8, 108.5], 'Widowed' => [65.8, 114], 'Separated' => [101.8, 114], 'Others' => [65.8, 119.5]] as $value => [$x, $y]) {
            $this->choice($x, $y, $p['civil_status'] ?? null, $value);
        }
        $this->text(61, 121.5, 58, 6, $p['height_m'] ?? '', 'C');
        $this->text(61, 130, 58, 6, $p['weight_kg'] ?? '', 'C');
        $this->text(61, 138.2, 58, 6, $p['blood_type'] ?? '', 'C');
        foreach ([['gsis_no',146.5], ['pagibig_no',155], ['philhealth_no',163.5], ['philsys_card_no',172], ['tin_no',180.5], ['agency_employee_no',189]] as [$key, $y]) {
            $this->text(61, $y, 58, 6, $p[$key] ?? '');
        }

        $citizenship = strtolower((string) ($p['citizenship'] ?? ''));
        if (str_contains($citizenship, 'filipino')) $this->mark(184.1, 81.1);
        if (!empty($p['dual_citizenship_country'])) $this->mark(204.9, 81.1);
        if (($p['dual_citizenship_type'] ?? '') === 'By Birth') $this->mark(212.4, 86.9);
        if (($p['dual_citizenship_type'] ?? '') === 'By Naturalization') $this->mark(231.4, 86.9);
        $this->text(209, 94, 40, 5, $p['dual_citizenship_country'] ?? '', 'C');

        $this->address($residential, 157.2, 106);
        $this->address($permanent, 157.2, 139);
        $this->text(157.2, 172.8, 108, 5.5, $p['telephone_no'] ?? '');
        $this->text(157.2, 181.3, 108, 5.5, $p['mobile_no'] ?? '');
        $this->text(157.2, 189.8, 108, 5.5, $p['email'] ?? '');

        foreach ([['spouse_surname',203], ['spouse_middle_name',217.4], ['spouse_occupation',224.6], ['spouse_employer',231.8], ['spouse_business_address',239], ['spouse_telephone_no',246.2]] as [$key, $y]) {
            $this->text(61, $y + $familyTextOffset, 96, 5.5, $family[$key] ?? '');
        }
        // The first-name and extension fields are separate cells in the official form.
        // Keeping the first name at the old 96 mm width caused it to run underneath
        // the extension and made both values look horizontally misaligned.
        $this->text(61, 210.2 + $familyTextOffset, 60, 5.5, $family['spouse_first_name'] ?? '');
        $this->text(122, 210.2 + $familyTextOffset, 16, 5.5, $family['spouse_name_extension'] ?? '', 'C');
        foreach (array_slice($d['children'] ?? [], 0, 7) as $i => $row) {
            // The children table has its own heading row; data begins below it.
            $y = 210.3 + $familyTextOffset + ($i * 7.3);
            $this->text(158, $y, 68, 5.5, $row['full_name'] ?? '');
            $this->text(227, $y, 35, 5.5, $this->date($row['birth_date'] ?? null), 'C');
        }
        foreach ([['father_surname',260], ['father_middle_name',274.4]] as [$key, $y]) {
            $this->text(61, $y + $familyTextOffset, 96, 5.5, $family[$key] ?? '');
        }
        $this->text(61, 267.2 + $familyTextOffset, 60, 5.5, $family['father_first_name'] ?? '');
        $this->text(122, 267.2 + $familyTextOffset, 16, 5.5, $family['father_name_extension'] ?? '', 'C');

        // "Mother's Maiden Name" is a printed heading row, not the surname
        // value row. The three values begin on the following row.
        foreach ([['mother_maiden_surname',289], ['mother_first_name',296.2], ['mother_middle_name',303.4]] as [$key, $y]) {
            $this->text(61, $y + $familyTextOffset, 96, 5.5, $family[$key] ?? '');
        }

        $educationByLevel = [];
        foreach ($d['education'] ?? [] as $row) $educationByLevel[$row['level']] = $row;
        foreach (['Elementary', 'Secondary', 'Vocational', 'College', 'Graduate Studies'] as $i => $level) {
            $row = $educationByLevel[$level] ?? [];
            $y = 335 + ($i * 9.8);
            $this->text(61, $y, 58, 6.5, $row['school_name'] ?? '');
            $this->text(120, $y, 55, 6.5, $row['degree_course'] ?? '');
            $this->text(176, $y, 14, 6.5, $row['period_from'] ?? '', 'C');
            $this->text(190, $y, 14, 6.5, $row['period_to'] ?? '', 'C');
            $this->text(205, $y, 21, 6.5, $row['highest_units_earned'] ?? '', 'C');
            $this->text(227, $y, 18, 6.5, $row['year_graduated'] ?? '', 'C');
            $this->text(245, $y, 17, 6.5, $row['scholarship_honors'] ?? '', 'C');
        }
    }

    private function pageTwo(array $d): void
    {
        foreach (array_slice($d['eligibility'] ?? [], 0, 7) as $i => $row) {
            $y = 50.5 + ($i * 9.2);
            $this->text(26, $y, 71, 6, $row['eligibility_name'] ?? '');
            $this->text(98, $y, 24, 6, $row['rating'] ?? '', 'C');
            $this->text(123, $y, 25, 6, $this->date($row['exam_date'] ?? null), 'C');
            $this->text(149, $y, 69, 6, $row['exam_place'] ?? '');
            $this->text(219, $y, 21, 6, $row['license_number'] ?? '', 'C');
            $this->text(241, $y, 15, 6, $this->date($row['license_validity'] ?? null), 'C');
        }
        foreach (array_slice($d['workExperience'] ?? [], 0, 23) as $i => $row) {
            $y = 150.7 + ($i * 8.25);
            $this->text(26, $y, 18, 5.6, $this->date($row['date_from'] ?? null), 'C');
            $this->text(45, $y, 18, 5.6, $this->date($row['date_to'] ?? null), 'C');
            $this->text(64, $y, 58, 5.6, $row['position_title'] ?? '');
            $this->text(123, $y, 59, 5.6, $row['department_agency'] ?? '');
            $salary = isset($row['monthly_salary']) ? number_format((float) $row['monthly_salary'], 2) : '';
            $this->text(183, $y, 16, 5.6, $salary, 'R');
            $this->text(200, $y, 18, 5.6, $row['salary_grade_step'] ?? '', 'C');
            $this->text(219, $y, 21, 5.6, $row['appointment_status'] ?? '', 'C');
            $this->text(241, $y, 15, 5.6, !empty($row['is_government']) ? 'Y' : 'N', 'C');
        }
    }

    private function pageThree(array $d): void
    {
        foreach (array_slice($d['learningDevelopment'] ?? [], 0, 17) as $i => $row) {
            $y = 38.5 + ($i * 8.5);
            $this->text(14, $y, 112, 5.8, $row['title'] ?? '');
            $this->text(127, $y, 19, 5.8, $this->date($row['date_from'] ?? null), 'C');
            $this->text(147, $y, 19, 5.8, $this->date($row['date_to'] ?? null), 'C');
            $this->text(167, $y, 19, 5.8, $row['number_of_hours'] ?? '', 'C');
            $this->text(187, $y, 21, 5.8, $row['type_of_ld'] ?? '', 'C');
            $this->text(209, $y, 64, 5.8, $row['conducted_by'] ?? '');
        }
        foreach (array_slice($d['voluntaryWork'] ?? [], 0, 9) as $i => $row) {
            $y = 221 + ($i * 8.5);
            $organization = trim(($row['organization_name'] ?? '') . ' - ' . ($row['organization_address'] ?? ''), ' -');
            $this->text(14, $y, 112, 5.8, $organization);
            $this->text(127, $y, 19, 5.8, $this->date($row['date_from'] ?? null), 'C');
            $this->text(147, $y, 19, 5.8, $this->date($row['date_to'] ?? null), 'C');
            $this->text(167, $y, 19, 5.8, $row['number_of_hours'] ?? '', 'C');
            $this->text(187, $y, 86, 5.8, $row['position_nature_of_work'] ?? '');
        }
        $skills = array_values(array_filter($d['otherInfo'] ?? [], fn($r) => in_array($r['category'] ?? '', ['Skill', 'Hobby'], true)));
        $recognitions = array_merge(
            array_values(array_filter($d['otherInfo'] ?? [], fn($r) => ($r['category'] ?? '') === 'Recognition')),
            array_map(fn($r) => ['description' => $r['description'] ?? ''], $d['distinctions'] ?? [])
        );
        $memberships = array_merge(
            array_values(array_filter($d['otherInfo'] ?? [], fn($r) => ($r['category'] ?? '') === 'Membership')),
            array_map(fn($r) => ['description' => $r['organization_name'] ?? ''], $d['memberships'] ?? [])
        );
        for ($i = 0; $i < 7; $i++) {
            $y = 321 + ($i * 8.5);
            $this->text(14, $y, 65, 5.8, $skills[$i]['description'] ?? '');
            $this->text(80, $y, 128, 5.8, $recognitions[$i]['description'] ?? '');
            $this->text(209, $y, 64, 5.8, $memberships[$i]['description'] ?? '');
        }
    }

    private function pageFour(array $d): void
    {
        $q = $d['questionnaire'] ?? [];
        $questions = [
            ['q34a_related_by_consanguinity', 'q34a_details', 161, 18, 185, 18, 164, 28],
            ['q34b_related_to_appointing_authority', 'q34b_details', 161, 36, 185, 36, 164, 45],
            ['q35a_found_guilty_admin_case', 'q35a_details', 161, 53, 185, 53, 164, 62],
            ['q35b_criminal_charged', 'q35b_details', 161, 71, 186, 71, 187, 80],
            ['q35c_convicted', 'q35c_details', 161, 94, 187, 94, 164, 103],
            ['q35d_separated_from_service', 'q35d_details', 160, 112, 187, 112, 164, 119],
            ['q36_candidate_last_election', 'q36_details', 161, 127, 189, 127, 187, 132],
            ['q37_resigned_to_avoid_campaign', 'q37_details', 161, 138, 189, 138, 187, 143],
            ['q38a_immigrant_status', 'q38a_details', 161, 151, 189, 151, 164, 159],
            ['q39_indigenous_group', 'q39_details', 161, 181, 189, 181, 201, 184],
            ['q40_pwd', 'q40_details', 161, 190, 189, 190, 201, 193],
            ['q41_solo_parent', 'q41_details', 161, 200, 189, 200, 201, 203],
        ];
        foreach ($questions as [$key, $details, $yesX, $yesY, $noX, $noY, $textX, $textY]) {
            if (array_key_exists($key, $q) && $q[$key] !== null && $q[$key] !== '') {
                $this->mark((int) $q[$key] === 1 ? $yesX : $noX, (int) $q[$key] === 1 ? $yesY : $noY);
            }
            $this->text($textX, $textY, 32, 4.5, $q[$details] ?? '');
        }
        $this->text(187, 84, 45, 4.5, $this->date($q['q35b_date_filed'] ?? null));
        $this->text(187, 88.5, 45, 4.5, $q['q35b_status_cases'] ?? '');
        foreach (array_slice($d['characterReferences'] ?? [], 0, 3) as $i => $row) {
            $y = 222.5 + ($i * 8.2);
            $this->text(21, $y, 81, 5.8, $row['full_name'] ?? '');
            $this->text(103, $y, 51, 5.8, $row['address'] ?? '');
            $this->text(155, $y, 26, 5.8, $row['telephone_no'] ?? '', 'C');
        }
        $photoPath = $d['photo']['file_path'] ?? null;
        if ($photoPath && is_file($photoPath)) {
            $this->pdf->Image($photoPath, 192.5, 216.5, 35.5, 40.5);
        }
        $p = $d['personalInfo'] ?? [];
        $this->text(53, 277.5, 40, 5.5, $p['government_issued_id'] ?? '');
        $this->text(53, 288.5, 40, 5.5, $p['government_id_number'] ?? '');
        $this->text(53, 299.5, 40, 5.5, $p['government_id_issuance'] ?? '');
    }

    private function address(array $address, float $x, float $y): void
    {
        $rightX = 204.8;
        $this->text($x, $y, 46.2, 4.2, $address['house_block_lot'] ?? '', 'C');
        $this->text($rightX, $y, 60.2, 4.2, $address['street'] ?? '', 'C');
        $this->text($x, $y + 8.5, 46.2, 4.2, $address['subdivision_village'] ?? '', 'C');
        $this->text($rightX, $y + 8.5, 60.2, 4.2, $address['barangay'] ?? '', 'C');
        $this->text($x, $y + 17, 46.2, 4.2, $address['city_municipality'] ?? '', 'C');
        $this->text($rightX, $y + 17, 60.2, 4.2, $address['province'] ?? '', 'C');
        $this->text($x, $y + 25.5, 107.8, 4.2, $address['zip_code'] ?? '', 'C');
    }

    private function choice(float $x, float $y, mixed $actual, string $expected): void
    {
        if ((string) $actual === $expected) $this->mark($x, $y);
    }

    private function mark(float $x, float $y): void
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetTextColor(0, 0, 0);
        // The coordinates represent the visual center of a printed box. FPDF's
        // font baseline sits above the geometric center of a Cell, so retain the
        // horizontal half-cell offset while lowering the Cell by 1.2 mm.
        $this->pdf->SetXY($x - 1.6, $y - .4);
        $this->pdf->Cell(3.2, 3.2, 'X', 0, 0, 'C');
    }

    private function text(float $x, float $y, float $width, float $height, mixed $value, string $align = 'L'): void
    {
        $value = trim((string) $value);
        if ($value === '') return;
        $value = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value) ?: '';
        $size = 7.2;
        $this->pdf->SetFont('Arial', '', $size);
        while ($size > 4.3 && $this->pdf->GetStringWidth($value) > $width - 1.4) {
            $size -= 0.2;
            $this->pdf->SetFont('Arial', '', $size);
        }
        if ($this->pdf->GetStringWidth($value) > $width - 1.4) {
            while ($value !== '' && $this->pdf->GetStringWidth($value . '...') > $width - 1.4) $value = substr($value, 0, -1);
            $value .= '...';
        }
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetXY($x + .7, $y);
        $this->pdf->Cell($width - 1.4, $height, $value, 0, 0, $align);
    }

    private function date(mixed $value): string
    {
        if (!$value) return '';
        $timestamp = strtotime((string) $value);
        return $timestamp ? date('d/m/Y', $timestamp) : (string) $value;
    }
}
