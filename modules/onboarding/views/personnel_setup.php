<?php /** @var string|null $error */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken()) ?>">
<title>Complete your profile &mdash; HRMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="personnel-setup-page">
<main class="personnel-setup-shell">
    <section class="personnel-setup-intro">
        <div class="setup-brand"><span>P</span><div><strong>Project PUNLA</strong><small>Human Resource Management</small></div></div>
        <span class="setup-step">Profile setup &middot; One quick step</span>
        <h1>Tell us where you serve.</h1>
        <p>Your personnel classification shapes the profile fields and tools you see inside HRMS. You can update these details later from My Profile.</p>
        <div class="setup-privacy"><span>&#128274;</span><span><strong>Your information stays protected.</strong><small>Only authorized HR personnel can access official employment records.</small></span></div>
    </section>

    <section class="personnel-setup-form glass-strong">
        <div class="setup-form-heading"><span class="launcher-eyebrow">Welcome, <?= htmlspecialchars(explode(' ', Auth::displayName())[0]) ?></span><h2>Choose your personnel type</h2><p>Select the option that matches your current appointment.</p></div>
        <?php if ($error): ?><div class="alert alert-error" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/personnel-setup" id="personnel-setup-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
            <div class="personnel-choice-grid">
                <label class="personnel-choice">
                    <input type="radio" name="personnel_type" value="Teaching" required>
                    <span class="personnel-choice-check">&#10003;</span><span class="personnel-choice-icon">&#127979;</span>
                    <span><strong>Teaching</strong><small>Teachers and school-based instructional personnel</small></span>
                </label>
                <label class="personnel-choice">
                    <input type="radio" name="personnel_type" value="Non-Teaching" required>
                    <span class="personnel-choice-check">&#10003;</span><span class="personnel-choice-icon">&#128188;</span>
                    <span><strong>Non-Teaching</strong><small>Administrative, support, and non-instructional personnel</small></span>
                </label>
            </div>

            <div class="setup-teaching-fields" id="setup-teaching-fields" hidden>
                <div class="setup-section-title"><span>&#127891;</span><div><strong>Teaching assignment</strong><small>Tell us about your school and plantilla station.</small></div></div>
                <div class="form-row">
                    <div class="form-group"><label>School ID code</label><input name="school_id_code" maxlength="30" placeholder="e.g. 300001"></div>
                    <div class="form-group"><label>District</label><input name="district" maxlength="120" placeholder="e.g. District I"></div>
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
</script>
</body>
</html>
