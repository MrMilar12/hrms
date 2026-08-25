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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Personal details &mdash; HRMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="personnel-setup-page">
<main class="personnel-setup-shell personal-details-shell">
    <section class="personnel-setup-intro">
        <div class="setup-brand"><span>P</span><div><strong>Project PUNLA</strong><small>Human Resource Management</small></div></div>
        <span class="setup-step">Profile setup &middot; Personal details</span>
        <h1>Let&rsquo;s complete your essentials.</h1>
        <p>We need a few official details before opening your workspace. Information already on file is filled in automatically.</p>
        <div class="setup-privacy"><span>&#128274;</span><span><strong>Your data is handled securely.</strong><small>You can add your photo and finish the rest of your Personal Data Sheet later from My Profile.</small></span></div>
    </section>

    <section class="personnel-setup-form glass-strong">
        <div class="setup-form-heading personal-details-heading"><span class="launcher-eyebrow">Final setup step</span><h2>Personal information</h2><p><span class="required-star">*</span> Required fields must be completed to continue.</p></div>
        <?php if ($error): ?><div class="alert alert-error" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/personal-details-setup">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">

            <div class="setup-section-title"><span>&#128100;</span><div><strong>Identity</strong><small>Your basic employee record</small></div></div>
            <div class="form-row">
                <div class="form-group"><label>Employee number *</label><input name="employee_number" value="<?= htmlspecialchars($employeeNumber) ?>" maxlength="30" placeholder="e.g. 123456" required autocomplete="off"></div>
                <div class="form-group"><label>Last name *</label><input name="surname" value="<?= $value('surname') ?>" maxlength="100" required autocomplete="family-name"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>First name *</label><input name="first_name" value="<?= $value('first_name') ?>" maxlength="100" required autocomplete="given-name"></div>
                <div class="form-group"><label>Middle name</label><input name="middle_name" value="<?= $value('middle_name') ?>" maxlength="100" autocomplete="additional-name"></div>
                <div class="form-group setup-small-field"><label>Extension</label><input name="name_extension" value="<?= $value('name_extension') ?>" maxlength="20" placeholder="Jr., Sr., III"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Date of birth *</label><input type="date" name="birth_date" value="<?= $value('birth_date') ?>" required></div>
                <div class="form-group"><label>Gender *</label><select name="sex" required><option value="">Select&hellip;</option><option value="Male" <?= $value('sex') === 'Male' ? 'selected' : '' ?>>Male</option><option value="Female" <?= $value('sex') === 'Female' ? 'selected' : '' ?>>Female</option></select></div>
                <div class="form-group"><label>Civil status *</label><select name="civil_status" required><option value="">Select&hellip;</option><?php foreach (['Single','Married','Widowed','Separated','Others'] as $status): ?><option value="<?= $status ?>" <?= $value('civil_status') === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Place of birth</label><input name="birth_place" value="<?= $value('birth_place') ?>" maxlength="150"></div>
                <div class="form-group"><label>PWD status</label><select name="pwd_status"><option value="0">No</option><option value="1">Yes</option></select></div>
            </div>

            <div class="setup-section-title setup-section-spaced"><span>&#128222;</span><div><strong>Contact</strong><small>How HR can reach you</small></div></div>
            <div class="form-row">
                <div class="form-group"><label>Contact number *</label><input name="mobile_no" value="<?= $value('mobile_no') ?>" maxlength="30" placeholder="09xxxxxxxxx" required autocomplete="tel"></div>
                <div class="form-group"><label>Email address *</label><input type="email" name="email" value="<?= $value('email') ?>" maxlength="150" required autocomplete="email"></div>
            </div>

            <div class="setup-section-title setup-section-spaced"><span>&#127968;</span><div><strong>Residential address</strong><small>Your current home address</small></div></div>
            <div class="form-group"><label>House / lot / block / street / sitio / subdivision *</label><input name="house_block_lot" value="<?= $value('house_block_lot') ?>" maxlength="150" placeholder="e.g. Lot 5 Block 3, Rizal St., Poblacion" required autocomplete="street-address"></div>
            <div class="form-row">
                <div class="form-group"><label>Barangay *</label><input name="barangay" value="<?= $value('barangay') ?>" maxlength="100" required></div>
                <div class="form-group"><label>City / municipality *</label><input name="city_municipality" value="<?= $value('city_municipality') ?>" maxlength="100" required autocomplete="address-level2"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Province *</label><input name="province" value="<?= $value('province') ?>" maxlength="100" required autocomplete="address-level1"></div>
                <div class="form-group"><label>ZIP code</label><input name="zip_code" value="<?= $value('zip_code') ?>" maxlength="10" autocomplete="postal-code"></div>
            </div>

            <button class="btn btn-primary setup-continue" type="submit">Save and open HRMS <span>&rarr;</span></button>
        </form>
        <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="onboarding-logout" type="submit">Not you? Sign out</button></form>
    </section>
</main>
</body>
</html>
