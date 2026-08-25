<?php
/** @var array $account */
/** @var array|null $personalInfo */
/** @var array|null $photo */
/** @var int $pdsPercent */
/** @var string $qrDataUri */
/** @var array $workProfile */
/** @var array $addresses */
/** @var array|null $questionnaire */
/** @var array $positions */
/** @var array|null $highestEducation */
/** @var array $eligibilities */
/** @var bool $isUnlocked */
require MODULES_PATH . '/shared/views/header.php';
$info = $personalInfo ?? [];
$residential = $addresses['Residential'] ?? [];
$work = $workProfile ?? ['personnel_type' => 'Non-Teaching'];
$fullName = trim(($info['first_name'] ?? '') . ' ' . ($info['middle_name'] ?? '') . ' ' . ($info['surname'] ?? '')) ?: Auth::displayName();
?>
<?php $recordLockScope = 'profile'; require MODULES_PATH . '/shared/views/record_lock.php'; ?>
<div class="profile-layout" data-record-protected data-record-unlocked="<?= $isUnlocked ? 'true' : 'false' ?>">
    <aside class="profile-summary">
        <section class="employee-id-card" aria-label="Employee identification card">
            <div class="employee-id-pattern"></div>
            <header class="employee-id-header"><span class="employee-id-mark">P</span><span><strong>Project PUNLA</strong><small>Human Resource Management</small></span></header>
            <div class="employee-id-body">
                <form class="profile-photo-form" id="profile-photo-form">
                    <label class="profile-photo-control" for="profile-photo-input" title="Change profile picture">
                        <?php if ($photo): ?>
                            <img class="profile-photo" id="profile-photo-preview" src="<?= BASE_URL ?>/photo/<?= UrlId::encode((int) $photo['id']) ?>" alt="<?= htmlspecialchars($fullName) ?>">
                        <?php else: ?>
                            <span class="profile-photo profile-photo-placeholder" id="profile-photo-preview"><?= htmlspecialchars(strtoupper(substr($fullName, 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="profile-photo-edit" aria-hidden="true">&#128247;</span>
                    </label>
                    <input type="file" id="profile-photo-input" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="profile-photo-button" id="profile-photo-button">Change photo</button>
                    <small>JPG, PNG or WebP &middot; up to 5 MB</small>
                </form>
                <div class="employee-id-identity">
                    <span class="employee-id-label">Employee</span>
                    <h1><?= htmlspecialchars($fullName) ?></h1>
                    <p><?= htmlspecialchars($account['position_title'] ?? 'Position not assigned') ?></p>
                    <strong class="employee-id-number"><?= htmlspecialchars($account['employee_number']) ?></strong>
                </div>
            </div>
            <div class="employee-id-footer">
                <div><span>Department</span><strong><?= htmlspecialchars($account['department_name'] ?? 'Not assigned') ?></strong><small><?= htmlspecialchars($account['employment_status']) ?> employee</small></div>
                <div class="employee-id-qr"><img src="<?= htmlspecialchars($qrDataUri) ?>" alt="QR code for employee <?= htmlspecialchars($account['employee_number']) ?>"><small>Scan to identify</small></div>
            </div>
        </section>
        <p class="employee-id-privacy"><span>&#128274;</span> QR contains your employee number and public work details only. Internal system user IDs are never included.</p>
    </aside>

    <div class="profile-main">
        <header class="profile-page-heading">
            <div><span class="launcher-eyebrow">Employee workspace</span><h1>My profile</h1><p>Keep your personal, employment, and account information accurate and up to date.</p></div>
            <span class="profile-completeness"><strong><?= (int) $pdsPercent ?>%</strong><small>PDS complete</small></span>
        </header>
        <section class="profile-pds-card glass">
            <div class="profile-pds-icon">&#128196;</div>
            <div class="profile-pds-copy"><span class="launcher-eyebrow">Personal Data Sheet</span><h2>CS Form No. 212</h2><p>Complete your family, education, work history, eligibility, and other official records.</p></div>
            <div class="profile-pds-progress"><strong><?= (int) $pdsPercent ?>%</strong><span>complete</span></div>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/pds">Manage PDS &rarr;</a>
        </section>

        <section class="profile-security-card glass">
            <div class="profile-security-icon">&#128737;</div>
            <div><span class="launcher-eyebrow">Account security</span><h2>Two-factor authentication</h2><p><?= !empty($account['two_factor_enabled']) ? 'Your account is protected with an authenticator app.' : 'Add an extra verification step when signing in.' ?></p></div>
            <span class="badge <?= !empty($account['two_factor_enabled']) ? 'badge-done' : 'badge-open' ?>"><?= !empty($account['two_factor_enabled']) ? 'Enabled' : 'Not enabled' ?></span>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>/profile/security">Manage security &rarr;</a>
        </section>

        <section class="glass-card profile-form-card">
            <div class="profile-section-heading"><div><span class="launcher-eyebrow">Personal details</span><h2>Profile information</h2></div><span id="profile-save-state"></span></div>
            <form id="profile-form">
                <h3>Personal information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Employee number</label><input name="employee_number" value="<?= htmlspecialchars($account['employee_number']) ?>" required maxlength="30" placeholder="e.g. 123456"></div>
                    <div class="form-group"><label>First name</label><input name="first_name" value="<?= htmlspecialchars($info['first_name'] ?? '') ?>" required maxlength="100"></div>
                    <div class="form-group"><label>Middle name</label><input name="middle_name" value="<?= htmlspecialchars($info['middle_name'] ?? '') ?>" maxlength="100"></div>
                    <div class="form-group"><label>Surname</label><input name="surname" value="<?= htmlspecialchars($info['surname'] ?? '') ?>" required maxlength="100"></div>
                    <div class="form-group" style="max-width:120px;"><label>Suffix</label><input name="name_extension" value="<?= htmlspecialchars($info['name_extension'] ?? '') ?>" maxlength="20" placeholder="Jr."></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Birth date</label><input type="date" name="birth_date" value="<?= htmlspecialchars($info['birth_date'] ?? '') ?>"></div>
                    <div class="form-group"><label>Birth place</label><input name="birth_place" value="<?= htmlspecialchars($info['birth_place'] ?? '') ?>" maxlength="150"></div>
                    <div class="form-group"><label>Sex</label><select name="sex"><option value="">Select</option><?php foreach (['Male','Female'] as $value): ?><option value="<?= $value ?>" <?= ($info['sex'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Civil status</label><select name="civil_status"><option value="">Select</option><?php foreach (['Single','Married','Widowed','Separated','Others'] as $value): ?><option value="<?= $value ?>" <?= ($info['civil_status'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>PWD status</label><select name="pwd_status"><option value="0" <?= empty($questionnaire['q40_pwd']) ? 'selected' : '' ?>>No</option><option value="1" <?= !empty($questionnaire['q40_pwd']) ? 'selected' : '' ?>>Yes</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Citizenship</label><input name="citizenship" value="<?= htmlspecialchars($info['citizenship'] ?? '') ?>" maxlength="50"></div>
                    <div class="form-group"><label>Mobile number</label><input type="tel" name="mobile_no" value="<?= htmlspecialchars($info['mobile_no'] ?? '') ?>" maxlength="30"></div>
                    <div class="form-group"><label>Telephone</label><input type="tel" name="telephone_no" value="<?= htmlspecialchars($info['telephone_no'] ?? '') ?>" maxlength="30"></div>
                    <div class="form-group"><label>Personal email</label><input type="email" name="email" value="<?= htmlspecialchars($info['email'] ?? '') ?>" maxlength="150"></div>
                </div>

                <h3>Complete residential address</h3>
                <div class="profile-address-fields">
                    <div class="form-group address-house"><label>House no. / lot / block no.</label><input name="residential[house_block_lot]" value="<?= htmlspecialchars($residential['house_block_lot'] ?? '') ?>" maxlength="150" placeholder="e.g. Lot 5 Block 3" autocomplete="address-line1"></div>
                    <div class="form-group"><label>Street / sitio</label><input name="residential[street]" value="<?= htmlspecialchars($residential['street'] ?? '') ?>" maxlength="150" placeholder="e.g. Rizal Street" autocomplete="address-line2"></div>
                    <div class="form-group"><label>Subdivision / village</label><input name="residential[subdivision_village]" value="<?= htmlspecialchars($residential['subdivision_village'] ?? '') ?>" maxlength="150" placeholder="e.g. Mabuhay Village"></div>
                    <div class="form-group"><label>Barangay</label><input name="residential[barangay]" value="<?= htmlspecialchars($residential['barangay'] ?? '') ?>" maxlength="100" placeholder="e.g. Poblacion"></div>
                    <div class="form-group"><label>City / municipality</label><input name="residential[city_municipality]" value="<?= htmlspecialchars($residential['city_municipality'] ?? '') ?>" maxlength="100" placeholder="e.g. Baler" autocomplete="address-level2"></div>
                    <div class="form-group"><label>Province</label><input name="residential[province]" value="<?= htmlspecialchars($residential['province'] ?? '') ?>" maxlength="100" placeholder="e.g. Aurora" autocomplete="address-level1"></div>
                    <div class="form-group address-zip"><label>ZIP code</label><input name="residential[zip_code]" value="<?= htmlspecialchars($residential['zip_code'] ?? '') ?>" maxlength="10" inputmode="numeric" placeholder="e.g. 3200" autocomplete="postal-code"></div>
                </div>

                <h3>Employment information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Personnel classification</label><select name="personnel_type" id="personnel-type" required><option value="Teaching" <?= ($work['personnel_type'] ?? '') === 'Teaching' ? 'selected' : '' ?>>Teaching</option><option value="Non-Teaching" <?= ($work['personnel_type'] ?? 'Non-Teaching') === 'Non-Teaching' ? 'selected' : '' ?>>Non-Teaching</option></select></div>
                    <div class="form-group"><label>Position / designation</label><select name="position_id" data-searchable-select data-search-placeholder="Search government positions..."><option value="">Select position</option><?php foreach ($positions as $position): ?><option value="<?= (int) $position['id'] ?>" <?= (int) ($account['position_id'] ?? 0) === (int) $position['id'] ? 'selected' : '' ?>><?= htmlspecialchars($position['title']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Item number</label><input name="item_number" value="<?= htmlspecialchars($work['item_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>Salary grade</label><input name="salary_grade" value="<?= htmlspecialchars($work['salary_grade'] ?? '') ?>" placeholder="e.g. SG-11"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Appointment type</label><select name="employment_status"><?php foreach (['Regular','Casual','Contractual','Job Order','Probationary'] as $status): ?><option value="<?= $status ?>" <?= ($account['employment_status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Original appointment date</label><input type="date" name="date_hired" value="<?= htmlspecialchars($account['date_hired'] ?? '') ?>"></div>
                </div>

                <div id="teaching-fields" class="teaching-fields">
                    <div class="teaching-fields-heading"><span>&#127979;</span><div><strong>Teaching and plantilla assignment</strong><small>Required for teaching personnel</small></div></div>
                    <div class="form-row">
                        <div class="form-group"><label>School ID code</label><input name="school_id_code" value="<?= htmlspecialchars($work['school_id_code'] ?? '') ?>" maxlength="30" placeholder="e.g. 300001"></div>
                        <div class="form-group"><label>Plantilla school station</label><input name="plantilla_school_station" value="<?= htmlspecialchars($work['plantilla_school_station'] ?? '') ?>" placeholder="School where plantilla item is assigned"></div>
                        <div class="form-group"><label>Current school station</label><input name="current_school_station" value="<?= htmlspecialchars($work['current_school_station'] ?? '') ?>" placeholder="Current teaching/detail station"></div>
                        <div class="form-group"><label>District</label><input name="district" value="<?= htmlspecialchars($work['district'] ?? '') ?>" placeholder="e.g. District I"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Grade level/s taught</label><input name="grade_levels_taught" value="<?= htmlspecialchars($work['grade_levels_taught'] ?? '') ?>"></div>
                        <div class="form-group"><label>Specialization</label><input name="specialization" value="<?= htmlspecialchars($work['specialization'] ?? '') ?>" placeholder="e.g. Math, English, Science"></div>
                        <div class="form-group" style="flex:2;"><label>Subject/s taught</label><input name="subjects_taught" value="<?= htmlspecialchars($work['subjects_taught'] ?? '') ?>" placeholder="Comma-separated list of subjects"></div>
                    </div>
                </div>

                <h3>Education &amp; eligibility</h3>
                <div class="profile-readonly-grid">
                    <div><span>Highest educational attainment</span><strong><?= htmlspecialchars($highestEducation['level'] ?? 'Not recorded') ?></strong></div>
                    <div><span>Field of study / course</span><strong><?= htmlspecialchars($highestEducation['degree_course'] ?? 'Not recorded') ?></strong></div>
                    <div><span>CSEE / eligibility</span><strong><?= htmlspecialchars($eligibilities ? implode(', ', $eligibilities) : 'Not recorded') ?></strong></div>
                    <a href="<?= BASE_URL ?>/pds">Update education and eligibility in PDS &rarr;</a>
                </div>

                <h3>System account</h3>
                <div class="form-row">
                    <div class="form-group"><label>Username</label><input name="username" value="<?= htmlspecialchars($account['username']) ?>" required maxlength="60"></div>
                    <div class="form-group"><label>Account email</label><input type="email" name="account_email" value="<?= htmlspecialchars($account['account_email']) ?>" required maxlength="150"></div>
                </div>
                <div class="profile-form-actions"><button class="btn btn-primary" type="submit">Save changes</button></div>
            </form>
        </section>
    </div>
</div>
<script>
const photoForm = document.getElementById('profile-photo-form');
const photoInput = document.getElementById('profile-photo-input');
document.getElementById('profile-photo-button').addEventListener('click', () => photoInput.click());
photoInput.addEventListener('change', async () => {
    if (!photoInput.files.length) return;
    const file = photoInput.files[0];
    if (file.size > 5 * 1024 * 1024) {
        HRIS.flash('Profile picture must not exceed 5 MB.', 'error');
        photoInput.value = '';
        return;
    }
    const formData = new FormData(photoForm);
    formData.append('csrf_token', HRIS.getCsrfToken());
    const button = document.getElementById('profile-photo-button');
    button.disabled = true;
    button.textContent = 'Uploading...';
    try {
        const response = await fetch(`${window.BASE_URL}/profile/photo`, {method: 'POST', body: formData});
        const result = await response.json();
        HRIS.flash(result.message || result.error || 'Unable to upload picture.', result.success ? 'success' : 'error');
        if (result.success) setTimeout(() => window.location.reload(), 400);
    } catch (error) {
        HRIS.flash('Unable to upload picture.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'Change photo';
        photoInput.value = '';
    }
});

const personnelType = document.getElementById('personnel-type');
const teachingFields = document.getElementById('teaching-fields');
const updatePersonnelFields = () => {
    const teaching = personnelType.value === 'Teaching';
    teachingFields.hidden = !teaching;
    teachingFields.querySelectorAll('[name="school_id_code"], [name="plantilla_school_station"], [name="current_school_station"]').forEach(input => input.required = teaching);
};
personnelType.addEventListener('change', updatePersonnelFields);
updatePersonnelFields();

document.getElementById('profile-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = event.currentTarget.querySelector('[type="submit"]');
    button.disabled = true;
    const formData = new FormData(event.currentTarget);
    formData.append('csrf_token', HRIS.getCsrfToken());
    try {
        const response = await fetch(`${window.BASE_URL}/profile/update`, {method: 'POST', body: formData});
        const result = await response.json();
        HRIS.flash(result.message || result.error || 'Unable to save profile.', result.success ? 'success' : 'error');
        if (result.success) setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        HRIS.flash('Unable to save profile.', 'error');
    } finally {
        button.disabled = false;
    }
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
