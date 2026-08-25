<?php
/** @var array $releases */
/** @var ?array $githubStatus */
/** @var ?string $githubError */
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
</section>
<script>
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
