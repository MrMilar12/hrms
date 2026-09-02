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
$headerPhotoUrl = null;
$openAppDrawer = false;

$headerNotifications = [];
$unreadNotificationCount = 0;
if (Auth::check()) {
    if (Auth::employeeId()) {
        $photoStmt = Database::getInstance()->prepare('SELECT id, file_path FROM employee_photos WHERE employee_id = ? ORDER BY uploaded_at DESC LIMIT 1');
        $photoStmt->execute([Auth::employeeId()]);
        $headerPhoto = $photoStmt->fetch();
        if ($headerPhoto && is_file((string) $headerPhoto['file_path'])) {
            $headerPhotoUrl = BASE_URL . '/photo/' . UrlId::encode((int) $headerPhoto['id']);
        }
    }
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#2563eb">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="HRMS">
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken()) ?>">
<title><?= htmlspecialchars($pageTitle) ?> &mdash; HRMS</title>
<script>
(function(){try{var s=JSON.parse(localStorage.getItem('hrms-appearance')||'{}');var m=s.mode||'system';var d=m==='dark'||(m==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.dataset.theme=d?'dark':'light';if(/^#[0-9a-f]{6}$/i.test(s.primary||''))document.documentElement.style.setProperty('--accent-blue',s.primary);if(/^#[0-9a-f]{6}$/i.test(s.secondary||''))document.documentElement.style.setProperty('--accent-violet',s.secondary);document.documentElement.style.setProperty('--accent-primary','var(--accent-blue)');}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
<script src="<?= BASE_URL ?>/assets/js/sweetalert2.all.min.js?v=11"></script>
<script>window.BASE_URL = '<?= BASE_URL ?>'; window.HRMS_ROLE = <?= json_encode((string) (Auth::roleName() ?? 'User')) ?>; window.OPEN_APP_DRAWER = <?= $openAppDrawer ? 'true' : 'false' ?>;</script>
</head>
<body>
<div id="flash-message" class="alert" style="display:none; position:fixed; top:1rem; right:1rem; z-index:999; max-width:320px;"></div>

<div class="app-shell">
    <div class="app-drawer-backdrop <?= $openAppDrawer ? 'open' : '' ?>" id="app-drawer-backdrop" aria-hidden="<?= $openAppDrawer ? 'false' : 'true' ?>"></div>
    <aside class="app-drawer glass-strong <?= $openAppDrawer ? 'open' : '' ?>" id="app-drawer" role="dialog" aria-modal="true" aria-labelledby="app-drawer-title" aria-hidden="<?= $openAppDrawer ? 'false' : 'true' ?>">
        <div class="drawer-heading">
            <div class="drawer-brand"><span class="drawer-brand-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span><small>HRMS workspace</small><strong id="app-drawer-title">All applications</strong></span></div>
            <button class="drawer-close" id="drawer-close" type="button" aria-label="Close app drawer">&times;</button>
        </div>
        <p class="drawer-intro">Choose where you want to continue.</p>
        <p class="drawer-kicker">My workspace</p>
        <nav class="app-card-grid">
            <a class="app-nav-card <?= $isActive('/dashboard') ?: $isActive(BASE_URL . '/') ?>" href="<?= BASE_URL ?>/dashboard"><span class="app-nav-icon">&#9638;</span><span><strong>Home</strong><small>Overview</small></span></a>
            <?php if (Auth::can('employee.view')): ?>
                <a class="app-nav-card <?= $isActive('/employees') ?>" href="<?= BASE_URL ?>/employees"><span class="app-nav-icon">&#128100;</span><span><strong>Employees</strong><small>People directory</small></span></a>
            <?php endif; ?>
            <?php if (Auth::can('employee.manage')): ?>
                <a class="app-nav-card <?= $isActive('/vacant-positions') ?>" href="<?= BASE_URL ?>/vacant-positions"><span class="app-nav-icon">&#128188;</span><span><strong>Vacant Positions</strong><small>Available plantilla items</small></span></a>
            <?php endif; ?>
            <a class="app-nav-card <?= $isActive('/profile') ?: $isActive('/pds') ?>" href="<?= BASE_URL ?>/profile"><span class="app-nav-icon">&#128100;</span><span><strong>My Profile</strong><small>Information &amp; PDS</small></span></a>
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
            <span class="avatar drawer-profile-avatar"><?php if ($headerPhotoUrl): ?><img src="<?= htmlspecialchars($headerPhotoUrl) ?>" alt="<?= htmlspecialchars($displayName) ?>"><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?></span>
            <span><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars(Auth::roleName() ?? '') ?></small></span>
            <form method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button class="drawer-logout" type="submit" aria-label="Log out"><span aria-hidden="true">&#8594;</span><strong>Log out</strong></button></form>
        </div>
    </aside>

    <div class="main-area">
        <header class="glass-header glass-strong<?= $isDashboard ? ' dashboard-header' : '' ?>">
            <div class="header-left">
                <?php if (!$isDashboard): ?>
                    <button class="menu-toggle app-launcher" id="menu-toggle" aria-label="Open applications" aria-expanded="<?= $openAppDrawer ? 'true' : 'false' ?>"><span class="app-launcher-icon" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span>Apps</span></button>
                <?php endif; ?>
                <?php if (!$isDashboard): ?>
                    <button class="app-back-button" id="app-back-button" type="button" aria-label="Go back"><span aria-hidden="true">&larr;</span><b>Back</b></button>
                <?php endif; ?>
                <div class="header-context"><span class="header-workspace"><span class="header-brand-dot"></span> HRMS Workspace</span><div class="header-title"><?= htmlspecialchars($pageTitle) ?></div></div>
            </div>

            <div class="header-search-wrap" id="header-search-wrap">
                <div class="glass-search header-global-search" role="search">
                    <span class="search-leading-icon" aria-hidden="true"></span>
                    <input type="search" id="global-search" placeholder="Ask HRMS anything..." aria-label="Search your authorized HRMS records" autocomplete="off" aria-expanded="false" aria-controls="global-search-results">
                    <span class="search-hint" aria-hidden="true"><b>⌘</b>K</span>
                    <button class="search-voice" id="search-voice" type="button" aria-label="Read search results aloud" title="Read results aloud" hidden><span aria-hidden="true">&#128266;</span></button>
                    <button class="search-clear" id="search-clear" type="button" aria-label="Clear search" hidden>&times;</button>
                    <span class="search-spinner" id="search-spinner" hidden></span>
                </div>
                <div class="global-search-results glass-strong" id="global-search-results" role="listbox" aria-label="Search results" aria-live="polite" hidden></div>
            </div>

            <div class="header-actions">
                <a class="icon-button header-ai-link" href="<?= BASE_URL ?>/ai" aria-label="Open HRMS AI Assistant" title="HRMS AI Assistant"><span aria-hidden="true">✦</span></a>
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
                    <span class="avatar-sm header-profile-avatar"><?php if ($headerPhotoUrl): ?><img src="<?= htmlspecialchars($headerPhotoUrl) ?>" alt="<?= htmlspecialchars($displayName) ?>"><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?></span>
                    <span class="user-chip-copy"><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars(Auth::roleName() ?? '') ?></small></span>
                </a>
                <form class="header-logout-form" method="post" action="<?= BASE_URL ?>/logout"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><button type="submit" class="logout-control" aria-label="Log out" title="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg><b>Logout</b></button></form>
            </div>
        </header>
        <div class="settings-modal" data-settings-modal hidden role="dialog" aria-modal="true" aria-label="Appearance settings"><div class="settings-modal-backdrop" data-settings-modal-close></div><section class="settings-modal-card"><button class="settings-modal-close" type="button" data-settings-modal-close aria-label="Close appearance settings">&times;</button><iframe title="Appearance settings" data-settings-frame></iframe></section></div>
        <main class="content">
