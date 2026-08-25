<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAuth();

$pageTitle  = 'My Dashboard';
$userId     = $_SESSION['user_id'];
$employeeId = $_SESSION['employee_id'];

// Load employee profile
$emp = [];
if ($employeeId) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $emp = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

// PDS Status
$pdsStatus = null;
if ($employeeId) {
    $s = $conn->prepare("SELECT * FROM pds_status WHERE employee_id = ?");
    $s->bind_param('i', $employeeId);
    $s->execute();
    $pdsStatus = $s->get_result()->fetch_assoc();
    $s->close();
}

// Count PDS sections completed
$sections = [
    'Personal Info'   => !empty($emp['first_name']),
    'Family Bg.'      => false,
    'Education'       => false,
    'Eligibility'     => false,
    'Work Experience' => false,
    'Other Info'      => false,
];
if ($employeeId) {
    $fb = $conn->query("SELECT id FROM family_background WHERE employee_id = " . (int)$employeeId)->num_rows > 0;
    $ed = $conn->query("SELECT id FROM education WHERE employee_id = " . (int)$employeeId)->num_rows > 0;
    $el = $conn->query("SELECT id FROM eligibility WHERE employee_id = " . (int)$employeeId)->num_rows > 0;
    $we = $conn->query("SELECT id FROM work_experience WHERE employee_id = " . (int)$employeeId)->num_rows > 0;
    $oi = $conn->query("SELECT id FROM other_info WHERE employee_id = " . (int)$employeeId)->num_rows > 0;
    $sections['Family Bg.']      = $fb;
    $sections['Education']       = $ed;
    $sections['Eligibility']     = $el;
    $sections['Work Experience'] = $we;
    $sections['Other Info']      = $oi;
}
$doneCount = count(array_filter($sections));
$totalSections = count($sections);

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-0 fw-bold">Welcome, <?= h($emp['first_name'] ?? $_SESSION['user_name']) ?>!</h4>
    <small style="color:var(--text-muted);"><?= date('l, F j, Y') ?></small>
  </div>
  <a href="<?= BASE_URL ?>/pds/form.php" class="btn-glass">
    <i class="fas fa-edit me-1"></i>Fill / Edit PDS
  </a>
</div>

<!-- PDS Progress -->
<div class="glass-card mb-4">
  <h6 class="fw-bold mb-3"><i class="fas fa-tasks me-2" style="color:#60a5fa;"></i>PDS Completion</h6>
  <div class="d-flex align-items-center gap-3 mb-3">
    <div style="flex:1;">
      <div style="background:var(--glass-border);border-radius:10px;height:10px;overflow:hidden;">
        <div style="background:linear-gradient(90deg,#3b82f6,#818cf8);height:100%;border-radius:10px;
             width:<?= round(($doneCount / $totalSections) * 100) ?>%;transition:width .6s ease;"></div>
      </div>
    </div>
    <span style="font-size:.85rem;color:var(--text-muted);"><?= $doneCount ?>/<?= $totalSections ?> sections</span>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($sections as $sec => $done): ?>
      <span style="padding:5px 12px;border-radius:20px;font-size:.78rem;
            background:<?= $done ? 'rgba(52,211,153,.15)' : 'rgba(255,255,255,.05)' ?>;
            border:1px solid <?= $done ? 'rgba(52,211,153,.4)' : 'var(--glass-border)' ?>;
            color:<?= $done ? '#34d399' : 'var(--text-muted)' ?>;">
        <i class="fas <?= $done ? 'fa-check-circle' : 'fa-circle' ?> me-1"></i><?= $sec ?>
      </span>
    <?php endforeach; ?>
  </div>
  <?php if ($pdsStatus && $pdsStatus['is_submitted']): ?>
    <div class="mt-3" style="color:#34d399;font-size:.9rem;">
      <i class="fas fa-check-double me-1"></i>PDS Submitted on
      <?= date('F j, Y g:i A', strtotime($pdsStatus['submitted_at'])) ?>
    </div>
  <?php endif; ?>
</div>

<!-- Profile Summary -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="glass-card h-100">
      <h6 class="fw-bold mb-3"><i class="fas fa-id-card me-2" style="color:#a78bfa;"></i>My Profile</h6>
      <?php if ($emp): ?>
        <table class="w-100" style="font-size:.875rem;border-spacing:0 6px;border-collapse:separate;">
          <tr><td style="color:var(--text-muted);width:40%;">Employee No.</td>
              <td><?= h($emp['employee_no'] ?? '—') ?></td></tr>
          <tr><td style="color:var(--text-muted);">Full Name</td>
              <td><?= h(trim(($emp['last_name'] ?? '') . ', ' . ($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? ''))) ?></td></tr>
          <tr><td style="color:var(--text-muted);">Birthdate</td>
              <td><?= $emp['birthdate'] ? date('F j, Y', strtotime($emp['birthdate'])) : '—' ?></td></tr>
          <tr><td style="color:var(--text-muted);">Sex</td>
              <td><?= h($emp['sex'] ?? '—') ?></td></tr>
          <tr><td style="color:var(--text-muted);">Civil Status</td>
              <td><?= h($emp['civil_status'] ?? '—') ?></td></tr>
          <tr><td style="color:var(--text-muted);">Email</td>
              <td><?= h($emp['email_address'] ?? $_SESSION['user_email'] ?? '—') ?></td></tr>
        </table>
      <?php else: ?>
        <p style="color:var(--text-muted);">No profile data yet. <a href="<?= BASE_URL ?>/pds/form.php" style="color:#60a5fa;">Fill your PDS</a>.</p>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="glass-card h-100">
      <h6 class="fw-bold mb-3"><i class="fas fa-bolt me-2" style="color:#fbbf24;"></i>Quick Actions</h6>
      <div class="d-flex flex-column gap-2">
        <a href="<?= BASE_URL ?>/pds/form.php" class="btn-glass text-decoration-none text-center">
          <i class="fas fa-edit me-2"></i>Fill / Update PDS
        </a>
        <a href="<?= BASE_URL ?>/pds/preview.php" class="btn-glass text-decoration-none text-center">
          <i class="fas fa-eye me-2"></i>Preview PDS
        </a>
        <a href="<?= BASE_URL ?>/pds/print.php" target="_blank" class="btn-glass text-decoration-none text-center">
          <i class="fas fa-print me-2"></i>Print PDS
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
