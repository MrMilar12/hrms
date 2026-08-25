<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';

// === Stats ===
$totalEmployees = $conn->query("SELECT COUNT(*) AS c FROM employees")->fetch_assoc()['c'];
$totalUsers     = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='employee'")->fetch_assoc()['c'];
$totalPdsOk     = $conn->query("SELECT COUNT(*) AS c FROM pds_status WHERE is_submitted=1")->fetch_assoc()['c'];
$totalPdsPend   = $totalEmployees - $totalPdsOk;

// === Recent Employees ===
$recentRes = $conn->query("
    SELECT e.id, e.employee_no, e.first_name, e.last_name, e.created_at,
           u.email, u.role,
           ps.is_submitted
    FROM employees e
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN pds_status ps ON ps.employee_id = e.id
    ORDER BY e.created_at DESC LIMIT 10
");

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-0 fw-bold">Dashboard</h4>
    <small style="color:var(--text-muted);"><?= date('l, F j, Y') ?></small>
  </div>
  <a href="<?= BASE_URL ?>/modules/employees.php?action=add" class="btn-glass">
    <i class="fas fa-plus me-1"></i>Add Employee
  </a>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <i class="fas fa-users fa-2x mb-2" style="color:#60a5fa;"></i>
      <div class="stat-number"><?= $totalEmployees ?></div>
      <div class="stat-label">Total Employees</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <i class="fas fa-user-check fa-2x mb-2" style="color:#34d399;"></i>
      <div class="stat-number"><?= $totalUsers ?></div>
      <div class="stat-label">Registered Users</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <i class="fas fa-file-alt fa-2x mb-2" style="color:#a78bfa;"></i>
      <div class="stat-number"><?= $totalPdsOk ?></div>
      <div class="stat-label">PDS Submitted</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <i class="fas fa-hourglass-half fa-2x mb-2" style="color:#fbbf24;"></i>
      <div class="stat-number"><?= $totalPdsPend ?></div>
      <div class="stat-label">Pending PDS</div>
    </div>
  </div>
</div>

<!-- Recent Employees Table -->
<div class="glass-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 fw-bold">Recent Employees</h6>
    <a href="<?= BASE_URL ?>/modules/employees.php" style="color:#60a5fa;font-size:.875rem;">View All &rarr;</a>
  </div>
  <div class="table-responsive">
    <table class="table table-glass table-hover mb-0">
      <thead>
        <tr>
          <th>Emp. No</th>
          <th>Name</th>
          <th>Email</th>
          <th>PDS Status</th>
          <th>Registered</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recentRes->num_rows === 0): ?>
          <tr><td colspan="6" class="text-center" style="color:var(--text-muted);padding:30px;">
            No employees found. <a href="<?= BASE_URL ?>/modules/employees.php?action=add" style="color:#60a5fa;">Add one</a>.
          </td></tr>
        <?php else:
          while ($row = $recentRes->fetch_assoc()): ?>
          <tr>
            <td><span class="badge-glass"><?= h($row['employee_no'] ?? 'N/A') ?></span></td>
            <td>
              <strong><?= h(trim($row['last_name'] . ', ' . $row['first_name'])) ?></strong>
            </td>
            <td style="color:var(--text-muted);font-size:.85rem;"><?= h($row['email'] ?? '—') ?></td>
            <td>
              <?php if ($row['is_submitted']): ?>
                <span style="color:#34d399;font-size:.8rem;"><i class="fas fa-check-circle me-1"></i>Submitted</span>
              <?php else: ?>
                <span style="color:#fbbf24;font-size:.8rem;"><i class="fas fa-clock me-1"></i>Pending</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:var(--text-muted);"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/pds/preview.php?emp_id=<?= $row['id'] ?>"
                 class="btn-glass btn-glass me-1" style="padding:4px 10px;font-size:.78rem;">
                <i class="fas fa-eye"></i>
              </a>
              <a href="<?= BASE_URL ?>/modules/employees.php?action=edit&id=<?= $row['id'] ?>"
                 class="btn-glass" style="padding:4px 10px;font-size:.78rem;">
                <i class="fas fa-edit"></i>
              </a>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Links -->
<div class="row g-3 mt-2">
  <div class="col-md-4">
    <a href="<?= BASE_URL ?>/modules/employees.php" class="glass-card text-decoration-none d-block text-center">
      <i class="fas fa-users fa-2x mb-2" style="color:#60a5fa;"></i>
      <div class="fw-bold">Manage Employees</div>
      <small style="color:var(--text-muted);">View, add, edit, delete</small>
    </a>
  </div>
  <div class="col-md-4">
    <a href="<?= BASE_URL ?>/modules/education.php" class="glass-card text-decoration-none d-block text-center">
      <i class="fas fa-graduation-cap fa-2x mb-2" style="color:#a78bfa;"></i>
      <div class="fw-bold">Education Records</div>
      <small style="color:var(--text-muted);">Browse all education data</small>
    </a>
  </div>
  <div class="col-md-4">
    <a href="<?= BASE_URL ?>/modules/work.php" class="glass-card text-decoration-none d-block text-center">
      <i class="fas fa-briefcase fa-2x mb-2" style="color:#34d399;"></i>
      <div class="fw-bold">Work Experience</div>
      <small style="color:var(--text-muted);">Browse work history</small>
    </a>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
