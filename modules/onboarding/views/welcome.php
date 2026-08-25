<?php
/** @var int $completionPercent */
$displayName = Auth::displayName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome — HRMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="auth-page">
<div class="onboarding-card glass-strong">
    <div class="onboarding-icon">&#128075;</div>
    <div class="sidebar-brand" style="justify-content:center; padding:0 0 0.5rem;">
        <span class="brand-dot"></span>
        <span class="brand-text" style="text-align:left;"><strong>HRMS</strong><span>Human Resource Mgmt.</span></span>
    </div>

    <h1>Welcome to HRIS, <?= htmlspecialchars($displayName) ?>!</h1>
    <p class="onboarding-lead">
        Before you can access your dashboard, tasks, and the rest of the system, we need you to
        complete your <strong>Personal Data Sheet (CS Form 212)</strong>. This keeps your 201 file
        accurate and up to date.
    </p>

    <div class="onboarding-steps">
        <div class="onboarding-step">
            <span class="onboarding-step-num">1</span>
            <span>Fill out your personal information, family background, and education.</span>
        </div>
        <div class="onboarding-step">
            <span class="onboarding-step-num">2</span>
            <span>Add your eligibility, work experience, and other required sections.</span>
        </div>
        <div class="onboarding-step">
            <span class="onboarding-step-num">3</span>
            <span>Once at least <?= PDS_MIN_COMPLETION_PERCENT ?>% is complete, the rest of HRIS unlocks automatically.</span>
        </div>
    </div>

    <?php if ($completionPercent > 0): ?>
        <div style="margin:1.25rem 0;">
            <div class="progress-bar"><div class="progress-bar-fill" data-target="<?= $completionPercent ?>"></div></div>
            <span style="font-size:0.78rem; color:var(--text-muted);"><?= $completionPercent ?>% already complete</span>
        </div>
    <?php endif; ?>

    <a class="btn btn-primary onboarding-cta" href="<?= BASE_URL ?>/pds?required=1">Get Started &rarr;</a>
    <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="onboarding-logout" type="submit">Not you? Sign out</button></form>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= rawurlencode(JS_ASSET_VERSION) ?>" defer></script>
</body>
</html>
