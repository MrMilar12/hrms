<?php
/** @var array $employee */
/** @var array|null $personalInfo */
/** @var array $addresses */
/** @var array|null $familyBackground */
/** @var array $children */
/** @var array $education */
/** @var array $eligibility */
/** @var array $workExperience */
/** @var array $characterReferences */
function pv2($arr, $key, $default = '') { return htmlspecialchars((string) ($arr[$key] ?? $default)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PDS — <?= pv2($personalInfo, 'surname') ?>, <?= pv2($personalInfo, 'first_name') ?></title>
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 20px; }
    h1 { font-size: 16px; text-align: center; }
    h2 { font-size: 13px; background: #ddd; padding: 4px; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    td, th { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
    .no-print { margin-bottom: 15px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">Print / Save as PDF</button>
</div>

<h1>PERSONAL DATA SHEET (CS FORM NO. 212)</h1>

<h2>I. Personal Information</h2>
<table>
    <tr><th>Surname</th><td><?= pv2($personalInfo, 'surname') ?></td><th>First Name</th><td><?= pv2($personalInfo, 'first_name') ?></td><th>Middle Name</th><td><?= pv2($personalInfo, 'middle_name') ?></td></tr>
    <tr><th>Birth Date</th><td><?= pv2($personalInfo, 'birth_date') ?></td><th>Birth Place</th><td><?= pv2($personalInfo, 'birth_place') ?></td><th>Sex</th><td><?= pv2($personalInfo, 'sex') ?></td></tr>
    <tr><th>Civil Status</th><td><?= pv2($personalInfo, 'civil_status') ?></td><th>Citizenship</th><td><?= pv2($personalInfo, 'citizenship') ?></td><th>Blood Type</th><td><?= pv2($personalInfo, 'blood_type') ?></td></tr>
    <tr><th>GSIS No.</th><td><?= pv2($personalInfo, 'gsis_no') ?></td><th>Pag-IBIG No.</th><td><?= pv2($personalInfo, 'pagibig_no') ?></td><th>PhilHealth No.</th><td><?= pv2($personalInfo, 'philhealth_no') ?></td></tr>
    <tr><th>SSS No.</th><td><?= pv2($personalInfo, 'sss_no') ?></td><th>TIN No.</th><td><?= pv2($personalInfo, 'tin_no') ?></td><th>Mobile No.</th><td><?= pv2($personalInfo, 'mobile_no') ?></td></tr>
</table>

<table>
    <?php $r = $addresses['Residential'] ?? []; $p = $addresses['Permanent'] ?? []; ?>
    <tr><th colspan="2">Residential Address</th><th colspan="2">Permanent Address</th></tr>
    <tr>
        <td colspan="2"><?= implode(', ', array_filter([pv2($r,'house_block_lot'), pv2($r,'street'), pv2($r,'subdivision_village'), pv2($r,'barangay'), pv2($r,'city_municipality'), pv2($r,'province'), pv2($r,'zip_code')])) ?></td>
        <td colspan="2"><?= implode(', ', array_filter([pv2($p,'house_block_lot'), pv2($p,'street'), pv2($p,'subdivision_village'), pv2($p,'barangay'), pv2($p,'city_municipality'), pv2($p,'province'), pv2($p,'zip_code')])) ?></td>
    </tr>
</table>

<h2>II. Family Background</h2>
<table>
    <tr><th>Spouse</th><td><?= pv2($familyBackground,'spouse_surname') ?>, <?= pv2($familyBackground,'spouse_first_name') ?> <?= pv2($familyBackground,'spouse_middle_name') ?></td></tr>
    <tr><th>Father</th><td><?= pv2($familyBackground,'father_surname') ?>, <?= pv2($familyBackground,'father_first_name') ?> <?= pv2($familyBackground,'father_middle_name') ?></td></tr>
    <tr><th>Mother</th><td><?= pv2($familyBackground,'mother_maiden_surname') ?>, <?= pv2($familyBackground,'mother_first_name') ?> <?= pv2($familyBackground,'mother_middle_name') ?></td></tr>
</table>
<?php if ($children): ?>
<table>
    <tr><th>Children</th><th>Birth Date</th></tr>
    <?php foreach ($children as $c): ?>
        <tr><td><?= pv2($c,'full_name') ?></td><td><?= pv2($c,'birth_date') ?></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>III. Educational Background</h2>
<table>
    <tr><th>Level</th><th>School</th><th>Degree/Course</th><th>Period</th><th>Units/Year Grad.</th><th>Honors</th></tr>
    <?php foreach ($education as $e): ?>
        <tr>
            <td><?= pv2($e,'level') ?></td><td><?= pv2($e,'school_name') ?></td><td><?= pv2($e,'degree_course') ?></td>
            <td><?= pv2($e,'period_from') ?>–<?= pv2($e,'period_to') ?></td>
            <td><?= pv2($e,'highest_units_earned') ?> / <?= pv2($e,'year_graduated') ?></td>
            <td><?= pv2($e,'scholarship_honors') ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>IV. Civil Service Eligibility</h2>
<table>
    <tr><th>Eligibility</th><th>Rating</th><th>Exam Date</th><th>Exam Place</th><th>License No.</th></tr>
    <?php foreach ($eligibility as $el): ?>
        <tr><td><?= pv2($el,'eligibility_name') ?></td><td><?= pv2($el,'rating') ?></td><td><?= pv2($el,'exam_date') ?></td><td><?= pv2($el,'exam_place') ?></td><td><?= pv2($el,'license_number') ?></td></tr>
    <?php endforeach; ?>
</table>

<h2>V. Work Experience</h2>
<table>
    <tr><th>From</th><th>To</th><th>Position</th><th>Dept/Agency</th><th>Salary</th><th>Gov't</th></tr>
    <?php foreach ($workExperience as $w): ?>
        <tr>
            <td><?= pv2($w,'date_from') ?></td><td><?= pv2($w,'date_to') ?></td><td><?= pv2($w,'position_title') ?></td>
            <td><?= pv2($w,'department_agency') ?></td><td><?= pv2($w,'monthly_salary') ?></td>
            <td><?= ((int) ($w['is_government'] ?? 0)) ? 'Yes' : 'No' ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>IX. Character References</h2>
<table>
    <tr><th>Name</th><th>Address</th><th>Telephone</th></tr>
    <?php foreach ($characterReferences as $ref): ?>
        <tr><td><?= pv2($ref,'full_name') ?></td><td><?= pv2($ref,'address') ?></td><td><?= pv2($ref,'telephone_no') ?></td></tr>
    <?php endforeach; ?>
</table>

</body>
</html>
