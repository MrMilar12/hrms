<?php
/** @var array $releases */
/** @var ?array $githubStatus */
/** @var ?string $githubError */
/** @var array $deployments */
require MODULES_PATH . '/shared/views/header.php';
?>
<section class="release-admin-page">
    <div class="glass-card release-history-hero">
        <div><span class="launcher-eyebrow">Administration</span><h1>System Updates</h1><p>Create release notes and announce changes to every user.</p></div>
        <span class="release-version">Current version <?= htmlspecialchars(SystemRelease::currentVersion()) ?></span>
    </div>

    <div class="release-admin-grid">
        <div class="glass-card">
            <?php if ($githubError): ?>
                <span class="launcher-eyebrow">GitHub status</span><h2>Unable to check GitHub</h2><p class="release-empty"><?= htmlspecialchars($githubError) ?></p>
            <?php elseif (!empty($githubStatus['notification_needed'])): ?>
            <div class="release-detected-heading"><div><span class="updater-signal"></span><span class="launcher-eyebrow">New update detected</span><h2>Publish version notification</h2></div><code><?= htmlspecialchars(substr($githubStatus['remote_sha'], 0, 12)) ?></code></div>
            <form id="release-form">
                <div class="form-row">
                    <div class="form-group"><label for="release-version">Version</label><input id="release-version" name="version" placeholder="1.2.0" pattern="\d+\.\d+\.\d+.*" required></div>
                    <div class="form-group"><label for="release-title">Title</label><input id="release-title" name="title" placeholder="August 2026 Update" maxlength="150" required></div>
                </div>
                <div class="form-group"><label for="release-changes">Changes <small>(one item per line)</small></label><textarea id="release-changes" name="changes" rows="8" placeholder="Added...&#10;Improved...&#10;Fixed..." required></textarea></div>
                <label class="release-publish-check"><input type="checkbox" name="is_published" value="1"> Publish immediately and show after login</label>
                <button class="btn btn-primary" type="submit">Save version notification</button>
            </form>
            <?php elseif (!empty($githubStatus['update_available']) && !empty($githubStatus['version_ready'])): ?>
                <div class="release-detected-heading"><div><span class="updater-signal"></span><span class="launcher-eyebrow">Ready to install</span><h2>Version <?= htmlspecialchars($githubStatus['new_version']) ?></h2></div><code><?= htmlspecialchars(substr($githubStatus['remote_sha'], 0, 12)) ?></code></div>
                <p>The version notification is published. HRMS can now download and replace the updated application files from GitHub.</p>
                <?php if (empty($githubStatus['deployment_writable'])): ?><p class="updater-warning">PHP does not have permission to replace the application files on this server.</p><?php endif; ?>
                <button class="btn btn-primary" type="button" id="apply-system-update" <?= empty($githubStatus['deployment_writable']) ? 'disabled' : '' ?>>Update now</button>
            <?php elseif (!empty($githubStatus['update_available'])): ?>
                <span class="updater-signal"></span><span class="launcher-eyebrow">Publication required</span><h2>Release draft waiting</h2><p class="release-empty">Publish the draft from Release history to enable Update now.</p>
            <?php else: ?>
                <span class="updater-signal current"></span><span class="launcher-eyebrow">GitHub status</span><h2>No new update</h2><p class="release-empty">Version controls will appear here after a new commit is pushed to the main branch.</p>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <h2>Release history</h2>
            <div class="release-admin-list">
                <?php foreach ($releases as $release): ?>
                    <article>
                        <div><strong>v<?= htmlspecialchars($release['version']) ?> — <?= htmlspecialchars($release['title']) ?></strong><small><?= htmlspecialchars(date('M j, Y g:i A', strtotime($release['released_at']))) ?> · <?= (int) $release['view_count'] ?> viewed</small></div>
                        <?php if ((int) $release['is_published']): ?><span class="badge badge-success">Published</span><?php else: ?><button class="btn btn-sm btn-primary" type="button" onclick="publishRelease(<?= (int) $release['id'] ?>)">Publish</button><?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$releases): ?><p class="release-empty">No releases created yet.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="glass-card" style="margin-top:1rem;">
        <h2>Installation history</h2>
        <div class="updater-history">
            <?php foreach ($deployments as $deployment): ?>
                <article><span class="badge <?= $deployment['status'] === 'success' ? 'badge-success' : 'badge-danger' ?>"><?= htmlspecialchars(ucfirst($deployment['status'])) ?></span><div><strong><?= htmlspecialchars(($deployment['from_version'] ?: 'Unknown') . ' → ' . ($deployment['to_version'] ?: 'Rollback')) ?></strong><small><?= htmlspecialchars($deployment['username'] ?? 'System') ?> · <?= htmlspecialchars(date('M j, Y g:i A', strtotime($deployment['started_at']))) ?></small><p><?= htmlspecialchars($deployment['details'] ?: 'No deployment notes.') ?></p></div></article>
            <?php endforeach; ?>
            <?php if (!$deployments): ?><p class="release-empty">No automatic installations recorded yet.</p><?php endif; ?>
        </div>
    </div>
</section>
<script>
document.getElementById('apply-system-update')?.addEventListener('click', async function () {
    const confirmation = await Swal.fire({ icon: 'warning', title: 'Install this system update?', html: 'HRMS will back up current files, enter maintenance mode, and install <strong>Version <?= htmlspecialchars($githubStatus['new_version'] ?? '') ?></strong> from GitHub.', showCancelButton: true, confirmButtonText: 'Update now', confirmButtonColor: '#2563eb', allowOutsideClick: false });
    if (!confirmation.isConfirmed) return;
    this.disabled = true;
    Swal.fire({ title: 'Updating HRMS', html: `<div class="system-update-animation"><div class="update-orbit"><span></span><span></span><span></span><b>&#8681;</b></div><p id="live-update-message">Preparing the secure update…</p><div class="live-update-track"><i id="live-update-bar"></i></div><strong id="live-update-percent">0%</strong><small>Keep this page open. HRMS will roll back automatically if verification fails.</small></div>`, allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
    let polling = true;
    const pollProgress = async () => {
        while (polling) {
            try {
                const response = await fetch(`${window.BASE_URL}/admin/updater/progress`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                const data = await response.json(); const progress = data.progress || {};
                const percent = Math.max(0, Math.min(100, Number(progress.percent) || 0));
                const bar = document.getElementById('live-update-bar'); const label = document.getElementById('live-update-percent'); const message = document.getElementById('live-update-message');
                if (bar) bar.style.width = `${percent}%`; if (label) label.textContent = `${percent}%`; if (message && progress.message) message.textContent = progress.message;
            } catch (error) {}
            await new Promise(resolve => setTimeout(resolve, 600));
        }
    };
    const pollingPromise = pollProgress();
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/updater/apply`, new FormData());
    polling = false; await pollingPromise;
    if (result.success) await Swal.fire({ icon: 'success', title: result.updated ? `Version ${result.version} installed` : 'Already up to date', text: result.message });
    else await Swal.fire({ icon: 'error', title: 'Update was not installed', text: result.error || 'Installation failed and file rollback was attempted.' });
    window.location.reload();
});
document.getElementById('release-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/releases/store`, new FormData(event.target));
    if (result.success) window.location.reload();
    else HRIS.flash(result.error || 'Could not save the update.', 'error');
});
async function publishRelease(id) {
    if (!confirm('Publish this update? All users who have not seen it will be notified.')) return;
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/releases/${id}/publish`, new FormData());
    if (result.success) window.location.reload();
    else HRIS.flash(result.error || 'Could not publish the update.', 'error');
}
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
