<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAuth();

$pageTitle = 'PDS Preview';

// Admin can view any employee's PDS via ?emp_id=
if (isAdmin() && isset($_GET['emp_id'])) {
    $employeeId = (int)$_GET['emp_id'];
} else {
    $employeeId = (int)($_SESSION['employee_id'] ?? 0);
}

if (!$employeeId) {
    header('Location: ' . BASE_URL . '/pds/form.php');
    exit;
}

// Load all data
$s = $conn->prepare("SELECT e.*, u.email FROM employees e LEFT JOIN users u ON e.user_id=u.id WHERE e.id=?");
$s->bind_param('i', $employeeId); $s->execute();
$emp = $s->get_result()->fetch_assoc() ?? [];
$s->close();

$s = $conn->prepare("SELECT * FROM family_background WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$fam = $s->get_result()->fetch_assoc() ?? [];
$s->close();

$s = $conn->prepare("SELECT * FROM children WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$children = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM education WHERE employee_id=? ORDER BY FIELD(level,'Elementary','Secondary','Vocational','College','Graduate Studies')");
$s->bind_param('i', $employeeId); $s->execute();
$education = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM eligibility WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$eligibility = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM work_experience WHERE employee_id=? ORDER BY start_date DESC");
$s->bind_param('i', $employeeId); $s->execute();
$workExp = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM voluntary_work WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$voluntaryWork = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM learning_development WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$ldRecords = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM other_info WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$otherInfo = $s->get_result()->fetch_assoc() ?? [];
$s->close();

$s = $conn->prepare("SELECT * FROM pds_questions WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$questions = $s->get_result()->fetch_assoc() ?? [];
$s->close();

$s = $conn->prepare("SELECT * FROM references_info WHERE employee_id=? ORDER BY id LIMIT 3");
$s->bind_param('i', $employeeId); $s->execute();
$references = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$s = $conn->prepare("SELECT * FROM pds_status WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$pdsStatus = $s->get_result()->fetch_assoc();
$s->close();

function d($v) { return htmlspecialchars((string)($v ?? '—'), ENT_QUOTES, 'UTF-8'); }
function df($v) { return $v ? date('M j, Y', strtotime($v)) : '—'; }

require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 no-print">
  <div>
    <h4 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2" style="color:#60a5fa;"></i>PDS Preview</h4>
    <small style="color:var(--text-muted);">CSC Form 212 — <?= d($emp['last_name']) ?>, <?= d($emp['first_name']) ?></small>
  </div>
  <div class="d-flex gap-2">
    <?php if (!isAdmin()): ?>
    <a href="<?= BASE_URL ?>/pds/form.php" class="btn-glass text-decoration-none" style="padding:8px 16px;font-size:.85rem;">
      <i class="fas fa-edit me-1"></i>Edit PDS
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/pds/print.php<?= isAdmin() && isset($_GET['emp_id']) ? '?emp_id='.(int)$_GET['emp_id'] : '' ?>"
       target="_blank" class="btn-glass text-decoration-none" style="padding:8px 16px;font-size:.85rem;">
      <i class="fas fa-print me-1"></i>Print
    </a>
    <?php if (isAdmin()): ?>
    <a href="<?= BASE_URL ?>/modules/employees.php" class="btn-glass text-decoration-none" style="padding:8px 16px;font-size:.85rem;">
      <i class="fas fa-arrow-left me-1"></i>Back
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($pdsStatus && $pdsStatus['is_submitted']): ?>
  <div class="alert-glass alert-glass-success mb-3 no-print">
    <i class="fas fa-check-double me-2"></i>PDS submitted on <?= df($pdsStatus['submitted_at']) ?>
  </div>
<?php endif; ?>

<!-- I. PERSONAL INFORMATION -->
<div class="glass-card mb-3">
  <p class="section-title">I. PERSONAL INFORMATION</p>
  <div class="row g-2" style="font-size:.875rem;">
    <div class="col-md-3"><span style="color:var(--text-muted);">Last Name:</span><br><strong><?= d($emp['last_name']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">First Name:</span><br><strong><?= d($emp['first_name']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Middle Name:</span><br><strong><?= d($emp['middle_name']) ?></strong></div>
    <div class="col-md-1"><span style="color:var(--text-muted);">Ext.:</span><br><strong><?= d($emp['name_extension']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Date of Birth:</span><br><strong><?= df($emp['birthdate']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Place of Birth:</span><br><strong><?= d($emp['place_of_birth']) ?></strong></div>
    <div class="col-md-2"><span style="color:var(--text-muted);">Sex:</span><br><strong><?= d($emp['sex']) ?></strong></div>
    <div class="col-md-2"><span style="color:var(--text-muted);">Civil Status:</span><br><strong><?= d($emp['civil_status']) ?></strong></div>
    <div class="col-md-2"><span style="color:var(--text-muted);">Height:</span><br><strong><?= d($emp['height']) ?></strong></div>
    <div class="col-md-2"><span style="color:var(--text-muted);">Weight:</span><br><strong><?= d($emp['weight']) ?></strong></div>
    <div class="col-md-2"><span style="color:var(--text-muted);">Blood Type:</span><br><strong><?= d($emp['blood_type']) ?></strong></div>
    <div class="col-md-2"><span style="color:var(--text-muted);">Citizenship:</span><br><strong><?= d($emp['citizenship']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">GSIS:</span><br><strong><?= d($emp['gsis']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">PAG-IBIG:</span><br><strong><?= d($emp['pagibig']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">PHILHEALTH:</span><br><strong><?= d($emp['philhealth']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">SSS:</span><br><strong><?= d($emp['sss']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">TIN:</span><br><strong><?= d($emp['tin']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Mobile:</span><br><strong><?= d($emp['mobile']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Telephone:</span><br><strong><?= d($emp['telephone']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Email:</span><br><strong><?= d($emp['email_address'] ?: $emp['email']) ?></strong></div>
    <div class="col-12 mt-1"><span style="color:var(--text-muted);font-size:.75rem;">RESIDENTIAL ADDRESS:</span><br>
      <strong><?= d(trim(implode(', ', array_filter([
        $emp['residential_house'], $emp['residential_street'],
        $emp['residential_subdivision'], $emp['residential_barangay'],
        $emp['residential_city'], $emp['residential_province']
      ])))) ?> <?= $emp['residential_zip'] ? '('.$emp['residential_zip'].')' : '' ?></strong>
    </div>
    <div class="col-12"><span style="color:var(--text-muted);font-size:.75rem;">PERMANENT ADDRESS:</span><br>
      <?php if (!empty($emp['permanent_same'])): ?>
        <strong><em>(Same as residential)</em></strong>
      <?php else: ?>
        <strong><?= d(trim(implode(', ', array_filter([
          $emp['permanent_house'], $emp['permanent_street'],
          $emp['permanent_subdivision'], $emp['permanent_barangay'],
          $emp['permanent_city'], $emp['permanent_province']
        ])))) ?> <?= $emp['permanent_zip'] ? '('.$emp['permanent_zip'].')' : '' ?></strong>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- II. FAMILY -->
<div class="glass-card mb-3">
  <p class="section-title">II. FAMILY BACKGROUND</p>
  <div class="row g-2" style="font-size:.875rem;">
    <div class="col-md-12" style="color:var(--text-muted);font-size:.72rem;font-weight:700;">SPOUSE</div>
    <div class="col-md-4"><span style="color:var(--text-muted);">Surname:</span><br><strong><?= d($fam['spouse_surname']) ?></strong></div>
    <div class="col-md-4"><span style="color:var(--text-muted);">First Name:</span><br><strong><?= d($fam['spouse_firstname']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Middle Name:</span><br><strong><?= d($fam['spouse_middlename']) ?></strong></div>
    <div class="col-md-3"><span style="color:var(--text-muted);">Occupation:</span><br><strong><?= d($fam['spouse_occupation']) ?></strong></div>
    <div class="col-md-4"><span style="color:var(--text-muted);">Employer:</span><br><strong><?= d($fam['spouse_employer']) ?></strong></div>
    <div class="col-md-12 mt-2" style="color:var(--text-muted);font-size:.72rem;font-weight:700;">FATHER</div>
    <div class="col-md-4"><strong><?= d($fam['father_surname']) ?>, <?= d($fam['father_firstname']) ?> <?= d($fam['father_middlename']) ?></strong></div>
    <div class="col-md-12 mt-2" style="color:var(--text-muted);font-size:.72rem;font-weight:700;">MOTHER</div>
    <div class="col-md-4"><strong><?= d($fam['mother_surname']) ?>, <?= d($fam['mother_firstname']) ?> <?= d($fam['mother_middlename']) ?></strong></div>
  </div>
  <?php if ($children): ?>
    <div class="mt-3" style="font-size:.875rem;">
      <span style="color:var(--text-muted);font-size:.72rem;font-weight:700;">CHILDREN</span>
      <table class="table table-glass mt-1 mb-0" style="font-size:.82rem;">
        <thead><tr><th>#</th><th>Name</th><th>Date of Birth</th></tr></thead>
        <tbody>
          <?php foreach ($children as $i => $c): ?>
            <tr><td><?= $i+1 ?></td><td><?= d($c['child_name']) ?></td><td><?= df($c['date_of_birth']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- III. EDUCATION -->
<div class="glass-card mb-3">
  <p class="section-title">III. EDUCATIONAL BACKGROUND</p>
  <div class="table-responsive">
    <table class="table table-glass" style="font-size:.82rem;">
      <thead>
        <tr>
          <th>Level</th><th>School</th><th>Degree/Course</th>
          <th>Period</th><th>Yr. Graduated</th><th>Honors</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($education): foreach ($education as $e): ?>
          <tr>
            <td><?= d($e['level']) ?></td>
            <td><?= d($e['school']) ?></td>
            <td><?= d($e['degree']) ?></td>
            <td><?= d($e['from_year']) ?> – <?= d($e['to_year']) ?></td>
            <td><?= d($e['year_graduated']) ?></td>
            <td><?= d($e['honors']) ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center" style="color:var(--text-muted);">No education records</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- IV. ELIGIBILITY -->
<div class="glass-card mb-3">
  <p class="section-title">IV. CIVIL SERVICE ELIGIBILITY</p>
  <div class="table-responsive">
    <table class="table table-glass" style="font-size:.82rem;">
      <thead>
        <tr><th>Career Service</th><th>Rating</th><th>Date of Exam</th>
            <th>Place</th><th>License No.</th><th>Validity</th></tr>
      </thead>
      <tbody>
        <?php if ($eligibility): foreach ($eligibility as $e): ?>
          <tr>
            <td><?= d($e['career_service']) ?></td>
            <td><?= d($e['rating']) ?></td>
            <td><?= df($e['date_of_exam']) ?></td>
            <td><?= d($e['place_of_exam']) ?></td>
            <td><?= d($e['license_no']) ?></td>
            <td><?= df($e['license_validity']) ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center" style="color:var(--text-muted);">No eligibility records</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- V. WORK EXPERIENCE -->
<div class="glass-card mb-3">
  <p class="section-title">V. WORK EXPERIENCE</p>
  <div class="table-responsive">
    <table class="table table-glass" style="font-size:.82rem;">
      <thead>
        <tr><th>From</th><th>To</th><th>Position</th><th>Dept/Company</th>
            <th>Salary</th><th>SG/Step</th><th>Appointment</th><th>Gov't</th></tr>
      </thead>
      <tbody>
        <?php if ($workExp): foreach ($workExp as $w): ?>
          <tr>
            <td><?= df($w['start_date']) ?></td>
            <td><?= $w['is_present'] ? '<span style="color:#34d399">Present</span>' : df($w['end_date']) ?></td>
            <td><?= d($w['position_title']) ?></td>
            <td><?= d($w['department']) ?></td>
            <td><?= d($w['monthly_salary']) ?></td>
            <td><?= d($w['salary_grade']) ?></td>
            <td><?= d($w['status_appointment']) ?></td>
            <td><?= d($w['is_government']) ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="8" class="text-center" style="color:var(--text-muted);">No work experience records</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- VI-VII. VOLUNTARY / L&D -->
<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="glass-card h-100">
      <p class="section-title">VI. VOLUNTARY WORK</p>
      <?php if ($voluntaryWork): ?>
        <?php foreach ($voluntaryWork as $v): ?>
          <div style="font-size:.82rem;padding:8px 0;border-bottom:1px solid var(--glass-border);">
            <strong><?= d($v['organization']) ?></strong><br>
            <span style="color:var(--text-muted);"><?= df($v['from_date']) ?> – <?= df($v['to_date']) ?> | <?= d($v['hours_count']) ?> hrs</span><br>
            <?= d($v['position_nature']) ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem;">No voluntary work records</p>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="glass-card h-100">
      <p class="section-title">VII. LEARNING & DEVELOPMENT</p>
      <?php if ($ldRecords): ?>
        <?php foreach ($ldRecords as $l): ?>
          <div style="font-size:.82rem;padding:8px 0;border-bottom:1px solid var(--glass-border);">
            <strong><?= d($l['title']) ?></strong><br>
            <span style="color:var(--text-muted);"><?= df($l['from_date']) ?> – <?= df($l['to_date']) ?> | <?= d($l['hours_count']) ?> hrs | <?= d($l['ld_type']) ?></span><br>
            <?= d($l['conducted_by']) ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem;">No L&D records</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- VIII. OTHER INFO -->
<div class="glass-card mb-3">
  <p class="section-title">VIII. OTHER INFORMATION</p>
  <div class="row g-3" style="font-size:.875rem;">
    <div class="col-md-4">
      <span style="color:var(--text-muted);">Special Skills/Hobbies:</span><br>
      <pre style="white-space:pre-wrap;font-family:inherit;color:white;"><?= d($otherInfo['special_skills'] ?? '') ?: '—' ?></pre>
    </div>
    <div class="col-md-4">
      <span style="color:var(--text-muted);">Non-Academic Distinctions:</span><br>
      <pre style="white-space:pre-wrap;font-family:inherit;color:white;"><?= d($otherInfo['non_academic_distinctions'] ?? '') ?: '—' ?></pre>
    </div>
    <div class="col-md-4">
      <span style="color:var(--text-muted);">Organization Memberships:</span><br>
      <pre style="white-space:pre-wrap;font-family:inherit;color:white;"><?= d($otherInfo['org_memberships'] ?? '') ?: '—' ?></pre>
    </div>
  </div>
</div>

<!-- IX. QUESTIONS -->
<div class="glass-card mb-3">
  <p class="section-title">IX. BACKGROUND INFORMATION</p>
  <?php
  $qLabels = [
    'q34a'=>'34a. Related to appointing authority (3rd degree)?',
    'q34b'=>'34b. Related to appointing authority (4th degree - LGU)?',
    'q35a'=>'35a. Found guilty of administrative offense?',
    'q35b'=>'35b. Criminally charged before any court?',
    'q36' =>'36. Convicted of any crime?',
    'q37' =>'37. Separated from service?',
    'q38a'=>'38a. Candidate in national/local election within last year?',
    'q38b'=>'38b. Resigned to campaign for a candidate?',
    'q39' =>'39. Acquired immigrant/permanent resident status in another country?',
    'q40a'=>'40a. Dual citizen?',
    'q40b'=>'40b. Member of indigenous group?',
    'q40c'=>'40c. Person with disability (PWD)?',
  ];
  foreach ($qLabels as $k => $label):
    $ans = $questions[$k] ?? 'No';
  ?>
  <div class="d-flex justify-content-between" style="font-size:.82rem;padding:6px 0;border-bottom:1px solid var(--glass-border);">
    <span style="flex:1;"><?= $label ?></span>
    <span style="font-weight:700;color:<?= $ans==='Yes'?'#f87171':'#34d399' ?>;margin-left:12px;"><?= $ans ?></span>
  </div>
  <?php if ($ans==='Yes' && !empty($questions[$k.'_details'])): ?>
    <div style="font-size:.78rem;color:var(--text-muted);padding:4px 0 8px 16px;">
      Details: <?= d($questions[$k.'_details']) ?>
    </div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<!-- X. REFERENCES -->
<div class="glass-card mb-3">
  <p class="section-title">X. CHARACTER REFERENCES</p>
  <div class="row g-3">
    <?php foreach ($references as $i => $r): ?>
      <div class="col-md-4" style="font-size:.875rem;">
        <strong><?= d($r['ref_name']) ?></strong><br>
        <span style="color:var(--text-muted);"><?= d($r['ref_address']) ?></span><br>
        <?= d($r['ref_tel']) ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$references): ?>
      <div class="col-12" style="color:var(--text-muted);font-size:.85rem;">No references provided</div>
    <?php endif; ?>
  </div>
</div>

<div class="no-print d-flex gap-3 mt-3">
  <?php if (!isAdmin()): ?>
    <a href="<?= BASE_URL ?>/pds/form.php" class="btn-glass text-decoration-none">
      <i class="fas fa-edit me-1"></i>Edit PDS
    </a>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>/pds/print.php<?= isAdmin() && isset($_GET['emp_id']) ? '?emp_id='.(int)$_GET['emp_id'] : '' ?>"
     target="_blank" class="btn-glass text-decoration-none">
    <i class="fas fa-print me-1"></i>Print PDS
  </a>
</div>

<?php require_once '../includes/footer.php'; ?>
