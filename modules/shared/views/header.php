<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'HRMS';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isActive = fn(string $needle) => str_contains($currentPath, $needle) ? 'active' : '';
$normalizedCurrentPath = rtrim($currentPath, '/');
$isDashboard = strcasecmp($normalizedCurrentPath, rtrim(BASE_URL, '/')) === 0
    || strcasecmp($normalizedCurrentPath, rtrim(BASE_URL . '/dashboard', '/')) === 0;
$displayName = Auth::check() ? Auth::displayName() : '';
$initials = strtoupper(substr($displayName ?: '?', 0, 1));
$openAppDrawer = false;

$headerNotifications = [];
$unreadNotificationCount = 0;
if (Auth::check()) {
    $stmt = Database::getInstance()->prepare(
        'SELECT id, message, link, is_read, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 6'
    );
    $stmt->execute([Auth::userId()]);
    $headerNotifications = $stmt->fetchAll();
    $unreadStmt = Database::getInstance()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([Auth::userId()]);
    $unreadNotificationCount = (int) $unreadStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken()) ?>">
<title><?= htmlspecialchars($pageTitle) ?> &mdash; HRMS</title>
<script>
(function(){try{var s=JSON.parse(localStorage.getItem('hrms-appearance')||'{}');var m=s.mode||'system';var d=m==='dark'||(m==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.dataset.theme=d?'dark':'light';if(/^#[0-9a-f]{6}$/i.test(s.primary||''))document.documentElement.style.setProperty('--accent-blue',s.primary);if(/^#[0-9a-f]{6}$/i.test(s.secondary||''))document.documentElement.style.setProperty('--accent-violet',s.secondary);document.documentElement.style.setProperty('--accent-primary','var(--accent-blue)');}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BASE_URL = '<?= BASE_URL ?>'; window.OPEN_APP_DRAWER = <?= $openAppDrawer ? 'true' : 'false' ?>;</script>
</head>
<body>
<div id="flash-message" class="alert" style="display:none; position:fixed; top:1rem; right:1rem; z-index:999; max-width:320px;"></div>

<div class="app-shell">
    <div class="app-drawer-backdrop <?= $openAppDrawer ? 'open' : '' ?>" id="app-drawer-backdrop" aria-hidden="<?= $openAppDrawer ? 'false' : 'true' ?>"></div>
    <aside class="app-drawer glass-strong <?= $openAppDrawer ? 'open' : '' ?>" id="app-drawer" aria-label="Application navigation" aria-hidden="<?= $openAppDrawer ? 'false' : 'true' ?>">
        <div class="drawer-heading">
            <div class="drawer-brand"><span class="brand-dot"></span><span><strong>HRMS</strong><small>Workspace</small></span></div>
            <button class="drawer-close" id="drawer-close" type="button" aria-label="Close app drawer">&times;</button>
        </div>
        <p class="drawer-kicker">Applications</p>
        <p class="drawer-intro">Choose a workspace to continue.</p>
        <nav class="app-card-grid">
            <a class="app-nav-card <?= $isActive('/dashboard') ?: $isActive(BASE_URL . '/') ?>" href="<?= BASE_URL ?>/dashboard"><span class="app-nav-icon">&#9638;</span><span><strong>Dashboard</strong><small>Overview</small></span></a>
            <?php if (Auth::can('employee.view')): ?>
                <a class="app-nav-card <?= $isActive('/employees') ?>" href="<?= BASE_URL ?>/employees"><span class="app-nav-icon">&#128100;</span><span><strong>Employees</strong><small>People directory</small></span></a>
            <?php endif; ?>
            <a class="app-nav-card <?= $isActive('/profile') ?: $isActive('/pds') ?>" href="<?= BASE_URL ?>/profile"><span class="app-nav-icon">&#128100;</span><span><strong>My Profile</strong><small>Information &amp; PDS</small></span></a>
            <a class="app-nav-card <?= $isActive('/settings') ?>" href="<?= BASE_URL ?>/settings"><span class="app-nav-icon">&#9881;</span><span><strong>Settings</strong><small>Theme &amp; colors</small></span></a>
            <a class="app-nav-card <?= $isActive('/updates') ?>" href="<?= BASE_URL ?>/updates"><span class="app-nav-icon">&#10024;</span><span><strong>What's New</strong><small>Version <?= htmlspecialchars(SystemRelease::currentVersion()) ?></small></span></a>
            <a class="app-nav-card <?= $isActive('/tasks') ?>" href="<?= BASE_URL ?>/tasks"><span class="app-nav-icon">&#10003;</span><span><strong>Tasks</strong><small>Work board</small></span></a>
            <?php if (Auth::can('accomplishment.create')): ?>
                <a class="app-nav-card <?= $isActive('/accomplishments') ?>" href="<?= BASE_URL ?>/accomplishments"><span class="app-nav-icon">&#10022;</span><span><strong>Accomplishments</strong><small>Evidence &amp; reviews</small></span></a>
            <?php endif; ?>
            <?php if (Auth::can('report.view')): ?>
                <a class="app-nav-card <?= $isActive('/reports') ?>" href="<?= BASE_URL ?>/reports/pds-completion"><span class="app-nav-icon">&#9635;</span><span><strong>Reports</strong><small>Team progress</small></span></a>
            <?php endif; ?>
        </nav>
        <?php if (Auth::can('report.view') || Auth::can('user.manage')): ?>
            <p class="drawer-kicker">Administration</p>
            <nav class="app-card-grid app-card-grid-compact">
                <?php if (Auth::can('report.view')): ?><a class="app-nav-card <?= $isActive('/admin/dashboard') ?>" href="<?= BASE_URL ?>/admin/dashboard"><span class="app-nav-icon">&#9632;</span><span><strong>Analytics</strong><small>Statistics &amp; trends</small></span></a><?php endif; ?>
                <?php if (Auth::can('user.manage')): ?>
                    <a class="app-nav-card <?= $isActive('/admin/activity') ?>" href="<?= BASE_URL ?>/admin/activity"><span class="app-nav-icon">&#128220;</span><span><strong>Activity Logs</strong><small>System audit trail</small></span></a>
                    <a class="app-nav-card <?= $isActive('/admin/users') ?>" href="<?= BASE_URL ?>/admin/users"><span class="app-nav-icon">&#128273;</span><span><strong>Accounts</strong><small>Access control</small></span></a>
                    <a class="app-nav-card <?= $isActive('/admin/departments') ?>" href="<?= BASE_URL ?>/admin/departments"><span class="app-nav-icon">&#9671;</span><span><strong>Departments</strong><small>Organization</small></span></a>
                    <a class="app-nav-card <?= $isActive('/admin/positions') ?>" href="<?= BASE_URL ?>/admin/positions"><span class="app-nav-icon">&#9734;</span><span><strong>Positions</strong><small>Job catalogue</small></span></a>
                    <?php if (Auth::isDeveloper()): ?><a class="app-nav-card <?= $isActive('/admin/releases') ?: $isActive('/admin/updater') ?>" href="<?= BASE_URL ?>/admin/releases"><span class="app-nav-icon">&#128640;</span><span><strong>System Updates</strong><small id="system-update-nav-status">Checking GitHub…</small></span></a><?php endif; ?>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
        <?php if (Auth::isDeveloper()): ?>
        <script>
        window.addEventListener('DOMContentLoaded', async () => {
            const label = document.getElementById('system-update-nav-status');
            if (!label) return;
            try {
                const response = await fetch(`${window.BASE_URL}/admin/updater/status`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'Check failed');
                label.textContent = result.status.update_available
                    ? (!result.status.version_ready ? 'New update: version required' : (!result.status.deployment_writable ? 'Update permission required' : `Version ${result.status.new_version} ready`))
                    : 'System is up to date';
                if (result.status.update_available) label.classList.add('system-update-available');
            } catch (error) { label.textContent = 'Unable to check GitHub'; }
        });
        </script>
        <?php endif; ?>
        <div class="drawer-user">
            <span class="avatar"><?= htmlspecialchars($initials) ?></span>
            <span><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars(Auth::roleName() ?? '') ?></small></span>
            <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="drawer-logout" type="submit" aria-label="Log out"><span aria-hidden="true">&#8594;</span><strong>Log out</strong></button></form>
        </div>
    </aside>

    <div class="main-area">
        <header class="glass-header glass-strong">
            <div class="header-left">
                <?php if (!$isDashboard): ?>
                    <button class="menu-toggle app-launcher" id="menu-toggle" aria-label="Open applications" aria-expanded="<?= $openAppDrawer ? 'true' : 'false' ?>"><span class="app-launcher-icon" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span>Apps</span></button>
                    <button class="app-back-button" id="app-back-button" type="button" aria-label="Go back"><span aria-hidden="true">&larr;</span><b>Back</b></button>
                <?php endif; ?>
                <div class="header-context"><span class="header-workspace"><span class="header-brand-dot"></span> HRMS Workspace</span><div class="header-title"><?= htmlspecialchars($pageTitle) ?></div></div>
            </div>

            <div class="header-search-wrap" id="header-search-wrap">
                <div class="glass-search header-global-search">
                    <span class="search-leading-icon" aria-hidden="true"></span>
                    <input type="search" id="global-search" placeholder="Search tasks, accomplishments<?= (Auth::roleName() === ROLE_ADMIN || Auth::isDeveloper()) ? ', employees' : '' ?>..." aria-label="Global search" autocomplete="off" aria-expanded="false" aria-controls="global-search-results">
                    <span class="search-hint" aria-hidden="true">⌘ K</span>
                    <button class="search-clear" id="search-clear" type="button" aria-label="Clear search" hidden>&times;</button>
                    <span class="search-spinner" id="search-spinner" hidden></span>
                </div>
                <div class="global-search-results glass-strong" id="global-search-results" role="listbox" aria-label="Search results" aria-live="polite" hidden></div>
            </div>

            <div class="header-actions">
                <a class="icon-button header-settings-link <?= $isActive('/settings') ?>" href="<?= BASE_URL ?>/settings" aria-label="Appearance settings" title="Appearance settings"><span aria-hidden="true">&#9881;</span></a>
                <div class="notification-control">
                    <button class="icon-button <?= $unreadNotificationCount ? 'pulse' : '' ?>" id="notif-toggle" aria-label="Notifications<?= $unreadNotificationCount ? ' (' . $unreadNotificationCount . ' unread)' : '' ?>" data-unread="<?= $unreadNotificationCount ?>">
                        <span aria-hidden="true">&#128276;</span>
                        <?php if ($unreadNotificationCount): ?><span class="dot-badge"><b><?= $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount ?></b></span><?php endif; ?>
                    </button>
                    <div class="glass-dropdown glass-strong" id="notif-dropdown" role="region" aria-label="Unread notifications">
                        <div class="glass-dropdown-header"><span>Notifications</span><small id="notification-unread-label"><?= $unreadNotificationCount ?> unread</small></div>
                        <div id="notif-list">
                            <?php foreach ($headerNotifications as $n): ?>
                                <a class="dropdown-item notification-link" data-notification-id="<?= (int) $n['id'] ?>" href="<?= htmlspecialchars($n['link'] ?: '#') ?>">
                                    <span class="notification-item-icon" aria-hidden="true">&#128276;</span>
                                    <span class="notification-item-copy"><strong><?= htmlspecialchars($n['message']) ?></strong><small><?= date('M j, Y', strtotime($n['created_at'])) ?> &middot; <?= date('g:i A', strtotime($n['created_at'])) ?></small></span>
                                    <span class="status-dot" aria-hidden="true"></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$headerNotifications): ?>
                                <div class="notification-empty">You&rsquo;re all caught up.<small>No unread notifications.</small></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a class="user-chip" href="<?= BASE_URL ?>/profile" title="Open my profile">
                    <span class="avatar-sm"><?= htmlspecialchars($initials) ?></span>
                    <span class="user-chip-copy"><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars(Auth::roleName() ?? '') ?></small></span>
                </a>
                <form class="header-logout-form" method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button type="submit" class="logout-control" aria-label="Log out" title="Log out"><span aria-hidden="true">&#10230;</span><b>Logout</b></button></form>
            </div>
        </header>
        <main class="content">
