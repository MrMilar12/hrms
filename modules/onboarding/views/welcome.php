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
<script>(function(){try{var s=JSON.parse(localStorage.getItem('hrms-appearance')||'{}');var m=s.mode||'system';var d=m==='dark'||(m==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.dataset.theme=d?'dark':'light';}catch(e){}})();</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="auth-page onboarding-finish-page">
<div class="onboarding-card onboarding-finish-card glass-strong">
    <div class="onboarding-complete-mark"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></div>
    <div class="onboarding-finish-brand">
        <span>P</span><span><strong>Project PUNLA</strong><small>Human Resource Management</small></span>
    </div>

    <span class="onboarding-finish-eyebrow">Your workspace is ready</span>
    <h1>Welcome, <span><?= htmlspecialchars($displayName) ?></span>.</h1>
    <p class="onboarding-lead">
        Personalize your view, then continue your <strong>Personal Data Sheet (CS Form 212)</strong> to keep your official 201 file accurate and complete.
    </p>

    <div class="onboarding-steps">
        <div class="onboarding-steps-heading"><strong>What happens next?</strong><small>Three simple steps to complete your profile</small></div>
        <div class="onboarding-step">
            <span class="onboarding-step-num">01</span>
            <span><strong>Complete the essentials</strong><small>Personal information, family background, and education.</small></span>
        </div>
        <div class="onboarding-step">
            <span class="onboarding-step-num">02</span>
            <span><strong>Build your service record</strong><small>Eligibility, work experience, and other required sections.</small></span>
        </div>
        <div class="onboarding-step">
            <span class="onboarding-step-num">03</span>
            <span><strong>Unlock your workspace</strong><small>Reach <?= PDS_MIN_COMPLETION_PERCENT ?>% completion to access the rest of HRMS.</small></span>
        </div>
    </div>

    <section class="onboarding-appearance" aria-labelledby="onboarding-appearance-title">
        <div class="onboarding-appearance-heading"><span><svg viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 9 9c0-.5-.4-.8-.9-.7a6 6 0 0 1-7.4-7.4c.1-.5-.2-.9-.7-.9Z"/></svg></span><span><strong id="onboarding-appearance-title">Choose your display style</strong><small>You can change this anytime from Settings.</small></span></div>
        <div class="theme-choice-grid onboarding-theme-grid" role="radiogroup" aria-label="Display style">
            <button type="button" class="theme-choice" data-onboarding-theme="system" role="radio"><span class="theme-preview theme-preview-system"><i></i><i></i></span><strong>System</strong><small>Match device</small><b aria-hidden="true">&#10003;</b></button>
            <button type="button" class="theme-choice" data-onboarding-theme="light" role="radio"><span class="theme-preview theme-preview-light"><i></i></span><strong>Light</strong><small>Bright and clear</small><b aria-hidden="true">&#10003;</b></button>
            <button type="button" class="theme-choice" data-onboarding-theme="dark" role="radio"><span class="theme-preview theme-preview-dark"><i></i></span><strong>Dark</strong><small>Easy on the eyes</small><b aria-hidden="true">&#10003;</b></button>
        </div>
        <p class="onboarding-theme-status" id="onboarding-theme-status" aria-live="polite">Your choice saves automatically on this device.</p>
    </section>

    <?php if ($completionPercent > 0): ?>
        <div style="margin:1.25rem 0;">
            <div class="progress-bar"><div class="progress-bar-fill" data-target="<?= $completionPercent ?>"></div></div>
            <span style="font-size:0.78rem; color:var(--text-muted);"><?= $completionPercent ?>% already complete</span>
        </div>
    <?php endif; ?>

    <div class="onboarding-finish-actions">
        <a class="btn btn-primary onboarding-cta" href="<?= BASE_URL ?>/pds?required=1">Continue to your PDS <span>&rarr;</span></a>
        <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="onboarding-logout" type="submit">Not you? Sign out</button></form>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= rawurlencode(JS_ASSET_VERSION) ?>" defer></script>
<script>
(() => {
    const key = 'hrms-appearance';
    const buttons = [...document.querySelectorAll('[data-onboarding-theme]')];
    const status = document.getElementById('onboarding-theme-status');
    const media = matchMedia('(prefers-color-scheme: dark)');
    const read = () => { try { return JSON.parse(localStorage.getItem(key) || '{}'); } catch (_) { return {}; } };
    const apply = (mode, announce = false) => {
        const selected = ['system','light','dark'].includes(mode) ? mode : 'system';
        document.documentElement.dataset.theme = selected === 'dark' || (selected === 'system' && media.matches) ? 'dark' : 'light';
        buttons.forEach(button => { const active = button.dataset.onboardingTheme === selected; button.classList.toggle('selected', active); button.setAttribute('aria-checked', String(active)); });
        if (announce && status) { status.textContent = `${selected[0].toUpperCase()}${selected.slice(1)} mode selected and saved.`; }
    };
    let settings = read();
    apply(settings.mode || 'system');
    buttons.forEach(button => button.addEventListener('click', () => {
        settings = { ...read(), mode: button.dataset.onboardingTheme };
        try { localStorage.setItem(key, JSON.stringify(settings)); } catch (_) {}
        apply(settings.mode, true);
    }));
    media.addEventListener?.('change', () => { if ((read().mode || 'system') === 'system') apply('system'); });
})();
</script>
</body>
</html>
