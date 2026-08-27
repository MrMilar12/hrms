<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HRMS &mdash; Login</title>
<script>
(function () {
    try {
        const settings = JSON.parse(localStorage.getItem('hrms-appearance') || '{}');
        const mode = settings.mode || 'system';
        const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    } catch (_) {
        document.documentElement.dataset.theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="auth-page">
<button class="auth-theme-toggle" id="auth-theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch theme">
    <svg class="theme-icon-sun" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.41M17.66 6.34l1.41-1.41"/></svg>
    <svg class="theme-icon-moon" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
</button>
<main class="auth-shell">
    <div class="auth-branding">
        <div class="brand-mark">
            <span class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            </span>
            <span><strong>HRMS</strong><small>Human Resource Management System</small></span>
        </div>
        <div class="auth-brand-copy">
            <span class="auth-eyebrow">Your people. One platform.</span>
            <h1>Empowering people,<br>simplifying work.</h1>
            <p class="lead">A secure workspace for employee records, Personal Data Sheets, and daily operations.</p>
            <div class="auth-feature-list">
                <div class="feature"><span class="feature-icon">01</span><span><strong>Project PUNLA</strong><small>Personnel Unified Nurturing Labor Administration</small></span></div>
                <div class="feature"><span class="feature-icon">02</span><span><strong>Digital PDS</strong><small>CS Form 212 with completion tracking</small></span></div>
                <div class="feature"><span class="feature-icon">03</span><span><strong>Smarter workflows</strong><small>Task management and photo attachments</small></span></div>
            </div>
        </div>
        <div class="auth-footnote"><span class="status-dot"></span> Secure HR workspace <span>&copy; <?= date('Y') ?> HRMS</span></div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <div class="auth-mobile-brand">
                <span class="mobile-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                </span>
                <span><strong>HRMS</strong><small>Human Resource Management System</small></span>
            </div>
            <span class="form-eyebrow">Welcome back</span>
            <h1>Sign in to your account</h1>
            <p class="subtitle">Enter your credentials to continue to the HRMS workspace.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/login">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-icon-group">
                        <span class="field-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="username" name="username" required autofocus autocomplete="username" placeholder="Enter your username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-group with-toggle">
                        <span class="field-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        <button type="button" class="toggle-visibility" id="toggle-password" aria-label="Show password">
                            <svg class="icon-eye-open" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-closed" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 4.22-5.19M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 7 11 7a20.4 20.4 0 0 1-3.22 4.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                        </button>
                    </div>
                </div>

                <div class="auth-options">
                    <label class="checkbox-field">
                        <input type="checkbox" name="remember">
                        <span class="checkbox-box"></span>
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    Sign in
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <div class="auth-form-footer"><span aria-hidden="true">&#128274;</span> Your account is protected by secure authentication.</div>
        </div>
    </div>
</main>
<script>
const themeToggle = document.getElementById('auth-theme-toggle');
const syncThemeToggle = () => {
    const dark = document.documentElement.dataset.theme === 'dark';
    themeToggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    themeToggle.setAttribute('aria-pressed', String(dark));
};
themeToggle?.addEventListener('click', () => {
    const nextMode = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = nextMode;
    try {
        const settings = JSON.parse(localStorage.getItem('hrms-appearance') || '{}');
        localStorage.setItem('hrms-appearance', JSON.stringify({ ...settings, mode: nextMode }));
    } catch (_) {}
    syncThemeToggle();
});
syncThemeToggle();

document.getElementById('toggle-password')?.addEventListener('click', (e) => {
    const btn = e.currentTarget;
    const input = document.getElementById('password');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    btn.querySelector('.icon-eye-open').style.display = showing ? '' : 'none';
    btn.querySelector('.icon-eye-closed').style.display = showing ? 'none' : '';
    btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
});
</script>
</body>
</html>
