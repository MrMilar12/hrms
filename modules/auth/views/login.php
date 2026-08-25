<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HRMS &mdash; Login</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css">
</head>
<body class="auth-page">
<div class="auth-shell"> 
    <div class="auth-branding">
        <div class="brand-mark"><span class="brand-icon">&#10024;</span> HRIS</div>
        <div>
            <h1>Welcome back to&nbsp;eManage.</h1>
            <p class="lead">Manage employee records, Personal Data Sheets, and tasks &mdash; all in one place.</p>
            <div class="auth-feature-list">
                 <div class="feature"><span class="feature-icon">&#128221;</span> Phase 1-Project PUNLA- Personel Unified Nurturing Labor Administration</div>
                <div class="feature"><span class="feature-icon">&#128203;</span> CS Form 212 (PDS) with completion tracking</div>
                <div class="feature"><span class="feature-icon">&#10003;</span> Task management with photo attachments</div>
                <div class="feature"><span class="feature-icon">&#10003;</span> Task management with photo attachments</div>
                

            </div>
        </div>
        <div class="auth-footnote">&copy; <?= date('Y') ?> HRMS &middot; Human Resource Management System</div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <h1>Sign in</h1>
            <p class="subtitle">Enter your credentials to access your account.</p>

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

                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Sign In</button>
            </form>

            <div class="auth-form-footer">Trouble signing in? Contact your HR administrator.</div>
        </div>
    </div>
</div>
<script>
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
