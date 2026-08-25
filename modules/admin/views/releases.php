<?php
/** @var array $releases */
require MODULES_PATH . '/shared/views/header.php';
?>
<section class="release-admin-page">
    <div class="glass-card release-history-hero">
        <div><span class="launcher-eyebrow">Administration</span><h1>System Updates</h1><p>Create release notes and announce changes to every user.</p></div>
        <div class="release-admin-source"><span class="release-version">Current version <?= htmlspecialchars(SystemRelease::currentVersion()) ?></span><button class="btn btn-primary" type="button" id="github-release-sync">Sync from GitHub</button></div>
    </div>

    <div class="release-admin-grid">
        <div class="glass-card">
            <h2>Create an update</h2>
            <form id="release-form">
                <div class="form-row">
                    <div class="form-group"><label for="release-version">Version</label><input id="release-version" name="version" placeholder="1.2.0" pattern="\d+\.\d+\.\d+.*" required></div>
                    <div class="form-group"><label for="release-title">Title</label><input id="release-title" name="title" placeholder="August 2026 Update" maxlength="150" required></div>
                </div>
                <div class="form-group"><label for="release-changes">Changes <small>(one item per line)</small></label><textarea id="release-changes" name="changes" rows="8" placeholder="Added...&#10;Improved...&#10;Fixed..." required></textarea></div>
                <label class="release-publish-check"><input type="checkbox" name="is_published" value="1"> Publish immediately and show after login</label>
                <button class="btn btn-primary" type="submit">Save system update</button>
            </form>
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
</section>
<script>
document.getElementById('github-release-sync').addEventListener('click', async function () {
    this.disabled = true; this.textContent = 'Syncing...';
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/releases/sync`, new FormData());
    if (result.success) { HRIS.flash(result.message, 'success'); setTimeout(() => window.location.reload(), 700); }
    else { this.disabled = false; this.textContent = 'Sync from GitHub'; HRIS.flash(result.error || 'GitHub sync failed.', 'error'); }
});
document.getElementById('release-form').addEventListener('submit', async (event) => {
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
