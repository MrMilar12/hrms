<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Two-Factor Verification &mdash; HRMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css">
</head>
<body class="auth-page">
<div class="onboarding-card glass-strong">
    <div class="onboarding-icon">&#128274;</div>
    <h1>Two-Factor Verification</h1>
    <p class="onboarding-lead">Enter the 6-digit code from your authenticator app to finish signing in.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/login/verify-2fa">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
        <div class="form-group">
            <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                   placeholder="123456" required autofocus
                   style="text-align:center; font-size:1.5rem; letter-spacing:0.5rem; font-weight:700;">
        </div>
        <button type="submit" class="btn btn-primary onboarding-cta">Verify</button>
    </form>

    <a class="onboarding-logout" href="<?= BASE_URL ?>/logout">Cancel and sign out</a>
</div>
</body>
</html>
