<?php
/** @var array $employee */
/** @var array|null $photo */
/** @var int $pdsPercent */
/** @var array $snapshot */
/** @var array $relatedSummary */
/** @var array $recentRecords */
/** @var array|null $highestEducation */
/** @var array $eligibilities */
/** @var array $movementHistory */
/** @var array $positions */
/** @var bool $canManage */
require MODULES_PATH . '/shared/views/header.php';

$value = static fn(mixed $item, string $fallback = 'Not provided'): string =>
    ($item !== null && trim((string) $item) !== '') ? (string) $item : $fallback;
$firstName = trim((string) ($snapshot['first_name'] ?? ''));
$surname = trim((string) ($snapshot['surname'] ?? ''));
$nameParts = $firstName !== '' && $surname !== '' && str_contains(mb_strtolower($firstName), mb_strtolower($surname))
    ? [$firstName, $snapshot['name_extension'] ?? null]
    : [$firstName ?: null, $snapshot['middle_name'] ?? null, $surname ?: null, $snapshot['name_extension'] ?? null];
$fullName = trim(implode(' ', array_filter($nameParts))) ?: $employee['employee_number'];
$address = implode(', ', array_filter([$snapshot['house_block_lot'] ?? null, $snapshot['street'] ?? null, $snapshot['subdivision_village'] ?? null, $snapshot['barangay'] ?? null, $snapshot['city_municipality'] ?? null, $snapshot['province'] ?? null, $snapshot['zip_code'] ?? null]));
$taskStats = $relatedSummary['tasks'] ?? [];
$accomplishmentStats = $relatedSummary['accomplishments'] ?? [];
$isTeaching = strcasecmp(trim((string) ($snapshot['personnel_type'] ?? '')), 'Teaching') === 0;
$subjectsTaught = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string) ($snapshot['subjects_taught'] ?? '')) ?: [])));
?>

<main class="employee-record-page">
    <section class="employee-record-hero glass-card">
        <div class="employee-record-cover" aria-hidden="true"><span>HRMS</span></div>
        <div class="employee-record-profile-row">
        <div class="employee-record-photo">
            <?php if ($photo): ?><img src="<?= BASE_URL ?>/photo/<?= UrlId::encode((int) $photo['id']) ?>" alt="Photo of <?= htmlspecialchars($fullName) ?>">
            <?php else: ?><span><?= htmlspecialchars(strtoupper(substr($snapshot['first_name'] ?? $employee['employee_number'], 0, 1))) ?></span><?php endif; ?>
        </div>
        <div class="employee-record-identity">
            <span class="launcher-eyebrow">Employee 201 file</span><h1><?= htmlspecialchars($fullName) ?></h1>
            <p><?= htmlspecialchars($value($employee['position_title'] ?? null)) ?> <span>&middot;</span> <?= htmlspecialchars($value($employee['department_name'] ?? null)) ?></p>
            <div class="employee-record-badges"><span class="status-pill"><span class="dot"></span><?= htmlspecialchars($employee['employment_status']) ?></span><span class="record-chip"><?= htmlspecialchars($value($snapshot['personnel_type'] ?? null, 'Unclassified')) ?></span><span class="record-chip">Employee # <?= htmlspecialchars($employee['employee_number']) ?></span></div>
        </div>
        <div class="employee-record-actions"><a class="btn btn-primary" href="<?= BASE_URL ?>/pds?employee_id=<?= UrlId::encode((int) $employee['id']) ?>">Edit PDS</a><a class="btn btn-secondary" href="<?= BASE_URL ?>/pds/print/<?= UrlId::encode((int) $employee['id']) ?>" target="_blank">Print PDS</a></div>
        </div>
        <nav class="employee-profile-nav" aria-label="Employee profile sections">
            <a class="active" href="#overview">Overview</a><a href="#information">Profile details</a>
        </nav>
    </section>

    <section class="employee-record-metrics" id="overview" aria-label="Employee record summary">
        <article class="glass-card record-metric"><i class="record-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h10v18H7zM9 8h6M9 12h6M9 16h4"/></svg></i><div><span>PDS completion</span><strong><?= $pdsPercent ?>%</strong><div class="progress-bar"><div class="progress-bar-fill" data-target="<?= $pdsPercent ?>"></div></div></div></article>
        <article class="glass-card record-metric"><i class="record-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><div><span>Assigned tasks</span><strong><?= (int) ($taskStats['total'] ?? 0) ?></strong><small><?= (int) ($taskStats['done'] ?? 0) ?> completed</small></div></article>
        <article class="glass-card record-metric"><i class="record-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 17v.1"/></svg></i><div><span>Overdue tasks</span><strong><?= (int) ($taskStats['overdue'] ?? 0) ?></strong><small>Needs attention</small></div></article>
        <article class="glass-card record-metric"><i class="record-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8L12 3Z"/></svg></i><div><span>Accomplishments</span><strong><?= (int) ($accomplishmentStats['total'] ?? 0) ?></strong><small><?= (int) ($accomplishmentStats['approved'] ?? 0) ?> approved</small></div></article>
    </section>

    <section class="employee-record-tabs glass-card" aria-label="Employee profile details">
        <div class="employee-record-tablist" role="tablist" aria-label="Employee information categories">
            <button class="active" type="button" role="tab" aria-selected="true" aria-controls="employee-tab-personal" data-employee-tab="employee-tab-personal">Personal</button>
            <button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-employment" data-employee-tab="employee-tab-employment">Employment</button>
            <?php if ($isTeaching): ?><button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-teaching" data-employee-tab="employee-tab-teaching">Teaching assignment</button><?php endif; ?>
            <button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-qualifications" data-employee-tab="employee-tab-qualifications">Qualifications</button>
            <button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-account" data-employee-tab="employee-tab-account">Account</button>
            <button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-tasks" data-employee-tab="employee-tab-tasks">Tasks</button>
            <button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-accomplishments" data-employee-tab="employee-tab-accomplishments">Accomplishments</button>
            <button type="button" role="tab" aria-selected="false" aria-controls="employee-tab-movements" data-employee-tab="employee-tab-movements">Movement history</button>
        </div>

        <div class="employee-record-tabpanels" id="information">
        <section class="employee-record-section employee-record-tabpanel active" id="employee-tab-personal" role="tabpanel">
            <div class="record-section-heading"><i aria-hidden="true">01</i><div><span class="launcher-eyebrow">Identity and contact</span><h2>Personal information</h2></div></div>
            <dl class="record-detail-grid">
                <div><dt>Birth date</dt><dd><?= htmlspecialchars($value($snapshot['birth_date'] ?? null)) ?></dd></div><div><dt>Gender</dt><dd><?= htmlspecialchars($value($snapshot['sex'] ?? null)) ?></dd></div>
                <div><dt>Civil status</dt><dd><?= htmlspecialchars($value($snapshot['civil_status'] ?? null)) ?></dd></div><div><dt>Citizenship</dt><dd><?= htmlspecialchars($value($snapshot['citizenship'] ?? null)) ?></dd></div>
                <div><dt>Mobile number</dt><dd><?= htmlspecialchars($value($snapshot['mobile_no'] ?? null)) ?></dd></div><div><dt>Email address</dt><dd><?= htmlspecialchars($value($snapshot['email'] ?? null)) ?></dd></div>
                <div class="record-detail-wide"><dt>Residential address</dt><dd><?= htmlspecialchars($value($address)) ?></dd></div>
            </dl>
        </section>
        <section class="employee-record-section employee-record-tabpanel" id="employee-tab-employment" role="tabpanel" hidden>
            <div class="record-section-heading"><i aria-hidden="true">02</i><div><span class="launcher-eyebrow">Appointment</span><h2>Employment information</h2></div></div>
            <dl class="record-detail-grid">
                <div><dt>Date hired</dt><dd><?= htmlspecialchars($value($employee['date_hired'] ?? null)) ?></dd></div><div><dt>Personnel type</dt><dd><?= htmlspecialchars($value($snapshot['personnel_type'] ?? null)) ?></dd></div>
                <div><dt>Item number</dt><dd><?= htmlspecialchars($value($snapshot['item_number'] ?? null)) ?></dd></div><div><dt>Salary grade</dt><dd><?= htmlspecialchars($value($snapshot['salary_grade'] ?? null)) ?></dd></div>
            </dl>
        </section>
        <?php if ($isTeaching): ?>
        <section class="employee-record-section employee-record-tabpanel teaching-assignment-panel" id="employee-tab-teaching" role="tabpanel" hidden>
            <div class="record-section-heading"><i aria-hidden="true">T</i><div><span class="launcher-eyebrow">Teaching personnel</span><h2>Teaching assignment</h2></div><span class="record-chip">Current assignment</span></div>
            <dl class="record-detail-grid teaching-detail-grid">
                <div><dt>School ID code</dt><dd><?= htmlspecialchars($value($snapshot['school_id_code'] ?? null)) ?></dd></div>
                <div><dt>District</dt><dd><?= htmlspecialchars($value($snapshot['district'] ?? null)) ?></dd></div>
                <div class="record-detail-wide"><dt>Plantilla school station</dt><dd><?= htmlspecialchars($value($snapshot['plantilla_school_station'] ?? null)) ?></dd></div>
                <div class="record-detail-wide"><dt>Current school station</dt><dd><?= htmlspecialchars($value($snapshot['current_school_station'] ?? null)) ?></dd></div>
                <div><dt>Grade level/s taught</dt><dd><?= htmlspecialchars($value($snapshot['grade_levels_taught'] ?? null)) ?></dd></div>
                <div><dt>Specialization</dt><dd><?= htmlspecialchars($value($snapshot['specialization'] ?? null)) ?></dd></div>
                <div class="record-detail-wide teaching-subjects"><dt>Subject/s taught</dt><dd><?php if ($subjectsTaught): ?><?php foreach ($subjectsTaught as $subject): ?><span><?= htmlspecialchars($subject) ?></span><?php endforeach; ?><?php else: ?>Not provided<?php endif; ?></dd></div>
            </dl>
        </section>
        <?php endif; ?>
        <section class="employee-record-section employee-record-tabpanel" id="employee-tab-qualifications" role="tabpanel" hidden>
            <div class="record-section-heading"><i aria-hidden="true">03</i><div><span class="launcher-eyebrow">Qualifications</span><h2>Education and eligibility</h2></div></div>
            <dl class="record-detail-grid"><div><dt>Highest attainment</dt><dd><?= htmlspecialchars($value($highestEducation['level'] ?? null)) ?></dd></div><div><dt>Course</dt><dd><?= htmlspecialchars($value($highestEducation['degree_course'] ?? null)) ?></dd></div><div class="record-detail-wide"><dt>School</dt><dd><?= htmlspecialchars($value($highestEducation['school_name'] ?? null)) ?></dd></div><div class="record-detail-wide"><dt>Eligibility</dt><dd><?= htmlspecialchars($eligibilities ? implode(', ', array_column($eligibilities, 'eligibility_name')) : 'Not provided') ?></dd></div></dl>
        </section>
        <section class="employee-record-section employee-record-tabpanel" id="employee-tab-account" role="tabpanel" hidden>
            <div class="record-section-heading"><i aria-hidden="true">04</i><div><span class="launcher-eyebrow">Access and security</span><h2>Account overview</h2></div></div>
            <dl class="record-detail-grid"><div><dt>Account status</dt><dd><?= htmlspecialchars(ucfirst($value($snapshot['account_status'] ?? null))) ?></dd></div><div><dt>System role</dt><dd><?= htmlspecialchars($value($snapshot['role_name'] ?? null)) ?></dd></div><div><dt>Two-factor security</dt><dd><?= !empty($snapshot['two_factor_enabled']) ? 'Enabled' : 'Not enabled' ?></dd></div><div><dt>Last login</dt><dd><?= htmlspecialchars($value($snapshot['last_login'] ?? null, 'Never')) ?></dd></div></dl>
        </section>
        <section class="employee-record-section employee-record-tabpanel" id="employee-tab-tasks" role="tabpanel" hidden><div class="record-section-heading"><i aria-hidden="true">05</i><div><span class="launcher-eyebrow">Workload</span><h2>Recent tasks</h2></div></div><div class="record-list">
            <?php foreach ($recentRecords['tasks'] as $task): ?><a href="<?= BASE_URL ?>/tasks/<?= UrlId::encode((int) $task['id']) ?>"><span><strong><?= htmlspecialchars($task['title']) ?></strong><small><?= htmlspecialchars($task['priority']) ?> priority<?= $task['due_date'] ? ' · Due ' . htmlspecialchars($task['due_date']) : '' ?></small></span><em><?= htmlspecialchars($task['status']) ?></em></a><?php endforeach; ?>
            <?php if (!$recentRecords['tasks']): ?><p class="record-empty">No assigned tasks yet.</p><?php endif; ?>
        </div></section>
        <section class="employee-record-section employee-record-tabpanel" id="employee-tab-accomplishments" role="tabpanel" hidden><div class="record-section-heading"><i aria-hidden="true">06</i><div><span class="launcher-eyebrow">Performance records</span><h2>Recent accomplishments</h2></div><span class="record-chip"><?= (int) ($accomplishmentStats['pending'] ?? 0) ?> pending</span></div><div class="record-list">
            <?php foreach ($recentRecords['accomplishments'] as $item): ?><a href="<?= BASE_URL ?>/accomplishments/<?= UrlId::encode((int) $item['id']) ?>"><span><strong><?= htmlspecialchars($item['title']) ?></strong><small><?= htmlspecialchars($item['accomplishment_date']) ?></small></span><em><?= htmlspecialchars($item['status']) ?></em></a><?php endforeach; ?>
            <?php if (!$recentRecords['accomplishments']): ?><p class="record-empty">No accomplishment records yet.</p><?php endif; ?>
        </div></section>
        <section class="employee-record-section employee-record-tabpanel" id="employee-tab-movements" role="tabpanel" hidden>
            <div class="record-section-heading"><i aria-hidden="true">07</i><div><span class="launcher-eyebrow">Service record</span><h2>Personnel movement history</h2></div><?php if ($canManage): ?><a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/vacant-positions">Vacant positions</a><?php endif; ?></div>
            <?php if ($canManage): ?>
            <form class="movement-form" id="personnel-movement-form" data-endpoint="<?= BASE_URL ?>/employees/<?= UrlId::encode((int) $employee['id']) ?>/movement">
                <div class="form-row"><div class="form-group"><label>Movement type</label><select name="movement_type" id="movement-type" required><?php if ($isTeaching): ?><option value="School Transfer">School transfer</option><?php endif; ?><option value="Promotion">Promotion</option><option value="Historical Appointment">Add previous appointment</option></select></div><div class="form-group"><label id="movement-effective-label">Effective date</label><input type="date" name="effective_date" required></div></div>
                <?php if ($isTeaching): ?><div id="transfer-fields"><div class="form-group school-search-group movement-school-search"><label>Search destination school</label><input type="search" id="movement-school-search" placeholder="Type school name or School ID" autocomplete="off"><div class="school-search-results" id="movement-school-results" hidden></div><small id="movement-school-help">Select an official school to fill the new assignment.</small></div><div class="form-row"><div class="form-group"><label>Destination School ID</label><input name="school_id_code" placeholder="Official School ID" readonly></div><div class="form-group"><label>District</label><input name="district" readonly></div></div><div class="form-row"><div class="form-group"><label>New plantilla station</label><input name="plantilla_school_station"></div><div class="form-group"><label>New current station</label><input name="current_school_station"></div></div></div><?php endif; ?>
                <div id="promotion-fields" hidden><div class="form-row"><div class="form-group"><label>New position</label><select name="position_id" data-searchable-select data-search-placeholder="Search new position..."><option value="">Select position</option><?php foreach ($positions as $position): ?><option value="<?= (int) $position['id'] ?>"><?= htmlspecialchars($position['title']) ?><?= $position['salary_grade'] ? ' · ' . htmlspecialchars($position['salary_grade']) : '' ?></option><?php endforeach; ?></select></div><div class="form-group"><label>New item number</label><input name="item_number"></div><div class="form-group"><label>New salary grade</label><input name="salary_grade"></div></div></div>
                <div id="historical-fields" hidden><div class="movement-history-note"><strong>Previous appointment only</strong><small>This adds history without replacing the employee’s current position.</small></div><div class="form-row"><div class="form-group"><label>Previous position</label><select name="historical_position_id"><option value="">Select previous position</option><?php foreach ($positions as $position): ?><option value="<?= (int)$position['id'] ?>"><?= htmlspecialchars($position['title']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Previous item number</label><input name="historical_item_number"></div><div class="form-group"><label>Salary grade</label><input name="historical_salary_grade"></div></div><div class="form-row"><div class="form-group"><label>Previous office / station</label><input name="historical_station"></div><div class="form-group"><label>Appointment end date</label><input type="date" name="historical_end_date"></div></div><label class="movement-vacancy-check"><input type="checkbox" name="historical_mark_vacant" value="1"><span><strong>Add the previous item to Vacant Positions</strong><small>Use this only when the former item is still available.</small></span></label></div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" maxlength="500" placeholder="Appointment, transfer order, or other reference"></textarea></div><button class="btn btn-primary" type="submit">Save personnel movement</button>
            </form>
            <?php endif; ?>
            <div class="movement-timeline">
                <?php foreach ($movementHistory as $movement): $before = json_decode($movement['previous_data'], true) ?: []; $after = json_decode($movement['new_data'], true) ?: []; ?>
                <article><span class="movement-dot"></span><div><div class="movement-title"><strong><?= htmlspecialchars($movement['movement_type']) ?></strong><time><?= htmlspecialchars($movement['effective_date']) ?></time></div><p><?php if ($movement['movement_type'] !== 'Historical Appointment'): ?><?= htmlspecialchars(implode(' · ', array_filter(array_map('strval', $before)))) ?> <b>&rarr;</b> <?php endif; ?><?= htmlspecialchars(implode(' · ', array_filter(array_map('strval', $after)))) ?></p><?php if ($movement['remarks']): ?><small><?= htmlspecialchars($movement['remarks']) ?></small><?php endif; ?></div></article>
                <?php endforeach; ?>
                <?php if (!$movementHistory): ?><p class="record-empty">No transfer or promotion history recorded yet.</p><?php endif; ?>
            </div>
        </section>
        </div>
    </section>
</main>
<?php if ($canManage): ?><script>
const movementType = document.getElementById('movement-type');
const transferFields = document.getElementById('transfer-fields');
const promotionFields = document.getElementById('promotion-fields');
const historicalFields = document.getElementById('historical-fields');
const movementEffectiveLabel = document.getElementById('movement-effective-label');
const syncMovementFields = () => { const promotion = movementType.value === 'Promotion'; const historical = movementType.value === 'Historical Appointment'; if (transferFields) transferFields.hidden = promotion || historical; promotionFields.hidden = !promotion; historicalFields.hidden = !historical; movementEffectiveLabel.textContent = historical ? 'Appointment start date' : 'Effective date'; };
movementType.addEventListener('change', syncMovementFields); syncMovementFields();
<?php if ($isTeaching): ?>
const movementSchoolSearch = document.getElementById('movement-school-search'); const movementSchoolResults = document.getElementById('movement-school-results'); const movementSchoolHelp = document.getElementById('movement-school-help'); let movementSchools = [];
const movementForm = document.getElementById('personnel-movement-form'); const movementAssignment = {id:movementForm.querySelector('[name="school_id_code"]'),district:movementForm.querySelector('[name="district"]'),plantilla:movementForm.querySelector('[name="plantilla_school_station"]'),current:movementForm.querySelector('[name="current_school_station"]')};
const closeMovementSchools = () => { movementSchoolResults.hidden = true; movementSchoolResults.replaceChildren(); };
movementSchoolSearch.addEventListener('input', () => { const q=movementSchoolSearch.value.trim().toLocaleUpperCase(); movementAssignment.id.value=''; movementSchoolResults.replaceChildren(); const matches=movementSchools.filter(s=>q.length>=2&&`${s.n} ${s.i} ${s.d} ${s.p}`.toLocaleUpperCase().includes(q)).slice(0,50); matches.forEach(s=>{const b=document.createElement('button');b.type='button';b.className='school-result';const n=document.createElement('strong');n.textContent=s.n;const m=document.createElement('small');m.textContent=`${s.i} • ${s.d||s.m||s.p}`;b.append(n,m);b.addEventListener('click',()=>{movementSchoolSearch.value=s.n;movementAssignment.id.value=s.i;movementAssignment.district.value=s.d||'';movementAssignment.plantilla.value=s.n;movementAssignment.current.value=s.n;movementSchoolHelp.textContent=`${s.i} • ${s.d||s.m||s.p} selected`;closeMovementSchools();});movementSchoolResults.append(b);});movementSchoolResults.hidden=!matches.length; });
document.addEventListener('click',e=>{if(!e.target.closest('.movement-school-search'))closeMovementSchools();}); fetch('<?= BASE_URL ?>/assets/data/deped-schools.json').then(r=>r.json()).then(d=>movementSchools=d).catch(()=>movementSchoolHelp.textContent='School directory unavailable. Reload and try again.');
<?php endif; ?>
document.getElementById('personnel-movement-form').addEventListener('submit', async event => {
    event.preventDefault(); const confirmation=await Swal.fire({title:'Confirm personnel movement',text:'Enter your password before changing appointment or position records.',input:'password',inputLabel:'Current password',inputAttributes:{autocomplete:'current-password'},showCancelButton:true,confirmButtonText:'Confirm and save',inputValidator:value=>!value?'Password is required.':undefined}); if(!confirmation.isConfirmed)return; const button = event.currentTarget.querySelector('[type="submit"]'); button.disabled = true;
    const data = new FormData(event.currentTarget); data.append('csrf_token', HRIS.getCsrfToken()); data.append('confirmation_password',confirmation.value);
    try { const response = await fetch(event.currentTarget.dataset.endpoint, {method:'POST',body:data}); const result = await response.json(); HRIS.flash(result.message || result.error || 'Unable to save movement.', result.success ? 'success' : 'error'); if (result.success) setTimeout(() => location.reload(), 500); }
    catch (_) { HRIS.flash('Unable to save personnel movement.', 'error'); } finally { button.disabled = false; }
});
</script><?php endif; ?>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
