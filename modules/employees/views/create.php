<?php
/** @var array $departments */
/** @var array $positions */
/** @var array $roles */
/** @var array $statuses */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <h2 style="margin-top:0;">Add Employee</h2>
    <p style="color:var(--text-muted); margin-top:-0.5rem;">Create the employee record and their login account in one step.</p>
    <form id="create-employee-form">
        <div class="form-row">
            <div class="form-group"><label>Employee Number</label><input name="employee_number" required placeholder="e.g. EMP-0002"></div>
            <div class="form-group"><label>Date Hired</label><input type="date" name="date_hired"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Department</label>
                <select name="department_id">
                    <option value="">--</option>
                    <?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Position</label>
                <select name="position_id" data-searchable-select data-search-placeholder="Search government positions...">
                    <option value="">Select position</option>
                    <?php foreach ($positions as $p): ?><option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Employment Status</label>
                <select name="employment_status">
                    <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $s === 'Probationary' ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <h3 style="margin:1.5rem 0 0.75rem;">Login Account</h3>
        <div class="form-row">
            <div class="form-group"><label>Username</label><input name="username" required maxlength="60" autocomplete="username"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required autocomplete="email"></div>
            <div class="form-group"><label>Temporary Password</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Role</label>
                <select name="role_id" id="account-role" required>
                    <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>" data-role-name="<?= htmlspecialchars($role['name'], ENT_QUOTES) ?>" <?= $role['name'] === 'Employee' ? 'selected' : '' ?>><?= htmlspecialchars($role['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group account-scope-field" id="district-scope-field" hidden><label>Assigned district</label>
                <select name="scope_district"><option value="">Select district</option><?php foreach (['BALER','CASIGURAN','DILASAG','DINALUNGAN','DINGALAN','DIPACULAO NORTH','DIPACULAO SOUTH','MARIA AURORA EAST','MARIA AURORA WEST','SAN LUIS'] as $district): ?><option value="<?= $district ?>"><?= $district ?></option><?php endforeach; ?></select>
                <small>PSDS and SDC can only view personnel assigned to this district.</small>
            </div>
            <div class="form-group account-scope-field" id="office-scope-field" hidden><label>Assigned office</label>
                <select name="scope_department_id"><option value="">Select office</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select>
                <small>Unit Heads can only view personnel assigned to this office.</small>
            </div>
            <div class="form-group account-scope-field school-search-group" id="school-scope-field" hidden><label>Assigned school</label>
                <input type="search" id="account-school-search" placeholder="Search school name or School ID" autocomplete="off"><input type="hidden" name="scope_school_id_code" id="account-school-id"><div class="school-search-results" id="account-school-results" hidden></div>
                <small id="account-school-help">Principals can only view personnel assigned to the selected School ID.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create Employee &amp; Account</button>
    </form>
</div>
<script>
const accountRole = document.getElementById('account-role');
const districtScopeField = document.getElementById('district-scope-field');
const officeScopeField = document.getElementById('office-scope-field');
const schoolScopeField = document.getElementById('school-scope-field');
const accountSchoolSearch = document.getElementById('account-school-search');
const accountSchoolId = document.getElementById('account-school-id');
const accountSchoolResults = document.getElementById('account-school-results');
const accountSchoolHelp = document.getElementById('account-school-help');
let accountSchools = [];
const updateAccountScope = () => {
    const role = accountRole.selectedOptions[0]?.dataset.roleName || '';
    const needsDistrict = role === 'PSDS' || role === 'SDC';
    const needsOffice = role === 'Unit Head';
    const needsSchool = role === 'Principal';
    districtScopeField.hidden = !needsDistrict;
    officeScopeField.hidden = !needsOffice;
    schoolScopeField.hidden = !needsSchool;
    districtScopeField.querySelector('select').required = needsDistrict;
    officeScopeField.querySelector('select').required = needsOffice;
    accountSchoolSearch.required = needsSchool;
    accountSchoolId.required = needsSchool;
};
accountRole.addEventListener('change', updateAccountScope);
updateAccountScope();

const closeAccountSchoolResults = () => { accountSchoolResults.hidden = true; accountSchoolResults.replaceChildren(); };
const showAccountSchoolResults = () => {
    const query = accountSchoolSearch.value.trim().toLocaleUpperCase();
    accountSchoolId.value = '';
    const matches = accountSchools.filter(school => query.length >= 2 && `${school.n} ${school.i} ${school.d} ${school.p}`.toLocaleUpperCase().includes(query)).slice(0, 50);
    accountSchoolResults.replaceChildren();
    matches.forEach(school => {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'school-result';
        const name = document.createElement('strong'); name.textContent = school.n;
        const meta = document.createElement('small'); meta.textContent = `${school.i} • ${school.d || school.m || school.p}`;
        button.append(name, meta);
        button.addEventListener('click', () => {
            accountSchoolSearch.value = school.n;
            accountSchoolId.value = school.i;
            accountSchoolHelp.textContent = `${school.i} • ${school.d || school.m || school.p} — Principal access will be limited to this school.`;
            closeAccountSchoolResults();
        });
        accountSchoolResults.append(button);
    });
    accountSchoolResults.hidden = matches.length === 0;
};
accountSchoolSearch.addEventListener('input', showAccountSchoolResults);
accountSchoolSearch.addEventListener('focus', showAccountSchoolResults);
document.addEventListener('click', event => { if (!event.target.closest('#school-scope-field')) closeAccountSchoolResults(); });
fetch('<?= BASE_URL ?>/assets/data/deped-schools.json').then(response => response.json()).then(data => { accountSchools = data; }).catch(() => { accountSchoolHelp.textContent = 'School directory unavailable. Reload the page and try again.'; });

document.getElementById('create-employee-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('csrf_token', HRIS.getCsrfToken());
    const res = await fetch(`${window.BASE_URL}/employees/store`, { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) {
        window.location.href = `${window.BASE_URL}/employees/${result.employee_token}`;
    } else {
        HRIS.flash(result.error || 'Failed to add employee.', 'error');
    }
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
