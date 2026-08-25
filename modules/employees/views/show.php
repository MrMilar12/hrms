<?php
/** @var array $employee */
/** @var array|null $photo */
/** @var int $pdsPercent */
/** @var array $snapshot */
/** @var array $relatedSummary */
/** @var array $recentRecords */
/** @var array|null $highestEducation */
/** @var array $eligibilities */
require MODULES_PATH . '/shared/views/header.php';

$value = static fn(mixed $item, string $fallback = 'Not provided'): string =>
    ($item !== null && trim((string) $item) !== '') ? (string) $item : $fallback;
$fullName = trim(implode(' ', array_filter([$snapshot['first_name'] ?? null, $snapshot['middle_name'] ?? null, $snapshot['surname'] ?? null, $snapshot['name_extension'] ?? null]))) ?: $employee['employee_number'];
$address = implode(', ', array_filter([$snapshot['house_block_lot'] ?? null, $snapshot['street'] ?? null, $snapshot['subdivision_village'] ?? null, $snapshot['barangay'] ?? null, $snapshot['city_municipality'] ?? null, $snapshot['province'] ?? null, $snapshot['zip_code'] ?? null]));
$taskStats = $relatedSummary['tasks'] ?? [];
$accomplishmentStats = $relatedSummary['accomplishments'] ?? [];
?>

<main class="employee-record-page">
    <section class="employee-record-hero glass-card">
        <div class="employee-record-photo">
            <?php if ($photo): ?><img src="<?= BASE_URL ?>/photo/<?= (int) $photo['id'] ?>" alt="Photo of <?= htmlspecialchars($fullName) ?>">
            <?php else: ?><span><?= htmlspecialchars(strtoupper(substr($snapshot['first_name'] ?? $employee['employee_number'], 0, 1))) ?></span><?php endif; ?>
        </div>
        <div class="employee-record-identity">
            <span class="launcher-eyebrow">Employee 201 file</span><h1><?= htmlspecialchars($fullName) ?></h1>
            <p><?= htmlspecialchars($value($employee['position_title'] ?? null)) ?> <span>&middot;</span> <?= htmlspecialchars($value($employee['department_name'] ?? null)) ?></p>
            <div class="employee-record-badges"><span class="status-pill"><span class="dot"></span><?= htmlspecialchars($employee['employment_status']) ?></span><span class="record-chip"><?= htmlspecialchars($value($snapshot['personnel_type'] ?? null, 'Unclassified')) ?></span><span class="record-chip">Employee # <?= htmlspecialchars($employee['employee_number']) ?></span></div>
        </div>
        <div class="employee-record-actions"><a class="btn btn-primary" href="<?= BASE_URL ?>/pds?employee_id=<?= (int) $employee['id'] ?>">Edit PDS</a><a class="btn btn-secondary" href="<?= BASE_URL ?>/pds/print/<?= (int) $employee['id'] ?>" target="_blank">Print PDS</a></div>
    </section>

    <section class="employee-record-metrics" aria-label="Employee record summary">
        <article class="glass-card record-metric"><span>PDS completion</span><strong><?= $pdsPercent ?>%</strong><div class="progress-bar"><div class="progress-bar-fill" data-target="<?= $pdsPercent ?>"></div></div></article>
        <article class="glass-card record-metric"><span>Assigned tasks</span><strong><?= (int) ($taskStats['total'] ?? 0) ?></strong><small><?= (int) ($taskStats['done'] ?? 0) ?> completed</small></article>
        <article class="glass-card record-metric"><span>Overdue tasks</span><strong><?= (int) ($taskStats['overdue'] ?? 0) ?></strong><small>Needs attention</small></article>
        <article class="glass-card record-metric"><span>Accomplishments</span><strong><?= (int) ($accomplishmentStats['total'] ?? 0) ?></strong><small><?= (int) ($accomplishmentStats['approved'] ?? 0) ?> approved</small></article>
    </section>

    <div class="employee-record-grid">
        <section class="glass-card employee-record-section">
            <div class="record-section-heading"><div><span class="launcher-eyebrow">Identity and contact</span><h2>Personal information</h2></div></div>
            <dl class="record-detail-grid">
                <div><dt>Birth date</dt><dd><?= htmlspecialchars($value($snapshot['birth_date'] ?? null)) ?></dd></div><div><dt>Gender</dt><dd><?= htmlspecialchars($value($snapshot['sex'] ?? null)) ?></dd></div>
                <div><dt>Civil status</dt><dd><?= htmlspecialchars($value($snapshot['civil_status'] ?? null)) ?></dd></div><div><dt>Citizenship</dt><dd><?= htmlspecialchars($value($snapshot['citizenship'] ?? null)) ?></dd></div>
                <div><dt>Mobile number</dt><dd><?= htmlspecialchars($value($snapshot['mobile_no'] ?? null)) ?></dd></div><div><dt>Email address</dt><dd><?= htmlspecialchars($value($snapshot['email'] ?? null)) ?></dd></div>
                <div class="record-detail-wide"><dt>Residential address</dt><dd><?= htmlspecialchars($value($address)) ?></dd></div>
            </dl>
        </section>
        <section class="glass-card employee-record-section">
            <div class="record-section-heading"><div><span class="launcher-eyebrow">Appointment</span><h2>Employment information</h2></div></div>
            <dl class="record-detail-grid">
                <div><dt>Date hired</dt><dd><?= htmlspecialchars($value($employee['date_hired'] ?? null)) ?></dd></div><div><dt>Personnel type</dt><dd><?= htmlspecialchars($value($snapshot['personnel_type'] ?? null)) ?></dd></div>
                <div><dt>Item number</dt><dd><?= htmlspecialchars($value($snapshot['item_number'] ?? null)) ?></dd></div><div><dt>Salary grade</dt><dd><?= htmlspecialchars($value($snapshot['salary_grade'] ?? null)) ?></dd></div>
                <div><dt>School ID code</dt><dd><?= htmlspecialchars($value($snapshot['school_id_code'] ?? null)) ?></dd></div><div><dt>District</dt><dd><?= htmlspecialchars($value($snapshot['district'] ?? null)) ?></dd></div>
                <div class="record-detail-wide"><dt>Plantilla station</dt><dd><?= htmlspecialchars($value($snapshot['plantilla_school_station'] ?? null)) ?></dd></div><div class="record-detail-wide"><dt>Current station</dt><dd><?= htmlspecialchars($value($snapshot['current_school_station'] ?? null)) ?></dd></div>
                <?php if (($snapshot['personnel_type'] ?? '') === 'Teaching'): ?><div><dt>Grade levels taught</dt><dd><?= htmlspecialchars($value($snapshot['grade_levels_taught'] ?? null)) ?></dd></div><div><dt>Specialization</dt><dd><?= htmlspecialchars($value($snapshot['specialization'] ?? null)) ?></dd></div><div class="record-detail-wide"><dt>Subjects taught</dt><dd><?= htmlspecialchars($value($snapshot['subjects_taught'] ?? null)) ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <section class="glass-card employee-record-section">
            <div class="record-section-heading"><div><span class="launcher-eyebrow">Qualifications</span><h2>Education and eligibility</h2></div></div>
            <dl class="record-detail-grid"><div><dt>Highest attainment</dt><dd><?= htmlspecialchars($value($highestEducation['level'] ?? null)) ?></dd></div><div><dt>Course</dt><dd><?= htmlspecialchars($value($highestEducation['degree_course'] ?? null)) ?></dd></div><div class="record-detail-wide"><dt>School</dt><dd><?= htmlspecialchars($value($highestEducation['school_name'] ?? null)) ?></dd></div><div class="record-detail-wide"><dt>Eligibility</dt><dd><?= htmlspecialchars($eligibilities ? implode(', ', array_column($eligibilities, 'eligibility_name')) : 'Not provided') ?></dd></div></dl>
        </section>
        <section class="glass-card employee-record-section">
            <div class="record-section-heading"><div><span class="launcher-eyebrow">Access and security</span><h2>Account overview</h2></div></div>
            <dl class="record-detail-grid"><div><dt>Account status</dt><dd><?= htmlspecialchars(ucfirst($value($snapshot['account_status'] ?? null))) ?></dd></div><div><dt>System role</dt><dd><?= htmlspecialchars($value($snapshot['role_name'] ?? null)) ?></dd></div><div><dt>Two-factor security</dt><dd><?= !empty($snapshot['two_factor_enabled']) ? 'Enabled' : 'Not enabled' ?></dd></div><div><dt>Last login</dt><dd><?= htmlspecialchars($value($snapshot['last_login'] ?? null, 'Never')) ?></dd></div></dl>
        </section>
    </div>

    <div class="employee-related-grid">
        <section class="glass-card employee-record-section"><div class="record-section-heading"><div><span class="launcher-eyebrow">Workload</span><h2>Recent tasks</h2></div></div><div class="record-list">
            <?php foreach ($recentRecords['tasks'] as $task): ?><a href="<?= BASE_URL ?>/tasks/<?= (int) $task['id'] ?>"><span><strong><?= htmlspecialchars($task['title']) ?></strong><small><?= htmlspecialchars($task['priority']) ?> priority<?= $task['due_date'] ? ' · Due ' . htmlspecialchars($task['due_date']) : '' ?></small></span><em><?= htmlspecialchars($task['status']) ?></em></a><?php endforeach; ?>
            <?php if (!$recentRecords['tasks']): ?><p class="record-empty">No assigned tasks yet.</p><?php endif; ?>
        </div></section>
        <section class="glass-card employee-record-section"><div class="record-section-heading"><div><span class="launcher-eyebrow">Performance records</span><h2>Recent accomplishments</h2></div><span class="record-chip"><?= (int) ($accomplishmentStats['pending'] ?? 0) ?> pending</span></div><div class="record-list">
            <?php foreach ($recentRecords['accomplishments'] as $item): ?><a href="<?= BASE_URL ?>/accomplishments/<?= (int) $item['id'] ?>"><span><strong><?= htmlspecialchars($item['title']) ?></strong><small><?= htmlspecialchars($item['accomplishment_date']) ?></small></span><em><?= htmlspecialchars($item['status']) ?></em></a><?php endforeach; ?>
            <?php if (!$recentRecords['accomplishments']): ?><p class="record-empty">No accomplishment records yet.</p><?php endif; ?>
        </div></section>
    </div>
</main>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
