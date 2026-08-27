<?php
/** @var string|null $error */
/** @var array $values */
/** @var string $employeeNumber */
$value = static fn(string $key): string => htmlspecialchars((string) ($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Personal details &mdash; HRMS</title>
<script>(function(){try{var s=JSON.parse(localStorage.getItem('hrms-appearance')||'{}');var m=s.mode||'system';var d=m==='dark'||(m==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.dataset.theme=d?'dark':'light';}catch(e){}})();</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="personnel-setup-page">
<main class="personnel-setup-shell personal-details-shell">
    <section class="personnel-setup-intro">
        <div class="setup-brand"><span>P</span><div><strong>Project PUNLA</strong><small>Human Resource Management</small></div></div>
        <div class="setup-intro-copy final-setup-copy">
            <span class="setup-step">Account setup <b>2 of 2</b></span>
            <h1>One last step before you begin.</h1>
            <p>Confirm your essential employee details to prepare your HRMS workspace.</p>
            <div class="final-setup-progress"><span><i></i></span><div><strong>Almost there</strong><small>Personnel type complete &middot; Personal details remaining</small></div><b>85%</b></div>
        </div>
        <div class="setup-privacy"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span><strong>Your information is protected</strong><small>You can complete the rest of your Personal Data Sheet later from My Profile.</small></span></div>
    </section>

    <section class="personnel-setup-form glass-strong">
        <div class="setup-form-heading personal-details-heading"><span class="launcher-eyebrow">Final setup step</span><div class="final-heading-row"><span><h2>Complete your profile</h2><p>Review the prefilled information and supply the missing details.</p></span><b>Step 2 of 2</b></div><div class="required-note"><span>*</span> Required information</div></div>
        <?php if ($error): ?><div class="alert alert-error" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/personal-details-setup">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">

            <div class="setup-section-title"><span><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><div><strong>Identity</strong><small>Your official employee record</small></div><b>01</b></div>
            <div class="form-row">
                <div class="form-group"><label>Employee number *</label><input name="employee_number" value="<?= htmlspecialchars($employeeNumber) ?>" maxlength="30" placeholder="e.g. 123456" required autocomplete="off" inputmode="numeric"></div>
                <div class="form-group"><label>Last name *</label><input name="surname" value="<?= $value('surname') ?>" maxlength="100" required autocomplete="family-name" autocapitalize="words"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>First name *</label><input name="first_name" value="<?= $value('first_name') ?>" maxlength="100" required autocomplete="given-name" autocapitalize="words"></div>
                <div class="form-group"><label>Middle name</label><input name="middle_name" value="<?= $value('middle_name') ?>" maxlength="100" autocomplete="additional-name" autocapitalize="words"></div>
                <div class="form-group setup-small-field"><label>Extension</label><input name="name_extension" value="<?= $value('name_extension') ?>" maxlength="20" placeholder="Jr., Sr., III"></div>
            </div>
            <div class="form-row demographic-fields-row">
                <div class="form-group birth-date-field"><label for="setup-birth-date">Date of birth *</label><div class="date-input-wrap"><input id="setup-birth-date" type="date" name="birth_date" value="<?= $value('birth_date') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>" required><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></span></div><small class="field-hint">Select your birth date; future dates are not allowed.</small></div>
                <div class="form-group"><label>Gender *</label><select name="sex" required><option value="">Select&hellip;</option><option value="Male" <?= $value('sex') === 'Male' ? 'selected' : '' ?>>Male</option><option value="Female" <?= $value('sex') === 'Female' ? 'selected' : '' ?>>Female</option></select></div>
                <div class="form-group"><label>Civil status *</label><select name="civil_status" required><option value="">Select&hellip;</option><?php foreach (['Single','Married','Widowed','Separated','Others'] as $status): ?><option value="<?= $status ?>" <?= $value('civil_status') === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Place of birth</label><input name="birth_place" value="<?= $value('birth_place') ?>" maxlength="150"></div>
                <div class="form-group"><label>PWD status</label><select name="pwd_status"><option value="0">No</option><option value="1">Yes</option></select></div>
            </div>

            <div class="setup-section-title setup-section-spaced"><span><svg viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.7 19.7 0 0 1-8.6-3.1 19.3 19.3 0 0 1-6-6A19.7 19.7 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></svg></span><div><strong>Contact</strong><small>How HR can reach you</small></div><b>02</b></div>
            <div class="form-row">
                <div class="form-group"><label>Contact number *</label><input type="tel" name="mobile_no" value="<?= $value('mobile_no') ?>" maxlength="30" placeholder="09xxxxxxxxx" required autocomplete="tel" inputmode="tel"></div>
                <div class="form-group"><label>Email address *</label><input type="email" name="email" value="<?= $value('email') ?>" maxlength="150" required autocomplete="email"></div>
            </div>

            <div class="setup-section-title setup-section-spaced"><span><svg viewBox="0 0 24 24"><path d="m3 11 9-8 9 8"/><path d="M5 10v11h14V10M9 21v-6h6v6"/></svg></span><div><strong>Residential address</strong><small>Your current home address</small></div><b>03</b></div>
            <div class="form-group"><label>House / lot / block / street / sitio / subdivision *</label><input name="house_block_lot" value="<?= $value('house_block_lot') ?>" maxlength="150" placeholder="e.g. Lot 5 Block 3, Rizal St., Poblacion" required autocomplete="street-address"></div>
            <div class="address-lookup-card">
                <div class="address-flow"><span class="active">1 <b>Province</b></span><i></i><span>2 <b>City / Municipality</b></span><i></i><span>3 <b>Barangay</b></span></div>
                <div class="form-group"><label>Province *</label><select name="province" required autocomplete="address-level1" data-final-province data-searchable-select data-search-placeholder="Search province..."><option value="<?= $value('province') ?>" selected><?= $value('province') ?: 'Loading provinces...' ?></option></select></div>
                <div class="form-row">
                    <div class="form-group"><label>City / municipality *</label><select name="city_municipality" required autocomplete="address-level2" data-final-city data-searchable-select data-search-placeholder="Search city or municipality..."><option value="<?= $value('city_municipality') ?>" selected><?= $value('city_municipality') ?: 'Select province first' ?></option></select></div>
                    <div class="form-group"><label>Barangay *</label><select name="barangay" required data-final-barangay data-searchable-select data-search-placeholder="Search barangay..."><option value="<?= $value('barangay') ?>" selected><?= $value('barangay') ?: 'Select city first' ?></option></select></div>
                </div>
                <p class="address-lookup-help" id="final-address-status"><span>i</span><span>Select your province first. The available cities and barangays will update automatically.</span></p>
            </div>
            <div class="form-row">
                <div class="form-group"><label>ZIP code</label><input name="zip_code" value="<?= $value('zip_code') ?>" maxlength="10" autocomplete="postal-code" inputmode="numeric"></div>
            </div>

            <section class="privacy-consent-card" aria-labelledby="privacy-consent-title">
                <div class="privacy-consent-head"><span><svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.3c0 4.7 3.2 8.9 7.5 9.7 4.3-.8 7.5-5 7.5-9.7V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg></span><span><strong id="privacy-consent-title">Data Privacy Consent</strong><small>Republic Act No. 10173 &middot; Data Privacy Act of 2012</small></span><b>Required</b></div>
                <div class="privacy-consent-copy">
                    <p>I voluntarily authorize Project PUNLA HRMS and its authorized HR personnel to collect, store, use, and otherwise process the personal information I provide for legitimate personnel administration, employment records, benefits, reporting, and other lawful HR functions.</p>
                    <p>I understand that my information will be accessible only to authorized personnel, retained according to applicable laws and agency records policies, and protected using reasonable organizational, physical, and technical safeguards. I may request access to or correction of my information and raise a privacy concern through the appropriate HR or Data Protection Officer.</p>
                </div>
                <label class="privacy-consent-check"><input id="privacy-consent-checkbox" type="checkbox" name="privacy_consent" value="1" required <?= $value('privacy_consent') === '1' ? 'checked' : '' ?>><span class="consent-checkbox"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span><span>I have read and understood this notice, and I consent to the collection and processing of my information for the purposes stated above.</span></label>
                <a class="privacy-consent-link" href="https://privacy.gov.ph/data-privacy-act/" target="_blank" rel="noopener noreferrer">Read the Data Privacy Act <span>&nearr;</span></a>
            </section>

            <div class="final-submit-bar"><span><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg><span><strong>Ready when you are</strong><small>You can update these details later.</small></span></span><button class="btn btn-primary setup-continue" id="final-setup-submit" type="submit" <?= $value('privacy_consent') === '1' ? '' : 'disabled' ?>>Save and open HRMS <span>&rarr;</span></button></div>
        </form>
        <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="onboarding-logout" type="submit">Not you? Sign out</button></form>
    </section>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const finalSetupForm = document.querySelector('.personal-details-shell form[action$="/personal-details-setup"]');
const privacyConsent = document.getElementById('privacy-consent-checkbox');
const finalSetupSubmit = document.getElementById('final-setup-submit');
const syncConsentButton = () => {
    if (!privacyConsent || !finalSetupSubmit) return;
    finalSetupSubmit.disabled = !privacyConsent.checked;
    finalSetupSubmit.setAttribute('aria-disabled', String(!privacyConsent.checked));
};
privacyConsent?.addEventListener('change', syncConsentButton);
syncConsentButton();
finalSetupForm?.querySelectorAll('input, select').forEach(control => {
    control.addEventListener('blur', () => { control.dataset.touched = 'true'; });
});
finalSetupForm?.addEventListener('submit', event => {
    finalSetupForm.classList.add('was-validated');
    if (!finalSetupForm.checkValidity()) {
        event.preventDefault();
        finalSetupForm.querySelector(':invalid')?.focus();
    }
});

(() => {
    const apiBase = 'https://psgc.cloud/api/v2';
    const province = document.querySelector('[data-final-province]');
    const city = document.querySelector('[data-final-city]');
    const barangay = document.querySelector('[data-final-barangay]');
    const status = document.querySelector('#final-address-status > span:last-child');
    if (!province || !city || !barangay) return;
    const saved = { province: province.value, city: city.value, barangay: barangay.value };
    const unpack = payload => Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
    const nameOf = value => typeof value === 'string' ? value : (value?.name || '');
    const normalize = value => String(value || '').trim().toLocaleLowerCase();
    const parentName = place => nameOf(place.province) || nameOf(place.region);
    let cities = [];

    const fill = (select, items, placeholder, selected = '') => {
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach(item => select.add(new Option(String(item.label).toLocaleUpperCase(), item.value, false, normalize(item.value) === normalize(selected))));
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const loadBarangays = async (selected = '') => {
        const match = cities.find(item => normalize(item.name) === normalize(city.value) && normalize(parentName(item)) === normalize(province.value));
        fill(barangay, [], city.value ? 'Loading barangays...' : 'Select city first');
        if (!match) return;
        try {
            const response = await fetch(`${apiBase}/cities-municipalities/${encodeURIComponent(match.code)}/barangays`);
            if (!response.ok) throw new Error();
            const rows = unpack(await response.json()).sort((a, b) => a.name.localeCompare(b.name));
            fill(barangay, rows.map(item => ({ value: item.name, label: item.name })), 'Select barangay', selected);
            status.textContent = 'Search and select your barangay to complete the address.';
        } catch (_) {
            fill(barangay, selected ? [{ value: selected, label: selected }] : [], 'Barangays unavailable', selected);
            status.textContent = 'Barangay lookup is temporarily unavailable. Refresh or enter it manually.';
        }
    };
    const loadCities = (selectedCity = '', selectedBarangay = '') => {
        const rows = cities.filter(item => normalize(parentName(item)) === normalize(province.value));
        fill(city, rows.map(item => ({ value: item.name, label: item.name })), province.value ? 'Select city / municipality' : 'Select province first', selectedCity);
        fill(barangay, selectedBarangay ? [{ value: selectedBarangay, label: selectedBarangay }] : [], 'Select city first', selectedBarangay);
        if (city.value) loadBarangays(selectedBarangay);
        status.textContent = province.value ? `Search from ${rows.length.toLocaleString()} cities and municipalities in ${province.value}.` : 'Select your province first. The available cities and barangays will update automatically.';
    };
    province.addEventListener('change', () => loadCities());
    city.addEventListener('change', () => loadBarangays());
    fetch(`${apiBase}/cities-municipalities`)
        .then(response => { if (!response.ok) throw new Error(); return response.json(); })
        .then(payload => {
            cities = unpack(payload).sort((a, b) => a.name.localeCompare(b.name));
            const provinces = [...new Set(cities.map(parentName).filter(Boolean))].sort((a, b) => a.localeCompare(b));
            fill(province, provinces.map(name => ({ value: name, label: name })), 'Select province', saved.province);
            loadCities(saved.city, saved.barangay);
        })
        .catch(() => {
            [province, city, barangay].forEach(select => {
                const input = document.createElement('input'); input.name = select.name; input.value = select.value; input.required = true; input.placeholder = 'Enter manually';
                const wrapper = select.closest('.searchable-select');
                if (wrapper) wrapper.replaceWith(input); else select.replaceWith(input);
            });
            status.textContent = 'The place directory is unavailable. You can enter each address field manually.';
        });
})();
</script>
</body>
</html>
