<?php /** @var string|null $error */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken()) ?>">
<title>Complete your profile &mdash; HRMS</title>
<script>(function(){try{var s=JSON.parse(localStorage.getItem('hrms-appearance')||'{}');var m=s.mode||'system';var d=m==='dark'||(m==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.dataset.theme=d?'dark':'light';}catch(e){}})();</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="personnel-setup-page">
<main class="personnel-setup-shell">
    <section class="personnel-setup-intro">
        <div class="setup-brand"><span>P</span><div><strong>Project PUNLA</strong><small>HRMS Workspace</small></div></div>
        <div class="setup-intro-copy">
            <span class="setup-step">Account setup <b>1 of 2</b></span>
            <h1>Let’s personalize your workspace.</h1>
            <p>Tell us how you serve so HRMS can show the right profile fields and tools for your role.</p>
            <div class="setup-benefit-list">
                <span><i>&#10003;</i> Role-specific profile fields</span>
                <span><i>&#10003;</i> Relevant HR tools and workflows</span>
                <span><i>&#10003;</i> Details can be updated later</span>
            </div>
        </div>
        <div class="setup-privacy"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span><strong>Private and protected</strong><small>Only authorized HR personnel can access official employment records.</small></span></div>
    </section>

    <section class="personnel-setup-form glass-strong">
        <div class="setup-form-heading"><span class="launcher-eyebrow">Welcome, <?= htmlspecialchars(explode(' ', Auth::displayName())[0]) ?></span><h2>What is your personnel type?</h2><p>Choose the classification that matches your current appointment.</p></div>
        <?php if ($error): ?><div class="alert alert-error" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/personnel-setup" id="personnel-setup-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
            <div class="personnel-choice-grid">
                <label class="personnel-choice">
                    <input type="radio" name="personnel_type" value="Teaching" required>
                    <span class="personnel-choice-check">&#10003;</span><span class="personnel-choice-icon"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 13h6M9 17h6"/></svg></span>
                    <span><strong>Teaching Personnel</strong><small>Teachers and school-based instructional roles</small></span>
                </label>
                <label class="personnel-choice">
                    <input type="radio" name="personnel_type" value="Non-Teaching" required>
                    <span class="personnel-choice-check">&#10003;</span><span class="personnel-choice-icon"><svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="3"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/></svg></span>
                    <span><strong>Non-Teaching Personnel</strong><small>Administrative, support, and non-instructional roles</small></span>
                </label>
            </div>

            <div class="setup-teaching-fields" id="setup-teaching-fields" hidden>
                <div class="setup-section-title"><span><svg viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11.5V16c3 3 9 3 12 0v-4.5M22 9v6"/></svg></span><div><strong>Teaching assignment</strong><small>Complete your current school and plantilla details.</small></div></div>
                <div class="school-picker" id="school-picker">
                    <div class="school-picker-heading"><span class="school-picker-icon"><svg viewBox="0 0 24 24"><path d="M4 21h16M6 21V8l6-4 6 4v13M9 12h6M9 16h6"/></svg></span><span><strong>Find your assigned school</strong><small>Choose an area first, then search the official directory.</small></span><b>Auto-fill</b></div>
                    <div class="form-row">
                        <div class="form-group"><label for="school-province"><span>1</span> Province / school division</label><select id="school-province"><option value="">Loading school directory...</option></select></div>
                        <div class="form-group school-search-group"><label for="school-search"><span>2</span> Search and select school</label><input id="school-search" type="search" placeholder="Select a province or division first" autocomplete="off" disabled><div class="school-search-results" id="school-search-results" hidden></div></div>
                    </div>
                    <p class="school-picker-help" id="school-picker-help"><span aria-hidden="true">i</span><span>Select the province or school division, then type the school name or its six-digit School ID.</span></p>
                </div>
                <div class="assignment-details-heading"><span>Assignment details</span><small>Automatically completed after school selection</small></div>
                <div class="form-row">
                    <div class="form-group"><label>School ID code</label><input name="school_id_code" maxlength="30" placeholder="Auto-filled after selecting a school"></div>
                    <div class="form-group"><label>District</label><input name="district" maxlength="120" placeholder="Auto-filled when available"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Plantilla school station</label><input name="plantilla_school_station" maxlength="180" placeholder="School where your plantilla item is assigned"></div>
                    <div class="form-group"><label>Current school station</label><input name="current_school_station" maxlength="180" placeholder="Current teaching or detail station"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Grade level/s taught</label><input name="grade_levels_taught" maxlength="180" placeholder="e.g. Grade 7, Grade 8"></div>
                    <div class="form-group"><label>Specialization</label><input name="specialization" maxlength="180" placeholder="e.g. Mathematics"></div>
                </div>
                <div class="form-group"><label>Subject/s taught</label><input name="subjects_taught" placeholder="Comma-separated list of subjects"></div>
            </div>

            <button class="btn btn-primary setup-continue" type="submit" disabled>Continue to HRMS <span>&rarr;</span></button>
        </form>
        <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="onboarding-logout" type="submit">Not you? Sign out</button></form>
    </section>
</main>
<script>
const choices = [...document.querySelectorAll('[name="personnel_type"]')];
const teachingFields = document.getElementById('setup-teaching-fields');
const continueButton = document.querySelector('.setup-continue');
const requiredTeaching = teachingFields.querySelectorAll('[name="school_id_code"], [name="plantilla_school_station"], [name="current_school_station"]');
const updateSetup = () => {
    const selected = choices.find(choice => choice.checked)?.value;
    const teaching = selected === 'Teaching';
    teachingFields.hidden = !teaching;
    requiredTeaching.forEach(input => input.required = teaching);
    continueButton.disabled = !selected;
    document.querySelectorAll('.personnel-choice').forEach(card => card.classList.toggle('selected', card.querySelector('input').checked));
};
choices.forEach(choice => choice.addEventListener('change', updateSetup));
updateSetup();

const provinceSelect = document.getElementById('school-province');
const schoolSearch = document.getElementById('school-search');
const schoolResults = document.getElementById('school-search-results');
const schoolHelp = document.getElementById('school-picker-help');
const assignmentFields = {
    id: teachingFields.querySelector('[name="school_id_code"]'),
    district: teachingFields.querySelector('[name="district"]'),
    plantilla: teachingFields.querySelector('[name="plantilla_school_station"]'),
    current: teachingFields.querySelector('[name="current_school_station"]')
};
let schoolDirectory = [];
let schoolsInProvince = [];
const normalizeSearch = value => String(value || '').toLocaleUpperCase().trim();
const hideSchoolResults = () => { schoolResults.hidden = true; schoolResults.replaceChildren(); };
const chooseSchool = school => {
    schoolSearch.value = school.n;
    assignmentFields.id.value = school.i;
    assignmentFields.district.value = school.d || '';
    assignmentFields.plantilla.value = school.n;
    assignmentFields.current.value = school.n;
    schoolHelp.lastElementChild.textContent = `${school.i} • ${school.d || school.m || school.p} — assignment fields filled automatically. You may edit the current station if you are detailed elsewhere.`;
    document.getElementById('school-picker').classList.add('has-selection');
    Object.values(assignmentFields).forEach(field => { field.classList.add('auto-filled'); field.dispatchEvent(new Event('input', { bubbles: true })); });
    hideSchoolResults();
};
const showSchoolResults = () => {
    const query = normalizeSearch(schoolSearch.value);
    const matches = schoolsInProvince.filter(school => !query || normalizeSearch(`${school.n} ${school.i} ${school.d} ${school.m}`).includes(query)).slice(0, 60);
    schoolResults.replaceChildren();
    if (!matches.length) {
        const empty = document.createElement('div'); empty.className = 'school-result-empty'; empty.textContent = 'No matching school found. You can still enter the assignment fields manually.'; schoolResults.append(empty);
    } else {
        matches.forEach(school => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'school-result';
            const name = document.createElement('strong'); name.textContent = school.n;
            const meta = document.createElement('small'); meta.textContent = `${school.i} • ${school.d || school.m || school.p}`;
            button.append(name, meta); button.addEventListener('click', () => chooseSchool(school)); schoolResults.append(button);
        });
    }
    schoolResults.hidden = false;
};
provinceSelect.addEventListener('change', () => {
    schoolsInProvince = schoolDirectory.filter(school => school.p === provinceSelect.value);
    schoolSearch.value = ''; schoolSearch.disabled = !provinceSelect.value;
    schoolSearch.placeholder = provinceSelect.value ? `Search ${schoolsInProvince.length.toLocaleString()} schools...` : 'Select a province or division first';
    schoolHelp.lastElementChild.textContent = provinceSelect.value ? `${schoolsInProvince.length.toLocaleString()} schools available in ${provinceSelect.value}. Search by school name or ID.` : 'Select the province or school division, then type the school name or its six-digit School ID.';
    document.getElementById('school-picker').classList.remove('has-selection');
    hideSchoolResults();
});
schoolSearch.addEventListener('input', showSchoolResults);
schoolSearch.addEventListener('focus', showSchoolResults);
document.addEventListener('click', event => { if (!event.target.closest('#school-picker')) hideSchoolResults(); });
fetch('<?= BASE_URL ?>/assets/data/deped-schools.json')
    .then(response => { if (!response.ok) throw new Error('School directory unavailable'); return response.json(); })
    .then(data => {
        schoolDirectory = data;
        const provinces = [...new Set(data.map(school => school.p).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        provinceSelect.replaceChildren(new Option('Select province / school division', ''), ...provinces.map(name => new Option(name, name)));
        schoolHelp.lastElementChild.textContent = 'Select the province or school division, then type the school name or its six-digit School ID.';
    })
    .catch(() => {
        provinceSelect.replaceChildren(new Option('Directory unavailable — enter manually', ''));
        schoolHelp.lastElementChild.textContent = 'The school directory could not be loaded. Please enter the School ID, district, and station fields manually.';
    });
</script>
</body>
</html>
