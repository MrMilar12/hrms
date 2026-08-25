<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAdmin();

$pageTitle = 'Employee Management';
$action    = $_GET['action'] ?? 'list';
$msg       = '';
$error     = '';

// ====== ADD EMPLOYEE ======
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $role    = 'employee';

    if (empty($name) || empty($email) || empty($pass)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $s = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $s->bind_param('s', $email); $s->execute(); $s->store_result();
        if ($s->num_rows > 0) {
            $error = 'Email already registered.';
            $s->close();
        } else {
            $s->close();
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $s = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $s->bind_param('ssss', $name, $email, $hashed, $role);
            if ($s->execute()) {
                $uid = $conn->insert_id;
                $empNo = 'EMP-' . str_pad($uid, 5, '0', STR_PAD_LEFT);
                $s2 = $conn->prepare("INSERT INTO employees (user_id, employee_no) VALUES (?, ?)");
                $s2->bind_param('is', $uid, $empNo);
                $s2->execute(); $s2->close();
                $s->close();
                header('Location: ' . BASE_URL . '/modules/employees.php?msg=added');
                exit;
            } else {
                $error = 'Failed to add employee.';
                $s->close();
            }
        }
    }
}

// ====== EDIT EMPLOYEE ======
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fn   = trim($_POST['first_name'] ?? '');
        $ln   = trim($_POST['last_name'] ?? '');
        $mn   = trim($_POST['middle_name'] ?? '');
        $bd   = trim($_POST['birthdate'] ?? '') ?: null;
        $sex  = trim($_POST['sex'] ?? '');
        $cs   = trim($_POST['civil_status'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin','hr','employee']) ? $_POST['role'] : 'employee';
        $active = isset($_POST['is_active']) ? 1 : 0;

        $s = $conn->prepare("UPDATE employees SET first_name=?,last_name=?,middle_name=?,birthdate=?,sex=?,civil_status=? WHERE id=?");
        $s->bind_param('ssssssi', $fn, $ln, $mn, $bd, $sex, $cs, $editId);
        $s->execute(); $s->close();

        // Update user role/active from employees.user_id
        $s = $conn->prepare("UPDATE users u INNER JOIN employees e ON u.id=e.user_id SET u.role=?,u.is_active=? WHERE e.id=?");
        $s->bind_param('sii', $role, $active, $editId);
        $s->execute(); $s->close();

        header('Location: ' . BASE_URL . '/modules/employees.php?msg=updated');
        exit;
    }

    $s = $conn->prepare("SELECT e.*, u.email, u.role, u.is_active FROM employees e LEFT JOIN users u ON e.user_id=u.id WHERE e.id=?");
    $s->bind_param('i', $editId); $s->execute();
    $editEmp = $s->get_result()->fetch_assoc();
    $s->close();
}

// ====== DELETE EMPLOYEE ======
if ($action === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    // Soft delete: deactivate user
    $s = $conn->prepare("UPDATE users u INNER JOIN employees e ON u.id=e.user_id SET u.is_active=0 WHERE e.id=?");
    $s->bind_param('i', $delId); $s->execute(); $s->close();
    header('Location: ' . BASE_URL . '/modules/employees.php?msg=deactivated');
    exit;
}

// ====== LIST ======
$search = trim($_GET['q'] ?? '');
if ($search) {
    $like = '%' . $search . '%';
    $listRes = $conn->prepare("
        SELECT e.*, u.email, u.role, u.is_active, ps.is_submitted
        FROM employees e
        LEFT JOIN users u ON e.user_id = u.id
        LEFT JOIN pds_status ps ON ps.employee_id = e.id
        WHERE e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_no LIKE ? OR u.email LIKE ?
        ORDER BY e.created_at DESC");
    $listRes->bind_param('ssss', $like, $like, $like, $like);
} else {
    $listRes = $conn->prepare("
        SELECT e.*, u.email, u.role, u.is_active, ps.is_submitted
        FROM employees e
        LEFT JOIN users u ON e.user_id = u.id
        LEFT JOIN pds_status ps ON ps.employee_id = e.id
        ORDER BY e.created_at DESC");
}
$listRes->execute();
$employees = $listRes->get_result()->fetch_all(MYSQLI_ASSOC);
$listRes->close();

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h4 class="mb-0 fw-bold"><i class="fas fa-users me-2" style="color:#60a5fa;"></i>Employee Management</h4>
  <a href="?action=add" class="btn-glass"><i class="fas fa-plus me-1"></i>Add Employee</a>
</div>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert-glass alert-glass-success mb-3">
    <?= ['added'=>'Employee added.','updated'=>'Employee updated.','deactivated'=>'Employee deactivated.'][$_GET['msg']] ?? 'Done.' ?>
  </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="glass-card mb-4">
  <h6 class="fw-bold mb-3"><?= $action === 'add' ? 'Add New Employee' : 'Edit Employee' ?></h6>
  <?php if ($error): ?><div class="alert-glass mb-3"><?= h($error) ?></div><?php endif; ?>

  <form method="POST" action="?action=<?= $action ?><?= $action==='edit' ? '&id='.(int)($_GET['id']??0) : '' ?>">
    <?php if ($action === 'add'): ?>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label-glass">Full Name *</label>
        <input type="text" name="name" class="form-control form-control-glass"
               value="<?= h($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label-glass">Email Address *</label>
        <input type="email" name="email" class="form-control form-control-glass"
               value="<?= h($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label-glass">Password (min. 6 chars) *</label>
        <input type="password" name="password" class="form-control form-control-glass" required>
      </div>
    </div>
    <?php else:
      $ee = $editEmp ?? [];
    ?>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label-glass">Last Name</label>
        <input type="text" name="last_name" class="form-control form-control-glass" value="<?= h($ee['last_name'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label-glass">First Name</label>
        <input type="text" name="first_name" class="form-control form-control-glass" value="<?= h($ee['first_name'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label-glass">Middle Name</label>
        <input type="text" name="middle_name" class="form-control form-control-glass" value="<?= h($ee['middle_name'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label-glass">Sex</label>
        <select name="sex" class="form-select form-control-glass form-select-glass">
          <option value="">—</option>
          <option value="Male" <?= ($ee['sex']??'')==='Male'?'selected':'' ?>>Male</option>
          <option value="Female" <?= ($ee['sex']??'')==='Female'?'selected':'' ?>>Female</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label-glass">Birthdate</label>
        <input type="date" name="birthdate" class="form-control form-control-glass" value="<?= h($ee['birthdate'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label-glass">Civil Status</label>
        <select name="civil_status" class="form-select form-control-glass form-select-glass">
          <option value="">—</option>
          <?php foreach (['Single','Married','Widowed','Separated','Others'] as $cs): ?>
            <option value="<?=$cs?>" <?= ($ee['civil_status']??'')===$cs?'selected':'' ?>><?=$cs?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label-glass">Role</label>
        <select name="role" class="form-select form-control-glass form-select-glass">
          <option value="employee" <?= ($ee['role']??'')==='employee'?'selected':'' ?>>Employee</option>
          <option value="hr" <?= ($ee['role']??'')==='hr'?'selected':'' ?>>HR</option>
          <option value="admin" <?= ($ee['role']??'')==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <label style="cursor:pointer;font-size:.9rem;">
          <input type="checkbox" name="is_active" value="1" <?= ($ee['is_active']??1)?'checked':'' ?>> Active
        </label>
      </div>
    </div>
    <?php endif; ?>
    <div class="d-flex gap-2">
      <button type="submit" class="btn-glass btn-glass-success">
        <i class="fas fa-save me-1"></i><?= $action === 'add' ? 'Add Employee' : 'Save Changes' ?>
      </button>
      <a href="<?= BASE_URL ?>/modules/employees.php" class="btn-glass text-decoration-none">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Search + Table -->
<div class="glass-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 fw-bold">All Employees (<?= count($employees) ?>)</h6>
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-glass"
             value="<?= h($search) ?>" placeholder="Search name, email, no..." style="width:220px;">
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
          <th>Emp No.</th><th>Name</th><th>Email</th><th>Sex</th>
          <th>Role</th><th>Status</th><th>PDS</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$employees): ?>
          <tr><td colspan="8" class="text-center" style="color:var(--text-muted);padding:30px;">
            No employees found.
          </td></tr>
        <?php else: foreach ($employees as $e): ?>
          <tr>
            <td><span class="badge-glass"><?= h($e['employee_no'] ?? '—') ?></span></td>
            <td><strong><?= h(trim(($e['last_name'] ?? '') . ', ' . ($e['first_name'] ?? ''))) ?></strong>
              <?php if ($e['middle_name']): ?><small style="color:var(--text-muted);"> <?= h($e['middle_name']) ?></small><?php endif; ?>
            </td>
            <td style="font-size:.82rem;color:var(--text-muted);"><?= h($e['email'] ?? '—') ?></td>
            <td><?= h($e['sex'] ?? '—') ?></td>
            <td><span class="badge-glass" style="font-size:.7rem;"><?= h($e['role'] ?? 'employee') ?></span></td>
            <td>
              <?php if ($e['is_active']): ?>
                <span style="color:#34d399;font-size:.8rem;"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>Active</span>
              <?php else: ?>
                <span style="color:#f87171;font-size:.8rem;"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($e['is_submitted']): ?>
                <span style="color:#34d399;font-size:.78rem;"><i class="fas fa-check-circle me-1"></i>Done</span>
              <?php else: ?>
                <span style="color:#fbbf24;font-size:.78rem;"><i class="fas fa-clock me-1"></i>Pending</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?= BASE_URL ?>/pds/preview.php?emp_id=<?= $e['id'] ?>"
                   class="btn-glass" style="padding:4px 9px;font-size:.75rem;" title="View PDS">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="?action=edit&id=<?= $e['id'] ?>"
                   class="btn-glass" style="padding:4px 9px;font-size:.75rem;" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="<?= BASE_URL ?>/pds/print.php?emp_id=<?= $e['id'] ?>" target="_blank"
                   class="btn-glass" style="padding:4px 9px;font-size:.75rem;" title="Print PDS">
                  <i class="fas fa-print"></i>
                </a>
                <a href="?action=delete&id=<?= $e['id'] ?>"
                   class="btn-glass btn-glass-danger" style="padding:4px 9px;font-size:.75rem;" title="Deactivate"
                   onclick="return confirm('Deactivate this employee?')">
                  <i class="fas fa-ban"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
