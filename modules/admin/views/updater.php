<?php
/** @var ?array $updateStatus */
/** @var ?string $statusError */
/** @var array $deployments */
require MODULES_PATH . '/shared/views/header.php';
$available = !empty($updateStatus['update_available']);
$clean = !empty($updateStatus['working_tree_clean']);
$writable = !empty($updateStatus['deployment_writable']);
?>
<section class="updater-page">
    <div class="glass-card updater-hero">
        <div class="updater-hero-icon" aria-hidden="true">&#8635;</div>
        <div><span class="launcher-eyebrow">Developer deployment</span><h1>System Updater</h1><p>Securely deploy updates pushed to <strong><?= htmlspecialchars(GITHUB_REPOSITORY) ?> / main</strong>.</p></div>
        <span class="release-version">Installed <?= htmlspecialchars(SystemRelease::currentVersion()) ?></span>
    </div>

    <div class="glass-card updater-status <?= $available ? 'has-update' : '' ?>">
        <?php if ($statusError): ?>
            <div><h2>Unable to check GitHub</h2><p><?= htmlspecialchars($statusError) ?></p></div>
            <button type="button" class="btn btn-secondary" onclick="window.location.reload()">Try again</button>
        <?php elseif ($available): ?>
            <div><span class="updater-signal"></span><h2>New version detected</h2><p>Commit <code><?= htmlspecialchars(substr($updateStatus['remote_sha'], 0, 12)) ?></code> is ready on GitHub.</p><?php if (!$clean): ?><p class="updater-warning">Update is blocked because this server has uncommitted files. Commit and push them first.</p><?php endif; ?><?php if (!$writable): ?><p class="updater-warning">A privileged deployment worker is required because Apache cannot write the repository.</p><?php endif; ?></div>
            <button type="button" class="btn btn-primary" id="apply-system-update" <?= (!$clean || !$writable) ? 'disabled' : '' ?>>Update now</button>
        <?php else: ?>
            <div><span class="updater-signal current"></span><h2>System is up to date</h2><p>Commit <code><?= htmlspecialchars(substr($updateStatus['local_sha'], 0, 12)) ?></code> matches GitHub.</p></div>
            <button type="button" class="btn btn-secondary" onclick="window.location.reload()">Check again</button>
        <?php endif; ?>
    </div>

    <div class="glass-card updater-safety"><h2>Automatic deployment safeguards</h2><div class="updater-safety-grid"><span>Database backup</span><span>Tracked-file archive</span><span>Fast-forward only</span><span>Migration tracking</span><span>Health check</span><span>Automatic rollback</span></div></div>

    <div class="glass-card"><h2>Deployment history</h2><div class="updater-history">
        <?php foreach ($deployments as $deployment): ?><article><span class="badge <?= $deployment['status'] === 'success' ? 'badge-success' : 'badge-danger' ?>"><?= htmlspecialchars(ucfirst($deployment['status'])) ?></span><div><strong><?= htmlspecialchars(($deployment['from_version'] ?: 'Unknown') . ' → ' . ($deployment['to_version'] ?: 'Rollback')) ?></strong><small><?= htmlspecialchars($deployment['username'] ?? 'System') ?> · <?= htmlspecialchars(date('M j, Y g:i A', strtotime($deployment['started_at']))) ?></small><p><?= htmlspecialchars($deployment['details'] ?: 'No deployment notes.') ?></p></div></article><?php endforeach; ?>
        <?php if (!$deployments): ?><p class="release-empty">No automatic deployments recorded yet.</p><?php endif; ?>
    </div></div>
</section>
<?php if ($available && $clean && $writable): ?><script>
document.getElementById('apply-system-update').addEventListener('click', async function () {
    const confirmation = await Swal.fire({ icon: 'warning', title: 'Install the GitHub update?', html: 'HRMS will enter maintenance mode, back up the database and files, then deploy <strong><?= htmlspecialchars(substr($updateStatus['remote_sha'], 0, 12)) ?></strong>.', showCancelButton: true, confirmButtonText: 'Update now', confirmButtonColor: '#2563eb', allowOutsideClick: false });
    if (!confirmation.isConfirmed) return;
    this.disabled = true; this.textContent = 'Updating…';
    Swal.fire({ title: 'Installing update', text: 'Do not close this page. The system will verify and roll back automatically if needed.', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/updater/apply`, new FormData());
    if (result.success) await Swal.fire({ icon: 'success', title: result.updated ? `Version ${result.version} installed` : 'Already up to date', text: result.message });
    else await Swal.fire({ icon: 'error', title: 'Update was not installed', text: result.error || 'The deployment failed and rollback was attempted.' });
    window.location.reload();
});
</script><?php endif; ?>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
