<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'HRMS';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isActive = fn(string $needle) => str_contains($currentPath, $needle) ? 'active' : '';
$isDashboard = rtrim($currentPath, '/') === rtrim(BASE_URL, '/') || rtrim($currentPath, '/') === rtrim(BASE_URL . '/dashboard', '/');
$displayName = Auth::check() ? Auth::displayName() : '';
$initials = strtoupper(substr($displayName ?: '?', 0, 1));
$openAppDrawer = !empty($_SESSION['show_app_drawer']);
unset($_SESSION['show_app_drawer']);

$headerNotifications = [];
if (Auth::check()) {
    $stmt = Database::getInstance()->prepare(
        'SELECT message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6'
    );
    $stmt->execute([Auth::userId()]);
    $headerNotifications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken()) ?>">
<title><?= htmlspecialchars($pageTitle) ?> &mdash; HRMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css">
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
            <a class="app-nav-card <?= $isActive('/dashboard') ?: $isActive('/HRIS/public/') ?>" href="<?= BASE_URL ?>/dashboard"><span class="app-nav-icon">&#9638;</span><span><strong>Dashboard</strong><small>Overview</small></span></a>
            <a class="app-nav-card <?= $isActive('/employees') ?>" href="<?= BASE_URL ?>/employees"><span class="app-nav-icon">&#128100;</span><span><strong>Employees</strong><small>People directory</small></span></a>
            <a class="app-nav-card <?= $isActive('/pds') ?>" href="<?= BASE_URL ?>/pds"><span class="app-nav-icon">&#128196;</span><span><strong>PDS</strong><small>Personal data sheet</small></span></a>
            <a class="app-nav-card <?= $isActive('/tasks') ?>" href="<?= BASE_URL ?>/tasks"><span class="app-nav-icon">&#10003;</span><span><strong>Tasks</strong><small>Work board</small></span></a>
            <?php if (Auth::can('accomplishment.create')): ?>
                <a class="app-nav-card <?= $isActive('/accomplishments') ?>" href="<?= BASE_URL ?>/accomplishments"><span class="app-nav-icon">&#10022;</span><span><strong>Accomplishments</strong><small>Evidence &amp; reviews</small></span></a>
            <?php endif; ?>
            <?php if (Auth::can('report.view')): ?>
                <a class="app-nav-card <?= $isActive('/reports') ?>" href="<?= BASE_URL ?>/reports/pds-completion"><span class="app-nav-icon">&#9635;</span><span><strong>Reports</strong><small>Team progress</small></span></a>
            <?php endif; ?>
        </nav>
        <?php if (Auth::can('user.manage')): ?>
            <p class="drawer-kicker">Administration</p>
            <nav class="app-card-grid app-card-grid-compact">
                <a class="app-nav-card <?= $isActive('/admin/users') ?>" href="<?= BASE_URL ?>/admin/users"><span class="app-nav-icon">&#128273;</span><span><strong>Accounts</strong><small>Access control</small></span></a>
                <a class="app-nav-card <?= $isActive('/admin/departments') ?>" href="<?= BASE_URL ?>/admin/departments"><span class="app-nav-icon">&#9671;</span><span><strong>Departments</strong><small>Organization</small></span></a>
                <a class="app-nav-card <?= $isActive('/admin/positions') ?>" href="<?= BASE_URL ?>/admin/positions"><span class="app-nav-icon">&#9734;</span><span><strong>Positions</strong><small>Job catalogue</small></span></a>
            </nav>
        <?php endif; ?>
        <div class="drawer-user">
            <span class="avatar"><?= htmlspecialchars($initials) ?></span>
            <span><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars(Auth::roleName() ?? '') ?></small></span>
            <a href="<?= BASE_URL ?>/logout" aria-label="Log out">&#8594;</a>
        </div>
    </aside>

    <div class="main-area">
        <header class="glass-header glass-strong">
            <div class="header-left">
                <button class="menu-toggle app-launcher" id="menu-toggle" aria-label="Open applications" aria-expanded="<?= $openAppDrawer ? 'true' : 'false' ?>"><span class="app-launcher-icon">&#9638;</span><span>Apps</span></button>
                <div class="header-context"><span class="header-workspace"><span class="header-brand-dot"></span> HRMS Workspace</span><div class="header-title"><?= htmlspecialchars($pageTitle) ?></div></div>
                <?php if (!$isDashboard): ?>
                    <button class="app-back-button" id="app-back-button" type="button" aria-label="Go back"><span>&larr;</span> Back</button>
                <?php endif; ?>
            </div>

            <div class="glass-search">
                <span>&#128269;</span>
                <input type="text" placeholder="Search employee, task, PDS..." aria-label="Search">
            </div>

            <div class="header-actions">
                <div style="position:relative;">
                    <button class="icon-button pulse" id="notif-toggle" aria-label="Notifications">
                        &#128276;
                        <span class="dot-badge"></span>
                    </button>
                    <div class="glass-dropdown glass-strong" id="notif-dropdown">
                        <div class="glass-dropdown-header">Notifications</div>
                        <div id="notif-list">
                            <?php foreach ($headerNotifications as $n): ?>
                                <div class="dropdown-item <?= $n['is_read'] ? 'read' : '' ?>">
                                    <span class="status-dot"></span>
                                    <span><?= htmlspecialchars($n['message']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$headerNotifications): ?>
                                <div class="dropdown-item"><span class="status-dot" style="opacity:.3;"></span> No notifications.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="user-chip" title="<?= htmlspecialchars(Auth::roleName() ?? '') ?>">
                    <span class="avatar-sm"><?= htmlspecialchars($initials) ?></span>
                    <span class="user-chip-copy"><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars(Auth::roleName() ?? '') ?></small></span>
                </div>
                <a href="<?= BASE_URL ?>/logout" class="logout-control" aria-label="Log out" title="Log out">&#10230;<span>Logout</span></a>
            </div>
        </header>
        <main class="content">
