<?php
/** @var int $employeeId */
/** @var array|null $personalInfo */
/** @var array $addresses */
/** @var array|null $familyBackground */
/** @var array $children */
/** @var array $education */
/** @var array $eligibility */
/** @var array $workExperience */
/** @var array $voluntaryWork */
/** @var array $learningDevelopment */
/** @var array $otherInfo */
/** @var array|null $questionnaire */
/** @var array $characterReferences */
/** @var array $completion */
/** @var int $completionPercent */
/** @var bool $isUnlocked */

function pv($arr, $key, $default = '') { return htmlspecialchars((string) ($arr[$key] ?? $default)); }

require MODULES_PATH . '/shared/views/header.php';
?>
<?php $recordLockScope = 'pds'; require MODULES_PATH . '/shared/views/record_lock.php'; ?>
<div data-record-protected data-record-unlocked="<?= $isUnlocked ? 'true' : 'false' ?>">
<?php if (!empty($_GET['required'])): ?>
    <div class="glass-card" style="border-color: rgba(220, 38, 38, 0.35);">
        <div style="display:flex; gap:0.75rem; align-items:flex-start;">
            <span style="font-size:1.3rem;">&#9888;</span>
            <div>
                <strong>Please complete your Personal Data Sheet to continue.</strong>
                <p style="margin:0.3rem 0 0; color:var(--text-secondary); font-size:0.85rem;">
                    You need to encode at least <?= PDS_MIN_COMPLETION_PERCENT ?>% of your PDS (roughly one full section) before you can access the rest of the system.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h2 style="margin:0;">Personal Data Sheet</h2>
            <span style="color:var(--text-muted); font-size:0.85rem;">CS Form No. 212</span>
        </div>
        <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/pds/print/<?= UrlId::encode($employeeId) ?>" target="_blank">Print / Save PDF</a>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem; margin-top:1rem;" aria-live="polite">
        <div class="progress-bar" style="flex:1;" role="progressbar" aria-label="PDS completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $completionPercent ?>"><div id="pds-completion-bar" class="progress-bar-fill" data-target="<?= $completionPercent ?>"></div></div>
        <strong id="pds-completion-text"><?= $completionPercent ?>% Complete</strong>
    </div>

    <aside class="pds-na-instruction" aria-label="Important form instruction">
        <span class="pds-na-icon" aria-hidden="true">i</span>
        <div>
            <strong>Do not leave applicable text fields blank</strong>
            <p>If you do not have the requested information or it does not apply to you, enter <b>N/A</b>. Leave date, number, and selection fields blank when they do not apply.</p>
        </div>
    </aside>

    <div class="tabs" style="margin-top:1.25rem; margin-bottom:0;">
        <button data-tab="tab-personal" class="active">Personal</button>
        <button data-tab="tab-family">Family</button>
        <button data-tab="tab-education">Education</button>
        <button data-tab="tab-eligibility">Eligibility</button>
        <button data-tab="tab-work">Experience</button>
        <button data-tab="tab-voluntary">Voluntary Work</button>
        <button data-tab="tab-training">Training</button>
        <button data-tab="tab-other">Other Info</button>
        <button data-tab="tab-questionnaire">Questionnaire</button>
        <button data-tab="tab-references">References</button>
    </div>
</div>

<!-- I. PERSONAL INFO + ADDRESSES -->
<div id="tab-personal" class="tab-panel glass-card">
    <form class="ajax-section-form" data-endpoint="<?= BASE_URL ?>/pds/save-section/personal_info?employee_id=<?= UrlId::encode($employeeId) ?>">
        <h3>Personal Information</h3>
        <div class="form-row">
            <div class="form-group"><label>Surname</label><input name="surname" value="<?= pv($personalInfo, 'surname') ?>" required></div>
            <div class="form-group"><label>First Name</label><input name="first_name" value="<?= pv($personalInfo, 'first_name') ?>" required></div>
            <div class="form-group"><label>Middle Name</label><input name="middle_name" value="<?= pv($personalInfo, 'middle_name') ?>"></div>
            <div class="form-group"><label>Ext. (Jr/Sr/III)</label><input name="name_extension" value="<?= pv($personalInfo, 'name_extension') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Birth Date</label><input type="date" name="birth_date" value="<?= pv($personalInfo, 'birth_date') ?>"></div>
            <div class="form-group"><label>Birth Place</label><input name="birth_place" value="<?= pv($personalInfo, 'birth_place') ?>"></div>
            <div class="form-group"><label>Sex</label>
                <select name="sex">
                    <option value="">--</option>
                    <option value="Male" <?= ($personalInfo['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($personalInfo['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="form-group"><label>Civil Status</label>
                <select name="civil_status">
                    <?php foreach (['Single','Married','Widowed','Separated','Others'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= ($personalInfo['civil_status'] ?? '') === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Height</label><input name="height_m" inputmode="decimal" value="<?= pv($personalInfo, 'height_m') ?>" placeholder="1.65 m or 165 cm"><small class="field-hint">Meters or centimeters accepted</small></div>
            <div class="form-group"><label>Weight (kg)</label><input name="weight_kg" value="<?= pv($personalInfo, 'weight_kg') ?>"></div>
            <?php $bloodType = strtoupper(trim((string) ($personalInfo['blood_type'] ?? ''))); ?>
            <div class="form-group"><label>Blood Type</label>
                <select name="blood_type">
                    <option value="">Select blood type</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'UNKNOWN', 'N/A'] as $type): ?>
                        <option value="<?= $type ?>" <?= $bloodType === $type ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                    <?php if ($bloodType !== '' && !in_array($bloodType, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'UNKNOWN', 'N/A'], true)): ?>
                        <option value="<?= htmlspecialchars($bloodType) ?>" selected><?= htmlspecialchars($bloodType) ?> (Previously saved)</option>
                    <?php endif; ?>
                </select>
                <small class="field-hint">Choose Unknown if you have not had your blood type tested.</small>
            </div>
            <?php
            $citizenship = trim((string) ($personalInfo['citizenship'] ?? ''));
            $hasDualCitizenship = trim((string) ($personalInfo['dual_citizenship_country'] ?? '')) !== ''
                || strcasecmp($citizenship, 'Dual Citizenship') === 0;
            ?>
            <div class="form-group"><label>Citizenship</label>
                <select name="citizenship" data-citizenship-select>
                    <option value="">Select citizenship</option>
                    <option value="FILIPINO" <?= !$hasDualCitizenship && strcasecmp($citizenship, 'Filipino') === 0 ? 'selected' : '' ?>>Filipino</option>
                    <option value="DUAL CITIZENSHIP" <?= $hasDualCitizenship ? 'selected' : '' ?>>Dual Citizenship</option>
                    <?php if (!$hasDualCitizenship && $citizenship !== '' && strcasecmp($citizenship, 'Filipino') !== 0 && strcasecmp($citizenship, 'Dual Citizenship') !== 0): ?>
                        <option value="<?= htmlspecialchars($citizenship) ?>" selected><?= htmlspecialchars($citizenship) ?> (Previously saved)</option>
                    <?php endif; ?>
                </select>
                <small class="field-hint">Choose Dual Citizenship only if you are Filipino and also a citizen of another country.</small>
            </div>
        </div>
        <div class="form-row dual-citizenship-fields" data-dual-citizenship-fields>
            <div class="form-group"><label>Dual Citizenship Basis</label><select name="dual_citizenship_type"><option value="">Not applicable</option><?php foreach (['By Birth','By Naturalization'] as $type): ?><option value="<?= $type ?>" <?= ($personalInfo['dual_citizenship_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Other Country of Citizenship</label><input name="dual_citizenship_country" value="<?= pv($personalInfo, 'dual_citizenship_country') ?>" placeholder="Enter country, e.g. Canada"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>GSIS No.</label><input name="gsis_no" value="<?= pv($personalInfo, 'gsis_no') ?>"></div>
            <div class="form-group"><label>Pag-IBIG No.</label><input name="pagibig_no" value="<?= pv($personalInfo, 'pagibig_no') ?>"></div>
            <div class="form-group"><label>PhilHealth No.</label><input name="philhealth_no" value="<?= pv($personalInfo, 'philhealth_no') ?>"></div>
            <div class="form-group"><label>SSS No.</label><input name="sss_no" value="<?= pv($personalInfo, 'sss_no') ?>"></div>
            <div class="form-group"><label>PhilSys Card Number (PCN)</label><input name="philsys_card_no" value="<?= pv($personalInfo, 'philsys_card_no') ?>"></div>
            <div class="form-group"><label>TIN No.</label><input name="tin_no" value="<?= pv($personalInfo, 'tin_no') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Telephone No.</label><input name="telephone_no" value="<?= pv($personalInfo, 'telephone_no') ?>"></div>
            <div class="form-group"><label>Mobile No.</label><input name="mobile_no" value="<?= pv($personalInfo, 'mobile_no') ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= pv($personalInfo, 'email') ?>"></div>
        </div>
        <h3>Government Issued ID</h3>
        <div class="form-row">
            <div class="form-group"><label>ID Type</label><input name="government_issued_id" value="<?= pv($personalInfo, 'government_issued_id') ?>" placeholder="Passport, GSIS, SSS, PRC, Driver's License"></div>
            <div class="form-group"><label>ID / License / Passport Number</label><input name="government_id_number" value="<?= pv($personalInfo, 'government_id_number') ?>"></div>
            <div class="form-group"><label>Date / Place of Issuance</label><input name="government_id_issuance" value="<?= pv($personalInfo, 'government_id_issuance') ?>"></div>
        </div>
        <button class="btn btn-primary" type="submit">Save Section</button>
    </form>

    <div class="sidebar-divider"></div>
    <form class="ajax-section-form pds-address-form" data-endpoint="<?= BASE_URL ?>/pds/save-section/addresses?employee_id=<?= UrlId::encode($employeeId) ?>">
        <h3>Residential Address</h3>
        <div class="form-row">
            <?php $r = $addresses['Residential'] ?? []; ?>
            <div class="form-group"><label>House/Block/Lot</label><input name="residential[house_block_lot]" value="<?= pv($r, 'house_block_lot') ?>"></div>
            <div class="form-group"><label>Street</label><input name="residential[street]" value="<?= pv($r, 'street') ?>"></div>
            <div class="form-group"><label>Subdivision/Village</label><input name="residential[subdivision_village]" value="<?= pv($r, 'subdivision_village') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Province</label><select name="residential[province]" data-address-province="residential" data-searchable-select data-search-placeholder="Search province..."><option value="<?= pv($r, 'province') ?>" selected><?= pv($r, 'province', 'Select province') ?></option></select></div>
            <div class="form-group"><label>City/Municipality</label><select name="residential[city_municipality]" data-address-city="residential" data-searchable-select data-search-placeholder="Search city or municipality..."><option value="<?= pv($r, 'city_municipality') ?>" selected><?= pv($r, 'city_municipality', 'Select province first') ?></option></select></div>
            <div class="form-group"><label>Barangay</label><select name="residential[barangay]" data-address-barangay="residential" data-searchable-select data-search-placeholder="Search barangay..."><option value="<?= pv($r, 'barangay') ?>" selected><?= pv($r, 'barangay', 'Select city first') ?></option></select></div>
            <div class="form-group"><label>Zip Code</label><input name="residential[zip_code]" value="<?= pv($r, 'zip_code') ?>"></div>
        </div>

        <h3>Permanent Address</h3>
        <div class="form-row">
            <?php $p = $addresses['Permanent'] ?? []; ?>
            <div class="form-group"><label>House/Block/Lot</label><input name="permanent[house_block_lot]" value="<?= pv($p, 'house_block_lot') ?>"></div>
            <div class="form-group"><label>Street</label><input name="permanent[street]" value="<?= pv($p, 'street') ?>"></div>
            <div class="form-group"><label>Subdivision/Village</label><input name="permanent[subdivision_village]" value="<?= pv($p, 'subdivision_village') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Province</label><select name="permanent[province]" data-address-province="permanent" data-searchable-select data-search-placeholder="Search province..."><option value="<?= pv($p, 'province') ?>" selected><?= pv($p, 'province', 'Select province') ?></option></select></div>
            <div class="form-group"><label>City/Municipality</label><select name="permanent[city_municipality]" data-address-city="permanent" data-searchable-select data-search-placeholder="Search city or municipality..."><option value="<?= pv($p, 'city_municipality') ?>" selected><?= pv($p, 'city_municipality', 'Select province first') ?></option></select></div>
            <div class="form-group"><label>Barangay</label><select name="permanent[barangay]" data-address-barangay="permanent" data-searchable-select data-search-placeholder="Search barangay..."><option value="<?= pv($p, 'barangay') ?>" selected><?= pv($p, 'barangay', 'Select city first') ?></option></select></div>
            <div class="form-group"><label>Zip Code</label><input name="permanent[zip_code]" value="<?= pv($p, 'zip_code') ?>"></div>
        </div>
        <p class="address-lookup-status" id="address-lookup-status" role="status">Loading Philippine places&hellip;</p>
        <button class="btn btn-primary" type="submit">Save Addresses</button>
    </form>
</div>

<!-- II. FAMILY BACKGROUND -->
<div id="tab-family" class="tab-panel glass-card" style="display:none;">
    <form class="ajax-section-form" data-endpoint="<?= BASE_URL ?>/pds/save-section/family_background?employee_id=<?= UrlId::encode($employeeId) ?>">
        <h3>Spouse</h3>
        <div class="form-row">
            <div class="form-group"><label>Surname</label><input name="spouse_surname" value="<?= pv($familyBackground, 'spouse_surname') ?>"></div>
            <div class="form-group"><label>First Name</label><input name="spouse_first_name" value="<?= pv($familyBackground, 'spouse_first_name') ?>"></div>
            <div class="form-group"><label>Middle Name</label><input name="spouse_middle_name" value="<?= pv($familyBackground, 'spouse_middle_name') ?>"></div>
            <div class="form-group"><label>Name Extension</label><input name="spouse_name_extension" value="<?= pv($familyBackground, 'spouse_name_extension') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Occupation</label><input name="spouse_occupation" value="<?= pv($familyBackground, 'spouse_occupation') ?>"></div>
            <div class="form-group"><label>Employer</label><input name="spouse_employer" value="<?= pv($familyBackground, 'spouse_employer') ?>"></div>
            <div class="form-group"><label>Business Address</label><input name="spouse_business_address" value="<?= pv($familyBackground, 'spouse_business_address') ?>"></div>
            <div class="form-group"><label>Telephone No.</label><input name="spouse_telephone_no" value="<?= pv($familyBackground, 'spouse_telephone_no') ?>"></div>
        </div>

        <h3>Father</h3>
        <div class="form-row">
            <div class="form-group"><label>Surname</label><input name="father_surname" value="<?= pv($familyBackground, 'father_surname') ?>"></div>
            <div class="form-group"><label>First Name</label><input name="father_first_name" value="<?= pv($familyBackground, 'father_first_name') ?>"></div>
            <div class="form-group"><label>Middle Name</label><input name="father_middle_name" value="<?= pv($familyBackground, 'father_middle_name') ?>"></div>
            <div class="form-group"><label>Name Extension</label><input name="father_name_extension" value="<?= pv($familyBackground, 'father_name_extension') ?>"></div>
        </div>

        <h3>Mother (Maiden Name)</h3>
        <div class="form-row">
            <div class="form-group"><label>Surname</label><input name="mother_maiden_surname" value="<?= pv($familyBackground, 'mother_maiden_surname') ?>"></div>
            <div class="form-group"><label>First Name</label><input name="mother_first_name" value="<?= pv($familyBackground, 'mother_first_name') ?>"></div>
            <div class="form-group"><label>Middle Name</label><input name="mother_middle_name" value="<?= pv($familyBackground, 'mother_middle_name') ?>"></div>
        </div>
        <button class="btn btn-primary" type="submit">Save Family Background</button>
    </form>

    <div class="sidebar-divider"></div>
    <h3>Children</h3>
    <div id="children-rows" data-repeating-section="children"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('children')">+ Add Child</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('children')">Save Children</button>
    </div>
</div>

<!-- III. EDUCATION -->
<div id="tab-education" class="tab-panel glass-card" style="display:none;">
    <h3>Educational Background</h3>
    <div id="educational_background-rows" data-repeating-section="educational_background"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('educational_background')">+ Add Row</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('educational_background')">Save Education</button>
    </div>
</div>

<!-- IV. ELIGIBILITY -->
<div id="tab-eligibility" class="tab-panel glass-card" style="display:none;">
    <h3>Civil Service Eligibility</h3>
    <div id="civil_service_eligibility-rows" data-repeating-section="civil_service_eligibility"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('civil_service_eligibility')">+ Add Row</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('civil_service_eligibility')">Save Eligibility</button>
    </div>
</div>

<!-- V. WORK EXPERIENCE -->
<div id="tab-work" class="tab-panel glass-card" style="display:none;">
    <h3>Work Experience</h3>
    <div id="work_experience-rows" data-repeating-section="work_experience"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('work_experience')">+ Add Row</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('work_experience')">Save Work Experience</button>
    </div>
</div>

<!-- VI. VOLUNTARY WORK -->
<div id="tab-voluntary" class="tab-panel glass-card" style="display:none;">
    <h3>Voluntary Work</h3>
    <div id="voluntary_work-rows" data-repeating-section="voluntary_work"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('voluntary_work')">+ Add Row</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('voluntary_work')">Save Voluntary Work</button>
    </div>
</div>

<!-- VII. LEARNING & DEVELOPMENT -->
<div id="tab-training" class="tab-panel glass-card" style="display:none;">
    <h3>Learning &amp; Development (Trainings/Seminars)</h3>
    <div id="learning_development-rows" data-repeating-section="learning_development"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('learning_development')">+ Add Row</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('learning_development')">Save Trainings</button>
    </div>
</div>

<!-- VIII. OTHER INFO -->
<div id="tab-other" class="tab-panel glass-card" style="display:none;">
    <h3>Other Information (Skills / Hobbies / Recognitions / Memberships)</h3>
    <p style="color:var(--text-muted); font-size:0.85rem;">Category options: Skill, Hobby, Recognition, Membership</p>
    <div id="other_info-rows" data-repeating-section="other_info"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('other_info')">+ Add Row</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('other_info')">Save Other Info</button>
    </div>
</div>

<!-- QUESTIONNAIRE -->
<div id="tab-questionnaire" class="tab-panel glass-card" style="display:none;">
    <form class="ajax-section-form" data-endpoint="<?= BASE_URL ?>/pds/save-section/questionnaire?employee_id=<?= UrlId::encode($employeeId) ?>">
        <h3>Background Questions</h3>
        <?php
        $questions = [
            'q34a_related_by_consanguinity' => '34a. Related by consanguinity/affinity to appointing/recommending authority?',
            'q34b_related_to_appointing_authority' => '34b. Related within third degree to head of office/bureau?',
            'q35a_found_guilty_admin_case' => '35a. Found guilty of any administrative offense?',
            'q35b_criminal_charged' => '35b. Criminally charged before any court?',
            'q35c_convicted' => '35c. Convicted of any crime?',
            'q35d_separated_from_service' => '35d. Separated from the service (resignation/retirement/dismissal)?',
            'q36_candidate_last_election' => '36. Candidate in the last election?',
            'q37_resigned_to_avoid_campaign' => '37. Resigned from government to campaign for an elective office?',
            'q38a_immigrant_status' => '38a. Acquired immigrant status in a foreign country?',
            'q39_indigenous_group' => '39. Member of an indigenous group?',
            'q40_pwd' => '40. Person with disability?',
            'q41_solo_parent' => '41. Solo parent?',
        ];
        foreach ($questions as $key => $label):
            $detailsKey = preg_replace('/_[a-z].*/', '', $key) . '_details';
        ?>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label><?= htmlspecialchars($label) ?></label>
                    <select name="<?= $key ?>">
                        <option value="">--</option>
                        <option value="1" <?= (($questionnaire[$key] ?? null) == 1) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= (($questionnaire[$key] ?? null) === '0' || ($questionnaire[$key] ?? null) === 0) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Details (if yes)</label>
                    <input name="<?= $detailsKey ?>" value="<?= pv($questionnaire, $detailsKey) ?>">
                </div>
            </div>
        <?php endforeach; ?>
        <div class="form-row">
            <div class="form-group"><label>35b. Date Filed</label><input type="date" name="q35b_date_filed" value="<?= pv($questionnaire, 'q35b_date_filed') ?>"></div>
            <div class="form-group"><label>35b. Status of Case/s</label><input name="q35b_status_cases" value="<?= pv($questionnaire, 'q35b_status_cases') ?>"></div>
        </div>
        <button class="btn btn-primary" type="submit">Save Questionnaire</button>
    </form>
</div>

<!-- IX. REFERENCES -->
<div id="tab-references" class="tab-panel glass-card" style="display:none;">
    <h3>Character References</h3>
    <div id="character_references-rows" data-repeating-section="character_references"></div>
    <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addRepeatingRow('character_references')">+ Add Reference</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveRepeatingSection('character_references')">Save References</button>
    </div>
</div>

</div>
<script>
// Field definitions per repeating section: [name, label, type]
const REPEATING_FIELD_DEFS = {
    children: [['full_name','Full Name','text'],['birth_date','Birth Date','date']],
    educational_background: [
        ['level','Level','select:Elementary,Secondary,Vocational,College,Graduate Studies'],
        ['school_name','School Name','text'],['degree_course','Degree/Course','text'],
        ['period_from','From (Year)','number'],['period_to','To (Year)','number'],
        ['highest_units_earned','Highest Units Earned','text'],['year_graduated','Year Graduated','number'],
        ['scholarship_honors','Scholarship/Honors','text'],
    ],
    civil_service_eligibility: [
        ['eligibility_name','Eligibility','text'],['rating','Rating','text'],['exam_date','Exam Date','date'],
        ['exam_place','Exam Place','text'],['license_number','License No.','text'],['license_validity','License Validity','date'],
    ],
    work_experience: [
        ['date_from','From','date'],['date_to','To','date'],['position_title','Position Title','text'],
        ['department_agency','Department/Agency','text'],['monthly_salary','Monthly Salary','number'],
        ['salary_grade_step','SG/Step','text'],['appointment_status','Status of Appointment','text'],
        ['is_government','Gov\'t Service?','select:1,0'],
    ],
    voluntary_work: [
        ['organization_name','Organization','text'],['organization_address','Address','text'],
        ['date_from','From','date'],['date_to','To','date'],['number_of_hours','No. of Hours','number'],
        ['position_nature_of_work','Position/Nature of Work','text'],
    ],
    learning_development: [
        ['title','Title','text'],['date_from','From','date'],['date_to','To','date'],
        ['number_of_hours','No. of Hours','number'],
        ['type_of_ld','Type','select:Managerial,Supervisory,Technical,Others'],
        ['conducted_by','Conducted/Sponsored By','text'],
    ],
    other_info: [
        ['category','Category','select:Skill,Hobby,Recognition,Membership'],
        ['description','Description','text'],
    ],
    character_references: [
        ['full_name','Full Name','text'],['address','Address','text'],['telephone_no','Telephone No.','text'],
    ],
};

const REPEATING_INITIAL_DATA = {
    children: <?= json_encode($children) ?>,
    educational_background: <?= json_encode($education) ?>,
    civil_service_eligibility: <?= json_encode($eligibility) ?>,
    work_experience: <?= json_encode($workExperience) ?>,
    voluntary_work: <?= json_encode($voluntaryWork) ?>,
    learning_development: <?= json_encode($learningDevelopment) ?>,
    other_info: <?= json_encode($otherInfo) ?>,
    character_references: <?= json_encode($characterReferences) ?>,
};

function buildFieldHtml(section, rowIndex, field, value) {
    const [name, label, type] = field;
    const v = value ?? '';
    if (type.startsWith('select:')) {
        const options = type.replace('select:', '').split(',');
        const opts = options.map(o => `<option value="${o}" ${String(v) === o ? 'selected' : ''}>${o}</option>`).join('');
        return `<div class="form-group"><label>${label}</label><select data-field="${name}"><option value="">--</option>${opts}</select></div>`;
    }
    return `<div class="form-group"><label>${label}</label><input data-field="${name}" type="${type}" value="${(v ?? '').toString().replace(/"/g,'&quot;')}"></div>`;
}

function addRepeatingRow(section, data = {}) {
    const container = document.getElementById(`${section}-rows`);
    const fields = REPEATING_FIELD_DEFS[section];
    const row = document.createElement('div');
    row.className = 'form-row repeating-row glass-light';
    row.style.padding = '0.75rem';
    row.style.borderRadius = 'var(--radius-small)';
    row.style.marginBottom = '0.6rem';
    row.innerHTML = fields.map(f => buildFieldHtml(section, 0, f, data[f[0]])).join('') +
        `<div class="form-group" style="flex:0 0 auto;align-self:flex-end;"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.repeating-row').remove()">Remove</button></div>`;
    container.appendChild(row);
}

function saveRepeatingSection(section) {
    const container = document.getElementById(`${section}-rows`);
    const fields = REPEATING_FIELD_DEFS[section];
    const rows = [...container.querySelectorAll('.repeating-row')].map(rowEl => {
        const obj = {};
        fields.forEach(([name]) => {
            const el = rowEl.querySelector(`[data-field="${name}"]`);
            if (el && el.value !== '') {
                const isTextEntry = el.matches('textarea, input:not([type="date"]):not([type="number"])');
                obj[name] = isTextEntry ? el.value.toLocaleUpperCase() : el.value;
                if (isTextEntry) el.value = obj[name];
            }
        });
        return obj;
    });

    const formData = new FormData();
    formData.append('rows', JSON.stringify(rows));
    HRIS.postForm(`${window.BASE_URL}/pds/save-section/${section}?employee_id=<?= UrlId::encode($employeeId) ?>`, formData)
        .then(result => {
            if (result.success) {
                HRIS.updatePdsCompletion(result.completionPercent);
                HRIS.flash(result.message || 'Saved.', 'success');
            } else if (window.showPdsSaveError) {
                window.showPdsSaveError(container, result);
            }
        })
        .catch(() => window.showPdsSaveError?.(container, {
            error: 'The server response could not be read.',
            suggestion: 'Check your connection, refresh the page, and submit this section again.',
        }));
}

Object.keys(REPEATING_INITIAL_DATA).forEach(section => {
    REPEATING_INITIAL_DATA[section].forEach(row => addRepeatingRow(section, row));
});

// Cascading Philippine address selection: Province -> City/Municipality -> Barangay.
(() => {
    const apiBase = 'https://psgc.cloud/api/v2';
    const status = document.getElementById('address-lookup-status');
    const provinceSelects = [...document.querySelectorAll('[data-address-province]')];
    if (!provinceSelects.length) return;

    const unpack = payload => Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
    const placeName = value => typeof value === 'string' ? value : (value?.name || '');
    const normalize = value => String(value || '').trim().toLocaleLowerCase();
    const parentName = city => placeName(city.province) || placeName(city.region);
    let cities = [];

    const fillSelect = (select, items, placeholder, selectedValue = '') => {
        select.replaceChildren();
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        select.appendChild(placeholderOption);
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.value;
            option.textContent = String(item.label).toLocaleUpperCase();
            if (normalize(item.value) === normalize(selectedValue)) option.selected = true;
            select.appendChild(option);
        });
        const searchInput = select.closest('.searchable-select')?.querySelector('.searchable-select-input');
        if (searchInput) {
            const current = select.options[select.selectedIndex];
            searchInput.value = current?.value ? current.textContent.trim() : '';
        }
    };

    const controls = group => ({
        province: document.querySelector(`[data-address-province="${group}"]`),
        city: document.querySelector(`[data-address-city="${group}"]`),
        barangay: document.querySelector(`[data-address-barangay="${group}"]`),
    });

    const loadBarangays = async (group, selectedValue = '') => {
        const fields = controls(group);
        const city = cities.find(item => normalize(item.name) === normalize(fields.city.value) && normalize(parentName(item)) === normalize(fields.province.value));
        fillSelect(fields.barangay, [], fields.city.value ? 'Loading barangays…' : 'Select city first');
        if (!city) return;
        try {
            const response = await fetch(`${apiBase}/cities-municipalities/${encodeURIComponent(city.code)}/barangays`);
            if (!response.ok) throw new Error('Barangay lookup failed');
            const barangays = unpack(await response.json()).sort((a, b) => a.name.localeCompare(b.name));
            fillSelect(fields.barangay, barangays.map(item => ({ value: item.name, label: item.name })), 'Select barangay', selectedValue);
        } catch (_) {
            fillSelect(fields.barangay, selectedValue ? [{ value: selectedValue, label: selectedValue }] : [], 'Barangays unavailable');
        }
    };

    const loadCities = (group, selectedCity = '', selectedBarangay = '') => {
        const fields = controls(group);
        const matches = cities.filter(city => normalize(parentName(city)) === normalize(fields.province.value));
        fillSelect(fields.city, matches.map(city => ({ value: city.name, label: city.name })), fields.province.value ? 'Select city/municipality' : 'Select province first', selectedCity);
        fillSelect(fields.barangay, selectedBarangay ? [{ value: selectedBarangay, label: selectedBarangay }] : [], 'Select city first', selectedBarangay);
        if (fields.city.value) loadBarangays(group, selectedBarangay);
    };

    provinceSelects.forEach(select => {
        const group = select.dataset.addressProvince;
        select.addEventListener('change', () => loadCities(group));
        controls(group).city.addEventListener('change', () => loadBarangays(group));
    });

    fetch(`${apiBase}/cities-municipalities`)
        .then(response => {
            if (!response.ok) throw new Error('Place lookup failed');
            return response.json();
        })
        .then(payload => {
            cities = unpack(payload).sort((a, b) => a.name.localeCompare(b.name));
            const provinces = [...new Set(cities.map(parentName).filter(Boolean))].sort((a, b) => a.localeCompare(b));
            provinceSelects.forEach(select => {
                const group = select.dataset.addressProvince;
                const fields = controls(group);
                const saved = { province: select.value, city: fields.city.value, barangay: fields.barangay.value };
                fillSelect(select, provinces.map(name => ({ value: name, label: name })), 'Select province', saved.province);
                loadCities(group, saved.city, saved.barangay);
            });
            status.textContent = 'Choose a province, then a city or municipality, and finally a barangay.';
        })
        .catch(() => {
            document.querySelectorAll('[data-address-province], [data-address-city], [data-address-barangay]').forEach(select => {
                const input = document.createElement('input');
                input.name = select.name;
                input.value = select.value;
                input.placeholder = 'Enter manually';
                input.disabled = select.disabled;
                select.replaceWith(input);
            });
            status.textContent = 'Place selections are temporarily unavailable. Enter the address manually.';
            status.classList.add('lookup-unavailable');
        });
})();
</script>

<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
