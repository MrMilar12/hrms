<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <i class="fas fa-building me-2" style="color:#60a5fa;"></i>HRMS
  </div>
  <ul class="sidebar-nav nav flex-column mt-2">
    <?php if (isAdmin()): ?>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/admin/dashboard.php"
           class="<?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false) ? 'active' : '' ?>">
          <i class="fas fa-chart-pie fa-fw"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/modules/employees.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>">
          <i class="fas fa-users fa-fw"></i> Employees
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/modules/family.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'family.php' ? 'active' : '' ?>">
          <i class="fas fa-people-roof fa-fw"></i> Family Records
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/modules/education.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'education.php' ? 'active' : '' ?>">
          <i class="fas fa-graduation-cap fa-fw"></i> Education Records
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/modules/work.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'work.php' ? 'active' : '' ?>">
          <i class="fas fa-briefcase fa-fw"></i> Work Experience
        </a>
      </li>
    <?php else: ?>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/employee/dashboard.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
          <i class="fas fa-home fa-fw"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/pds/form.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'form.php' ? 'active' : '' ?>">
          <i class="fas fa-file-alt fa-fw"></i> My PDS
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/pds/preview.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'preview.php' ? 'active' : '' ?>">
          <i class="fas fa-eye fa-fw"></i> Preview PDS
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/pds/print.php" target="_blank">
          <i class="fas fa-print fa-fw"></i> Print PDS
        </a>
      </li>
    <?php endif; ?>
  </ul>

  <div style="position:absolute;bottom:20px;left:0;right:0;padding:0 16px;">
    <div class="dynamic-row" style="padding:12px;border-radius:10px;">
      <div style="font-size:.8rem;color:var(--text-muted);">Logged in as</div>
      <div style="font-weight:600;font-size:.9rem;margin:4px 0;"><?= h($_SESSION['user_name'] ?? 'User') ?></div>
      <span class="badge-glass" style="font-size:.7rem;text-transform:uppercase;">
        <?= h($_SESSION['user_role'] ?? '') ?>
      </span>
    </div>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-glass-danger btn-glass mt-2 w-100 text-center text-decoration-none d-block"
       style="padding:8px;font-size:.85rem;">
      <i class="fas fa-sign-out-alt me-1"></i> Logout
    </a>
  </div>
</nav>

<!-- Mobile toggle -->
<button class="d-md-none no-print"
        onclick="document.getElementById('sidebar').classList.toggle('open')"
        style="position:fixed;top:14px;left:14px;z-index:200;background:rgba(59,130,246,.4);border:1px solid rgba(59,130,246,.5);color:white;border-radius:8px;padding:8px 12px;">
  <i class="fas fa-bars"></i>
</button>

<div class="main-content">
