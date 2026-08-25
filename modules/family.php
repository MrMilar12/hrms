<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAdmin();

$pageTitle = 'Family Background Records';
$search    = trim($_GET['q'] ?? '');

if ($search) {
    $like = '%' . $search . '%';
    $s = $conn->prepare("
        SELECT fb.*, e.first_name, e.last_name, e.employee_no,
               (SELECT COUNT(*) FROM children c WHERE c.employee_id=e.id) AS child_count
        FROM family_background fb
        JOIN employees e ON fb.employee_id = e.id
        WHERE e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_no LIKE ?
           OR fb.spouse_surname LIKE ? OR fb.father_surname LIKE ?
        ORDER BY e.last_name");
    $s->bind_param('sssss', $like, $like, $like, $like, $like);
} else {
    $s = $conn->prepare("
        SELECT fb.*, e.first_name, e.last_name, e.employee_no,
               (SELECT COUNT(*) FROM children c WHERE c.employee_id=e.id) AS child_count
        FROM family_background fb
        JOIN employees e ON fb.employee_id = e.id
        ORDER BY e.last_name");
}
$s->execute();
$records = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h4 class="mb-0 fw-bold"><i class="fas fa-people-roof me-2" style="color:#fbbf24;"></i>Family Background Records</h4>
</div>

<div class="glass-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 fw-bold">All Family Records (<?= count($records) ?>)</h6>
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-glass"
             value="<?= h($search) ?>" placeholder="Search by name..." style="width:220px;">
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
          <th>Employee</th>
          <th>Spouse</th>
          <th>Father</th>
          <th>Mother</th>
          <th>Children</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$records): ?>
          <tr><td colspan="6" class="text-center" style="color:var(--text-muted);padding:30px;">No family records found.</td></tr>
        <?php else: foreach ($records as $r): ?>
          <tr>
            <td>
              <strong><?= h($r['last_name'] . ', ' . $r['first_name']) ?></strong><br>
              <span class="badge-glass" style="font-size:.68rem;"><?= h($r['employee_no']) ?></span>
            </td>
            <td style="font-size:.82rem;">
              <?php if ($r['spouse_surname']): ?>
                <?= h($r['spouse_surname'] . ', ' . $r['spouse_firstname']) ?><br>
                <span style="color:var(--text-muted);font-size:.75rem;"><?= h($r['spouse_occupation']) ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted);">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;"><?= h(trim($r['father_surname'] . ', ' . $r['father_firstname'])) ?: '—' ?></td>
            <td style="font-size:.82rem;"><?= h(trim($r['mother_surname'] . ', ' . $r['mother_firstname'])) ?: '—' ?></td>
            <td>
              <span class="badge-glass"><?= $r['child_count'] ?></span>
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
