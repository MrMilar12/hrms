<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAdmin();

$pageTitle = 'Education Records';
$search    = trim($_GET['q'] ?? '');

if ($search) {
    $like = '%' . $search . '%';
    $s = $conn->prepare("
        SELECT ed.*, e.first_name, e.last_name, e.employee_no
        FROM education ed
        JOIN employees e ON ed.employee_id = e.id
        WHERE e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_no LIKE ? OR ed.school LIKE ?
        ORDER BY e.last_name, FIELD(ed.level,'Elementary','Secondary','Vocational','College','Graduate Studies')");
    $s->bind_param('ssss', $like, $like, $like, $like);
} else {
    $s = $conn->prepare("
        SELECT ed.*, e.first_name, e.last_name, e.employee_no
        FROM education ed
        JOIN employees e ON ed.employee_id = e.id
        ORDER BY e.last_name, FIELD(ed.level,'Elementary','Secondary','Vocational','College','Graduate Studies')");
}
$s->execute();
$records = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h4 class="mb-0 fw-bold"><i class="fas fa-graduation-cap me-2" style="color:#a78bfa;"></i>Education Records</h4>
</div>

<div class="glass-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 fw-bold">All Education Records (<?= count($records) ?>)</h6>
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-glass"
             value="<?= h($search) ?>" placeholder="Search name, school..." style="width:220px;">
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
          <th>Employee</th><th>Level</th><th>School</th><th>Degree/Course</th>
          <th>Period</th><th>Yr. Graduated</th><th>Honors</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$records): ?>
          <tr><td colspan="8" class="text-center" style="color:var(--text-muted);padding:30px;">No education records found.</td></tr>
        <?php else: foreach ($records as $r): ?>
          <tr>
            <td>
              <strong><?= h($r['last_name'] . ', ' . $r['first_name']) ?></strong><br>
              <span class="badge-glass" style="font-size:.68rem;"><?= h($r['employee_no']) ?></span>
            </td>
            <td><span style="font-size:.8rem;"><?= h($r['level']) ?></span></td>
            <td><?= h($r['school']) ?></td>
            <td style="font-size:.82rem;"><?= h($r['degree']) ?></td>
            <td style="font-size:.8rem;color:var(--text-muted);"><?= h($r['from_year']) ?><?= $r['from_year'] ? '–' : '' ?><?= h($r['to_year']) ?></td>
            <td><?= h($r['year_graduated']) ?></td>
            <td style="font-size:.8rem;"><?= h($r['honors']) ?></td>
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
