<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAdmin();

$pageTitle = 'Work Experience Records';
$search    = trim($_GET['q'] ?? '');

if ($search) {
    $like = '%' . $search . '%';
    $s = $conn->prepare("
        SELECT w.*, e.first_name, e.last_name, e.employee_no
        FROM work_experience w
        JOIN employees e ON w.employee_id = e.id
        WHERE e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_no LIKE ? OR w.position_title LIKE ? OR w.department LIKE ?
        ORDER BY e.last_name, w.start_date DESC");
    $s->bind_param('sssss', $like, $like, $like, $like, $like);
} else {
    $s = $conn->prepare("
        SELECT w.*, e.first_name, e.last_name, e.employee_no
        FROM work_experience w
        JOIN employees e ON w.employee_id = e.id
        ORDER BY e.last_name, w.start_date DESC");
}
$s->execute();
$records = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

function df2($v) { return $v && $v !== '0000-00-00' ? date('M j, Y', strtotime($v)) : '—'; }

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h4 class="mb-0 fw-bold"><i class="fas fa-briefcase me-2" style="color:#34d399;"></i>Work Experience Records</h4>
</div>

<div class="glass-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 fw-bold">All Work Records (<?= count($records) ?>)</h6>
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-glass"
             value="<?= h($search) ?>" placeholder="Search name, position..." style="width:220px;">
      <button type="submit" class="btn-glass" style="padding:8px 16px;">
        <i class="fas fa-search"></i>
      </button>
      <?php if ($search): ?>
        <a href="?" class="btn-glass text-decoration-none" style="padding:8px 14px;">
          <i class="fas fa-times"></i>
        </a>
      <?php endif; ?>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-glass table-hover mb-0">
      <thead>
        <tr>
          <th>Employee</th><th>Position</th><th>Department/Company</th>
          <th>From</th><th>To</th><th>Salary</th><th>SG</th><th>Gov't</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$records): ?>
          <tr><td colspan="9" class="text-center" style="color:var(--text-muted);padding:30px;">No work records found.</td></tr>
        <?php else: foreach ($records as $r): ?>
          <tr>
            <td>
              <strong><?= h($r['last_name'] . ', ' . $r['first_name']) ?></strong><br>
              <span class="badge-glass" style="font-size:.68rem;"><?= h($r['employee_no']) ?></span>
            </td>
            <td><strong><?= h($r['position_title']) ?></strong></td>
            <td style="font-size:.82rem;"><?= h($r['department']) ?></td>
            <td style="font-size:.8rem;"><?= df2($r['start_date']) ?></td>
            <td style="font-size:.8rem;">
              <?php if ($r['is_present']): ?>
                <span style="color:#34d399;">Present</span>
              <?php else: ?>
                <?= df2($r['end_date']) ?>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;"><?= h($r['monthly_salary']) ?></td>
            <td style="font-size:.8rem;"><?= h($r['salary_grade']) ?></td>
            <td>
              <span style="font-size:.8rem;color:<?= $r['is_government']==='Y'?'#34d399':'var(--text-muted)' ?>;">
                <?= $r['is_government'] === 'Y' ? 'Yes' : 'No' ?>
              </span>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/pds/preview.php?emp_id=<?= $r['employee_id'] ?>"
                 class="btn-glass" style="padding:4px 9px;font-size:.75rem;" title="View PDS">
                <i class="fas fa-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
