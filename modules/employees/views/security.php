<?php
/** @var array $account */
/** @var string|null $secret */
/** @var string|null $qrDataUri */
/** @var string|null $error */
/** @var bool $enabledMessage */
/** @var bool $disabledMessage */
require MODULES_PATH . '/shared/views/header.php';
$enabled = (bool) $account['two_factor_enabled'];
?>
<div class="security-page">
    <section class="security-hero glass-card">
        <div class="security-hero-icon">&#128737;</div>
        <div><span class="launcher-eyebrow">Account protection</span><h1>Two-factor authentication</h1><p>Use a time-based code from your phone in addition to your password.</p></div>
        <span class="security-status <?= $enabled ? 'enabled' : '' ?>"><i></i><?= $enabled ? 'Protected' : 'Protection off' ?></span>
    </section>

    <?php if ($error): ?><div class="alert alert-error" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($enabledMessage): ?><div class="alert alert-success" style="display:block;">Two-factor authentication is now enabled.</div><?php endif; ?>
    <?php if ($disabledMessage): ?><div class="alert alert-success" style="display:block;">Two-factor authentication has been disabled.</div><?php endif; ?>

    <?php if (!$enabled): ?>
    <div class="security-setup-grid">
        <section class="glass-card security-steps">
            <span class="security-step-number">1</span>
            <h2>Scan the QR code</h2>
            <p>Open Google Authenticator, Microsoft Authenticator, Authy, or another TOTP app and scan this code.</p>
            <div class="security-qr"><img src="<?= htmlspecialchars($qrDataUri ?? '') ?>" alt="Authenticator setup QR code"></div>
            <details class="security-manual"><summary>Can&rsquo;t scan the code?</summary><p>Enter this setup key manually:</p><code><?= htmlspecialchars($secret ?? '') ?></code></details>
        </section>

        <section class="glass-card security-steps">
            <span class="security-step-number">2</span>
            <h2>Verify and activate</h2>
            <p>Enter the latest six-digit code from your authenticator app and confirm with your current password.</p>
            <form method="post" action="<?= BASE_URL ?>/profile/security/2fa/enable" class="security-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                <div class="form-group"><label>Authenticator code</label><input class="security-code-input" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required></div>
                <div class="form-group"><label>Current password</label><input type="password" name="password" autocomplete="current-password" required></div>
                <button class="btn btn-primary" type="submit">Enable two-factor authentication</button>
            </form>
        </section>
    </div>
    <?php else: ?>
    <section class="glass-card security-enabled-card">
        <div class="security-success-mark">&#10003;</div>
        <div><h2>Your account has extra protection</h2><p>Each new login requires your password and the six-digit code from your authenticator app.</p></div>
    </section>
    <section class="glass-card security-disable-card">
        <div><span class="launcher-eyebrow">Security settings</span><h2>Disable two-factor authentication</h2><p>This reduces your account security. Confirm your current password to continue.</p></div>
        <form method="post" action="<?= BASE_URL ?>/profile/security/2fa/disable" class="security-disable-form" onsubmit="return confirm('Disable two-factor authentication for this account?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
            <div class="form-group"><label>Current password</label><input type="password" name="password" autocomplete="current-password" required></div>
            <button class="btn btn-danger" type="submit">Disable 2FA</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
