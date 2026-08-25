<?php
/** @var int $pdsPercent */
/** @var array $taskCounts */
/** @var array $notifications */
/** @var array $myAccomplishmentCounts */
/** @var array $unseenReleases */
require MODULES_PATH . '/shared/views/header.php';

$firstName = explode(' ', trim(Auth::displayName()))[0] ?: 'there';
$isAdmin = Auth::roleName() === ROLE_ADMIN || Auth::isDeveloper();
$myTaskTotal = array_sum($taskCounts);
$myAccomplishmentTotal = array_sum($myAccomplishmentCounts);
?>
<section class="launcher-page" aria-labelledby="launcher-title">
    <div class="launcher-hero">
        <div>
            <span class="launcher-eyebrow">HRMS Workspace</span>
            <h1 id="launcher-title">Good day, <?= htmlspecialchars($firstName) ?>.</h1>
            <p>Choose an application to get started.</p>
        </div>
        <div class="launcher-date"><span><?= htmlspecialchars(date('l')) ?></span><strong><?= htmlspecialchars(date('M j')) ?></strong></div>
    </div>

    <div class="launcher-grid">
        <?php if ($isAdmin): ?>
            <a class="launcher-app launcher-app-blue" href="<?= BASE_URL ?>/employees">
                <span class="launcher-app-icon" aria-hidden="true">&#128101;</span>
                <span class="launcher-app-copy"><strong>Employees</strong><small>Manage employee records and 201 files</small></span>
                <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <a class="launcher-app launcher-app-violet" href="<?= BASE_URL ?>/profile">
            <span class="launcher-app-icon" aria-hidden="true">&#128100;</span>
            <span class="launcher-app-copy"><strong>My Profile</strong><small>Update your information and manage your PDS</small></span>
            <span class="launcher-app-meta"><?= (int) $pdsPercent ?>%</span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <a class="launcher-app launcher-app-teal" href="<?= BASE_URL ?>/tasks">
            <span class="launcher-app-icon" aria-hidden="true">&#10003;</span>
            <span class="launcher-app-copy"><strong>Tasks</strong><small>Open your work board and assignments</small></span>
            <span class="launcher-app-meta"><?= number_format($myTaskTotal) ?></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <?php if (Auth::can('accomplishment.create')): ?>
            <a class="launcher-app launcher-app-orange" href="<?= BASE_URL ?>/accomplishments">
                <span class="launcher-app-icon" aria-hidden="true">&#10022;</span>
                <span class="launcher-app-copy"><strong>Accomplishments</strong><small>Document evidence and completed work</small></span>
                <span class="launcher-app-meta"><?= number_format($myAccomplishmentTotal) ?></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::can('report.view')): ?>
            <a class="launcher-app launcher-app-pink" href="<?= BASE_URL ?>/reports/pds-completion">
                <span class="launcher-app-icon" aria-hidden="true">&#9635;</span>
                <span class="launcher-app-copy"><strong>Reports</strong><small>Review team progress and completion</small></span>
                <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::can('user.manage')): ?>
            <a class="launcher-app launcher-app-slate" href="<?= BASE_URL ?>/admin/dashboard">
                <span class="launcher-app-icon" aria-hidden="true">&#9632;</span>
                <span class="launcher-app-copy"><strong>Admin Analytics</strong><small>Statistics, trends and workforce insights</small></span>
                <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="launcher-lower-grid">
        <section class="launcher-panel glass" aria-labelledby="quick-status-title">
            <div class="launcher-panel-heading"><div><span class="launcher-eyebrow">At a glance</span><h2 id="quick-status-title">Your workspace</h2></div><a href="<?= BASE_URL ?>/tasks">View tasks &rarr;</a></div>
            <div class="launcher-stats">
                <div><strong><?= (int) ($taskCounts['Open'] ?? 0) ?></strong><span>Open tasks</span></div>
                <div><strong><?= (int) ($taskCounts['In Progress'] ?? 0) ?></strong><span>In progress</span></div>
                <div><strong><?= (int) ($taskCounts['For Review'] ?? 0) ?></strong><span>For review</span></div>
                <div><strong><?= (int) ($myAccomplishmentCounts['Approved'] ?? 0) ?></strong><span>Approved</span></div>
            </div>
        </section>

        <section class="launcher-panel glass" aria-labelledby="recent-title">
            <div class="launcher-panel-heading"><div><span class="launcher-eyebrow">Updates</span><h2 id="recent-title">Recent notifications</h2></div></div>
            <div class="launcher-notifications">
                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                    <a class="launcher-notification notification-link" data-notification-id="<?= (int) $notification['id'] ?>" href="<?= htmlspecialchars($notification['link'] ?: '#') ?>"><span class="status-dot"></span><span><?= htmlspecialchars($notification['message']) ?><small><?= htmlspecialchars($notification['created_at']) ?></small></span></a>
                <?php endforeach; ?>
                <?php if (!$notifications): ?><p class="launcher-empty">You are all caught up.</p><?php endif; ?>
            </div>
        </section>
    </div>
</section>

<?php if ($unseenReleases): ?>
<div class="release-modal-backdrop" id="release-modal" role="dialog" aria-modal="true" aria-labelledby="release-modal-title">
    <div class="release-modal glass-strong">
        <div class="release-modal-heading">
            <div><span class="launcher-eyebrow">What's New</span><h2 id="release-modal-title">HRMS has been updated</h2></div>
            <span class="release-version">v<?= htmlspecialchars($unseenReleases[0]['version']) ?></span>
        </div>
        <div class="release-modal-list">
            <?php foreach ($unseenReleases as $release): ?>
                <article class="release-note">
                    <div class="release-note-title"><h3><?= htmlspecialchars($release['title']) ?></h3><time><?= htmlspecialchars(date('M j, Y', strtotime($release['released_at']))) ?></time></div>
                    <div class="release-changes"><?= nl2br(htmlspecialchars($release['changes'])) ?></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="release-modal-actions"><a href="<?= BASE_URL ?>/updates" class="btn btn-secondary">View update history</a><button type="button" class="btn btn-primary" id="acknowledge-releases">Got it</button></div>
    </div>
</div>
<script>
document.getElementById('acknowledge-releases').addEventListener('click', async function () {
    this.disabled = true;
    const data = new FormData();
    <?php foreach ($unseenReleases as $release): ?>data.append('release_ids[]', '<?= (int) $release['id'] ?>');<?php endforeach; ?>
    const result = await HRIS.postForm(`${window.BASE_URL}/updates/acknowledge`, data);
    if (result.success) document.getElementById('release-modal').remove();
    else { this.disabled = false; HRIS.flash(result.error || 'Could not save your response.', 'error'); }
});
</script>
<?php endif; ?>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
