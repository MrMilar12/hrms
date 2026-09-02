<?php
/** @var int $pdsPercent */
/** @var array $taskCounts */
/** @var array $notifications */
/** @var array $myAccomplishmentCounts */
/** @var array $unseenReleases */
/** @var array $dashboardProfile */
require MODULES_PATH . '/shared/views/header.php';

$firstName = explode(' ', trim(Auth::displayName()))[0] ?: 'there';
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
            <a class="profile-app-card" href="<?= BASE_URL ?>/profile" aria-label="Open my profile">
                <span class="profile-app-identity">
                    <span class="profile-app-photo"><?php if (!empty($dashboardProfile['photo_id'])): ?><img src="<?= BASE_URL ?>/photo/<?= UrlId::encode((int) $dashboardProfile['photo_id']) ?>" alt="<?= htmlspecialchars($dashboardProfile['display_name'] ?? 'Profile') ?>"><?php else: ?><?= htmlspecialchars(strtoupper(substr($dashboardProfile['display_name'] ?? Auth::displayName() ?: '?', 0, 1))) ?><?php endif; ?><i aria-hidden="true"></i></span>
                    <span class="profile-app-name"><small>Signed in as</small><strong><?= htmlspecialchars($dashboardProfile['display_name'] ?? Auth::displayName()) ?></strong><span><i aria-hidden="true"></i><?= htmlspecialchars($dashboardProfile['role_name'] ?? Auth::roleName() ?? '') ?></span></span>
                </span>
                <span class="profile-app-details">
                    <span><i aria-hidden="true">#</i><span><small>Employee number</small><strong><?= htmlspecialchars($dashboardProfile['employee_number'] ?? 'Not assigned') ?></strong></span></span>
                    <span><i aria-hidden="true">&#9671;</i><span><small>Position</small><strong><?= htmlspecialchars($dashboardProfile['position_title'] ?? 'Not assigned') ?></strong></span></span>
                    <span><i aria-hidden="true">&#9638;</i><span><small>Department</small><strong><?= htmlspecialchars($dashboardProfile['department_name'] ?? 'Not assigned') ?></strong></span></span>
                </span>
                <span class="profile-app-footer">
                    <span class="profile-app-progress"><span><small>PDS completion</small><strong><?= (int) $pdsPercent ?>%</strong></span><span class="profile-app-track"><i style="width:<?= min(100, max(0, (int) $pdsPercent)) ?>%"></i></span></span>
                    <span class="profile-app-open">View profile <b aria-hidden="true">&rarr;</b></span>
                </span>
            </a>

        <a class="launcher-app launcher-app-violet" href="<?= BASE_URL ?>/pds">
            <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
            <span class="launcher-app-copy"><strong>My PDS</strong><small>Complete and update your personal data sheet</small></span>
            <span class="launcher-app-meta"><?= (int) $pdsPercent ?>%</span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <?php if (Auth::can('employee.view')): ?>
            <a class="launcher-app launcher-app-blue" href="<?= BASE_URL ?>/employees">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6m1 3a5 5 0 0 1 4 5"/></svg></span>
                <span class="launcher-app-copy"><strong>Employees</strong><small>Open the personnel directory and employee records</small></span>
                <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <a class="launcher-app launcher-app-teal" href="<?= BASE_URL ?>/tasks">
            <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="m8 12 2.5 2.5L16 9"/></svg></span>
            <span class="launcher-app-copy"><strong>Tasks</strong><small>Open your work board and assignments</small></span>
            <span class="launcher-app-meta"><?= number_format($myTaskTotal) ?></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <?php if (Auth::can('accomplishment.create')): ?>
            <a class="launcher-app launcher-app-orange" href="<?= BASE_URL ?>/accomplishments">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.8-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.2 5.9-.8L12 3Z"/></svg></span>
                <span class="launcher-app-copy"><strong>Accomplishments</strong><small>Document evidence and completed work</small></span>
                <span class="launcher-app-meta"><?= number_format($myAccomplishmentTotal) ?></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::can('report.view')): ?>
            <a class="launcher-app launcher-app-pink" href="<?= BASE_URL ?>/reports/pds-completion">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
                <span class="launcher-app-copy"><strong>Reports</strong><small>Review team progress and completion</small></span>
                <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::can('report.view')): ?>
            <a class="launcher-app launcher-app-slate" href="<?= BASE_URL ?>/admin/dashboard">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg></span>
                <span class="launcher-app-copy"><strong>Admin Analytics</strong><small>Statistics, trends and workforce insights</small></span>
                <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <a class="launcher-app launcher-app-violet" href="<?= BASE_URL ?>/ai">
            <span class="launcher-app-icon" aria-hidden="true">✦</span><span class="launcher-app-copy"><strong>HRMS AI Assistant</strong><small>Ask your connected Llama model</small></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <a class="launcher-app launcher-app-blue" href="<?= BASE_URL ?>/settings?embed=1" data-settings-modal-open>
            <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg></span>
            <span class="launcher-app-copy"><strong>Settings</strong><small>Choose your theme, appearance and preferences</small></span>
            <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <a class="launcher-app launcher-app-violet" href="<?= BASE_URL ?>/updates">
            <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4L12 3Z"/><path d="m18.5 14 .8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2Z"/></svg></span>
            <span class="launcher-app-copy"><strong>What’s New</strong><small>View recent HRMS improvements and release notes</small></span>
            <span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <button class="launcher-app launcher-app-slate launcher-app-menu-card" id="menu-toggle" type="button" aria-label="Open all applications" aria-expanded="false">
            <span class="launcher-app-icon launcher-grid-icon" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
            <span class="launcher-app-copy"><strong>All Apps</strong><small>Browse the complete HRMS application menu</small></span>
            <span class="launcher-app-meta">Menu</span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
        </button>

        <?php if (Auth::can('user.manage')): ?>
            <a class="launcher-app launcher-app-orange" href="<?= BASE_URL ?>/admin/activity">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h4"/></svg></span>
                <span class="launcher-app-copy"><strong>Activity Logs</strong><small>Review system activity and the audit trail</small></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
            <a class="launcher-app launcher-app-pink" href="<?= BASE_URL ?>/admin/users">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="8" cy="9" r="3"/><path d="M3 20a5 5 0 0 1 10 0M16 8h5M18.5 5.5v5M15 15h6v5h-6z"/></svg></span>
                <span class="launcher-app-copy"><strong>Accounts</strong><small>Manage user access, roles and account status</small></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
            <a class="launcher-app launcher-app-teal" href="<?= BASE_URL ?>/admin/departments">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 21V8l8-5 8 5v13M8 21v-8h8v8M2 21h20"/></svg></span>
                <span class="launcher-app-copy"><strong>Departments</strong><small>Maintain the organization’s department structure</small></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
            <a class="launcher-app launcher-app-slate" href="<?= BASE_URL ?>/admin/positions">
                <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM9 7V4h6v3M4 12h16M10 12v2h4v-2"/></svg></span>
                <span class="launcher-app-copy"><strong>Positions</strong><small>Manage the government position catalogue</small></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
            </a>
            <?php if (Auth::isDeveloper()): ?>
                <a class="launcher-app launcher-app-blue" href="<?= BASE_URL ?>/admin/releases">
                    <span class="launcher-app-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 5c3-2 5-2 7-2 0 2 0 4-2 7l-5 5-5-5 5-5ZM9 10l-4 1-2 4 6-1M14 15l-1 6 4-2 1-4M8 16l-2 2"/></svg></span>
                    <span class="launcher-app-copy"><strong>System Updates</strong><small>Manage releases and HRMS deployment updates</small></span><span class="launcher-app-arrow" aria-hidden="true">&rarr;</span>
                </a>
            <?php endif; ?>
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

<nav class="dashboard-tabbar glass-strong" aria-label="Dashboard quick navigation">
    <a class="active" href="<?= BASE_URL ?>/dashboard" aria-current="page"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5V21h-6v-6H9v6H3v-9.5Z"/></svg></span><strong>Home</strong></a>
    <a href="<?= BASE_URL ?>/tasks"><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><strong>Tasks</strong></a>
    <?php if (Auth::can('accomplishment.create')): ?>
        <a class="dashboard-tabbar-primary" href="<?= BASE_URL ?>/accomplishments/create"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><strong>Add</strong></a>
    <?php else: ?>
        <a class="dashboard-tabbar-primary" href="<?= BASE_URL ?>/profile"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><strong>Profile</strong></a>
    <?php endif; ?>
    <?php if (Auth::can('report.view')): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span><strong>Analytics</strong></a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/accomplishments"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8L12 3Z"/></svg></span><strong>Work</strong></a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/profile"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><strong>Profile</strong></a>
</nav>

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
