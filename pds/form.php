<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAuth();

$pageTitle  = 'Personal Data Sheet — CS Form 212';
$userId     = (int)$_SESSION['user_id'];
$employeeId = $_SESSION['employee_id'] ? (int)$_SESSION['employee_id'] : null;

$emp = $fam = $otherInfo = $questions = [];
$children = $education = $eligibility = $workExp = $voluntaryWork = $ldRecords = $references = [];

if ($employeeId) {
    $s = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $s->bind_param('i', $employeeId); $s->execute();
    $emp = $s->get_result()->fetch_assoc() ?? []; $s->close();

    $s = $conn->prepare("SELECT * FROM family_background WHERE employee_id = ?");
    $s->bind_param('i', $employeeId); $s->execute();
    $fam = $s->get_result()->fetch_assoc() ?? []; $s->close();

    $s = $conn->prepare("SELECT * FROM children WHERE employee_id = ? ORDER BY id");
    $s->bind_param('i', $employeeId); $s->execute();
    $children = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

    $s = $conn->prepare("SELECT * FROM education WHERE employee_id = ? ORDER BY FIELD(level,'Elementary','Secondary','Vocational','Vocational/Trade Course','College','Graduate Studies')");
    $s->bind_param('i', $employeeId); $s->execute();
    $education = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

    $s = $conn->prepare("SELECT * FROM eligibility WHERE employee_id = ? ORDER BY id");
    $s->bind_param('i', $employeeId); $s->execute();
    $eligibility = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

    $s = $conn->prepare("SELECT * FROM work_experience WHERE employee_id = ? ORDER BY start_date DESC");
    $s->bind_param('i', $employeeId); $s->execute();
    $workExp = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

    $s = $conn->prepare("SELECT * FROM voluntary_work WHERE employee_id = ? ORDER BY id");
    $s->bind_param('i', $employeeId); $s->execute();
    $voluntaryWork = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

    $s = $conn->prepare("SELECT * FROM learning_development WHERE employee_id = ? ORDER BY id");
    $s->bind_param('i', $employeeId); $s->execute();
    $ldRecords = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

    $s = $conn->prepare("SELECT * FROM other_info WHERE employee_id = ?");
    $s->bind_param('i', $employeeId); $s->execute();
    $otherInfo = $s->get_result()->fetch_assoc() ?? []; $s->close();

    $s = $conn->prepare("SELECT * FROM pds_questions WHERE employee_id = ?");
    $s->bind_param('i', $employeeId); $s->execute();
    $questions = $s->get_result()->fetch_assoc() ?? []; $s->close();

    $s = $conn->prepare("SELECT * FROM references_info WHERE employee_id = ? ORDER BY id LIMIT 3");
    $s->bind_param('i', $employeeId); $s->execute();
    $references = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
}

$eduMap = [];
foreach ($education as $e) { $eduMap[$e['level']] = $e; }
while (count($references) < 3) { $references[] = []; }

function fv($a, $k)      { return htmlspecialchars((string)($a[$k] ?? ''), ENT_QUOTES, 'UTF-8'); }
function crc($a, $k, $v) { return (isset($a[$k]) && $a[$k] === $v) ? 'checked' : ''; }

require_once '../includes/header.php';
?>
<style>
/* ============================================================
   CS FORM 212 — EXACT PAPER REPLICA STYLES
   ============================================================ */
.pds-wrap { padding:10px 0 60px; }

/* Action bar */
.pds-bar {
  max-width:1050px; margin:0 auto 10px;
  display:flex; gap:8px; flex-wrap:wrap; align-items:center;
}

/* The white paper container — A4 proportions at screen size */
.pds-paper {
  max-width:1050px; margin:0 auto;
  background:#fff; color:#000;
  font-family:Arial,Helvetica,sans-serif;
  font-size:7.8pt;
  box-shadow:0 2px 24px rgba(0,0,0,.55);
  border:1px solid #777;
}

/* All tables share base border style */
.pf { width:100%; border-collapse:collapse; table-layout:fixed; }
.pf td, .pf th {
  border:1px solid #000;
  padding:1px 3px;
  vertical-align:top;
  word-wrap:break-word;
  overflow:hidden;
}
/* Remove double border between adjacent tables */
.pf-join { border-top:none; }
.pf-join td, .pf-join th { border-top:none; }

/* ── Section header (dark gray bar) ── */
.sec-hdr {
  background:#404040; color:#fff;
  font-size:7.5pt; font-weight:700;
  text-transform:uppercase; letter-spacing:.5px;
  padding:3px 6px !important;
  text-align:left;
}

/* ── Column header (light gray, centered) ── */
.col-hdr {
  background:#c6c6c6; font-weight:700;
  font-size:6.5pt; text-transform:uppercase;
  text-align:center; padding:2px 3px !important;
  line-height:1.25;
}

/* ── Field label (tiny italic, sits above input) ── */
.lbl {
  font-size:6pt; font-style:italic; color:#222;
  display:block; line-height:1.2; margin-bottom:1px;
  text-transform:uppercase;
}

/* ── Text/date inputs ── */
.fi {
  width:100%; border:none; background:transparent;
  font-family:Arial,Helvetica,sans-serif;
  font-size:9pt; color:#000;
  outline:none; padding:0; margin:0;
  box-sizing:border-box;
}
.fi:focus { background:#fffff0; }
input.fi[type="date"] { font-size:7.8pt; }
select.fi {
  height:18px; font-size:7.8pt; cursor:pointer;
  padding:0; border:none; background:transparent;
  width:100%;
}
textarea.fi { resize:vertical; min-height:20px; font-size:7.8pt; }

/* ── Radio/checkbox rows ── */
.rb { display:flex; flex-wrap:wrap; gap:1px 7px; margin-top:1px; }
.rb label {
  display:inline-flex; align-items:center; gap:2px;
  font-size:7.5pt; white-space:nowrap; cursor:pointer;
}
.rb input[type=radio], .rb input[type=checkbox] {
  width:10px; height:10px; accent-color:#000; margin:0;
}

/* ── Field value (large bold text — name fields) ── */
.fi-name { font-size:10pt; font-weight:700; text-transform:uppercase; }

/* ── Dynamic row buttons ── */
.add-btn {
  background:#e8f5e9; color:#1b5e20; border:1px dashed #4caf50;
  padding:1px 8px; font-size:6.5pt; cursor:pointer;
  border-radius:1px; font-weight:700; margin:2px 0 1px;
}
.del-btn {
  float:right; background:#ffebee; color:#b71c1c;
  border:1px solid #ef9a9a; font-size:6pt;
  cursor:pointer; padding:0 4px; border-radius:1px; margin-left:3px;
}

/* ── Page-break rule ── */
.pg-sep {
  border:none; border-top:2px dashed #bbb;
  margin:0; padding:4px 0;
  text-align:center; font-size:7pt; color:#999;
  background:#f5f5f5;
}

/* ── Letterhead area ── */
.lh-main {
  text-align:center; padding:8px 16px 6px;
  border-right:1px solid #000;
}
.lh-title {
  font-size:14pt; font-weight:900;
  letter-spacing:3px; text-transform:uppercase;
  line-height:1.1;
}
.lh-sub { font-size:8.5pt; font-weight:600; margin-top:2px; }
.lh-code { font-size:7.5pt; color:#444; margin-top:1px; }

/* ── Warning banner ── */
.warn-bar {
  background:#fff9c4; font-size:7pt; line-height:1.5;
  padding:4px 10px;
  border-top:1px solid #000;
}

/* ── Photo / thumbmark boxes ── */
.photo-box {
  border:1px solid #000; display:flex;
  align-items:center; justify-content:center;
  font-size:6.5pt; text-align:center;
  color:#555; line-height:1.4;
}

@media print {
  .no-print, .pds-bar, .sidebar,
  .main-content > .d-flex,
  .add-btn, .del-btn { display:none !important; }
  body .main-content { margin-left:0 !important; padding:0 !important; }
  .pds-paper { box-shadow:none; max-width:100%; border:none; }
  .pg-sep { border:none; page-break-after:always; }
  @page { margin:.5cm; }
}
</style>

<div class="pds-wrap">

<!-- ═══ ACTION BAR ═══ -->
<div class="pds-bar no-print">
  <?php if (isset($_GET['saved'])): ?>
    <div class="alert-glass alert-glass-success" style="padding:5px 12px;font-size:.8rem;">
      <i class="fas fa-check-circle me-1"></i>Saved successfully!
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="alert-glass" style="padding:5px 12px;font-size:.8rem;color:#f87171;">
      <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  <span style="color:var(--text-muted);font-size:.78rem;flex:1;">
    <i class="fas fa-file-alt me-1" style="color:#60a5fa;"></i>
    CS Form No.&nbsp;212 (Revised 2025) &mdash; Personal Data Sheet
  </span>
  <button type="submit" form="pdsForm" name="action" value="save"
          class="btn-glass btn-glass-success" style="padding:6px 16px;font-size:.8rem;">
    <i class="fas fa-save me-1"></i>Save
  </button>
  <button type="submit" form="pdsForm" name="action" value="submit"
          class="btn-glass" style="padding:6px 16px;font-size:.8rem;"
          onclick="return confirm('Submit PDS for review?')">
    <i class="fas fa-paper-plane me-1"></i>Submit
  </button>
  <a href="<?= BASE_URL ?>/pds/print.php" target="_blank"
     class="btn-glass text-decoration-none" style="padding:6px 16px;font-size:.8rem;">
    <i class="fas fa-print me-1"></i>Print
  </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/pds/save.php" id="pdsForm">
<div class="pds-paper">

<!-- ════════════════════════════════════════════════════════
     LETTERHEAD
════════════════════════════════════════════════════════ -->
<table class="pf" style="border-bottom:2px solid #000;">
  <colgroup>
    <col style="width:75%">
    <col style="width:25%">
  </colgroup>
  <tr>
    <td style="border:none; border-right:1px solid #000; padding:8px 20px 6px; text-align:center;">
      <div class="lh-title">Personal Data Sheet</div>
      <div class="lh-sub">Republic of the Philippines — Civil Service Commission</div>
      <div class="lh-code">CS Form No. 212 (Revised 2025)</div>
    </td>
    <td style="border:none; padding:6px 10px; vertical-align:middle;">
      <span class="lbl">Date Filed</span>
      <input type="date" name="date_filed" class="fi" style="font-size:8pt;"
             value="<?= fv($emp,'date_filed') ?>">
    </td>
  </tr>
  <tr>
    <td colspan="2" class="warn-bar">
      <b>WARNING:</b> Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause
      the filing of administrative/criminal case/s against the person concerned.<br>
      <b>READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.</b>
      DO NOT ABBREVIATE. Print legibly. Tick appropriate boxes (&#10003;). Indicate N/A if not applicable.
    </td>
  </tr>
</table>

<!-- ════════════════════════════════════════════════════════
     PAGE 1
════════════════════════════════════════════════════════ -->

<!-- ──── I. PERSONAL INFORMATION ──── -->
<table class="pf pf-join">
  <!-- Section header spanning full width -->
  <tr>
    <td colspan="10" class="sec-hdr">I. &nbsp;Personal Information</td>
  </tr>

  <!--
    CSC Layout — Row by row, left panel ~ 52%, right panel ~48%
    Left:  Surname / First+Ext / Middle Name
    Right: Date of Birth / Place of Birth / Sex + Civil Status
    Then: Height/Weight/Blood | Citizenship
    Then: IDs (4 cols)
    Then: TIN+Agency (2 splits)
    Then: Addresses
    Then: Contact info
  -->

  <!-- ROW: Surname | Date of Birth -->
  <tr>
    <td colspan="5" style="width:52%; border-right:2px solid #000; padding:2px 3px;">
      <span class="lbl">(1.) Surname</span>
      <input type="text" name="last_name" class="fi fi-name"
             value="<?= fv($emp,'last_name') ?>">
    </td>
    <td colspan="5" style="width:48%; padding:2px 3px;">
      <span class="lbl">(4.) Date of Birth <em style="font-style:normal;">(mm/dd/yyyy)</em></span>
      <input type="date" name="birthdate" class="fi" style="font-size:9pt;"
             value="<?= fv($emp,'birthdate') ?>">
    </td>
  </tr>

  <!-- ROW: First Name + Extension | Place of Birth -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000; padding:2px 3px;">
      <span class="lbl">
        (2.) First Name
        <span style="float:right;font-size:5.8pt;">Name Extension (Jr., Sr., III)</span>
      </span>
      <div style="display:flex;gap:0;align-items:center;">
        <input type="text" name="first_name" class="fi fi-name" style="flex:1;"
               value="<?= fv($emp,'first_name') ?>">
        <input type="text" name="name_extension" class="fi"
               style="width:68px;border-left:1px solid #bbb;padding-left:4px;font-size:8.5pt;"
               placeholder="Jr./Sr./III"
               value="<?= fv($emp,'name_extension') ?>">
      </div>
    </td>
    <td colspan="5" style="padding:2px 3px;">
      <span class="lbl">(5.) Place of Birth</span>
      <input type="text" name="place_of_birth" class="fi"
             value="<?= fv($emp,'place_of_birth') ?>">
    </td>
  </tr>

  <!-- ROW: Middle Name | Sex + Civil Status -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000; padding:2px 3px;">
      <span class="lbl">(3.) Middle Name</span>
      <input type="text" name="middle_name" class="fi" style="font-size:10pt;font-weight:700;text-transform:uppercase;"
             value="<?= fv($emp,'middle_name') ?>">
    </td>
    <td colspan="2" style="width:12%; padding:2px 3px; border-right:1px solid #000;">
      <span class="lbl">(6.) Sex at Birth</span>
      <div class="rb" style="flex-direction:column;gap:1px;">
        <label><input type="radio" name="sex" value="Male"   <?= crc($emp,'sex','Male') ?>>   Male</label>
        <label><input type="radio" name="sex" value="Female" <?= crc($emp,'sex','Female') ?>> Female</label>
      </div>
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">(7.) Civil Status</span>
      <div class="rb" style="flex-direction:column;gap:1px;">
        <label><input type="radio" name="civil_status" value="Single"      <?= crc($emp,'civil_status','Single') ?>>      Single</label>
        <label><input type="radio" name="civil_status" value="Married"     <?= crc($emp,'civil_status','Married') ?>>     Married</label>
        <label><input type="radio" name="civil_status" value="Widow/er"    <?= crc($emp,'civil_status','Widow/er') ?>>    Widow/er</label>
        <label><input type="radio" name="civil_status" value="Separated"   <?= crc($emp,'civil_status','Separated') ?>>   Separated</label>
        <label><input type="radio" name="civil_status" value="Solo Parent" <?= crc($emp,'civil_status','Solo Parent') ?>> Solo Parent</label>
        <label><input type="radio" name="civil_status" value="Others"      <?= crc($emp,'civil_status','Others') ?>>      Others &ndash; ____________</label>
      </div>
    </td>
  </tr>

  <!-- ROW: Height | Weight | Blood Type | Citizenship -->
  <tr>
    <td style="width:13%; padding:2px 3px;">
      <span class="lbl">(8.) Height <em>(m)</em></span>
      <input type="text" name="height" class="fi" placeholder="e.g. 1.65"
             value="<?= fv($emp,'height') ?>">
    </td>
    <td style="width:13%; padding:2px 3px;">
      <span class="lbl">(9.) Weight <em>(kg)</em></span>
      <input type="text" name="weight" class="fi" placeholder="e.g. 60"
             value="<?= fv($emp,'weight') ?>">
    </td>
    <td style="width:13%; padding:2px 3px; border-right:2px solid #000;">
      <span class="lbl">(10.) Blood Type</span>
      <select name="blood_type" class="fi">
        <option value=""></option>
        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
          <option value="<?= $bt ?>" <?= crc($emp,'blood_type',$bt) ?>><?= $bt ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td colspan="7" style="padding:3px 5px;">
      <span class="lbl">(16.) Citizenship</span>
      <div class="rb">
        <label style="font-size:8pt;font-weight:700;">
          <input type="radio" name="citizenship" value="Filipino"
                 <?= (($emp['citizenship'] ?? 'Filipino') !== 'Dual' ? 'checked' : '') ?>>
          Filipino
        </label>
        <label style="font-size:8pt;">
          <input type="radio" name="citizenship" value="Dual"
                 <?= (($emp['citizenship'] ?? '') === 'Dual' ? 'checked' : '') ?>>
          Dual Citizenship
        </label>
      </div>
      <div style="margin-top:3px;font-size:7.2pt;">
        If dual citizenship, pls. indicate type:&nbsp;
        <label><input type="radio" name="dual_citizenship_type" value="By birth"
               <?= crc($emp,'dual_citizenship_type','By birth') ?>> By birth</label>&nbsp;
        <label><input type="radio" name="dual_citizenship_type" value="By naturalization"
               <?= crc($emp,'dual_citizenship_type','By naturalization') ?>> By naturalization</label>
        &nbsp;&nbsp;
        <span style="font-style:italic;font-size:6.2pt;">Pls. indicate country:</span>
        <input type="text" name="dual_country" class="fi"
               style="display:inline;width:140px;border-bottom:1px solid #888;font-size:8pt;"
               value="<?= fv($emp,'dual_country') ?>">
      </div>
    </td>
  </tr>

  <!-- ROW: UMID | PAG-IBIG | PHILHEALTH | PHILSYS -->
  <tr>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">(10.) UMID ID No.</span>
      <input type="text" name="gsis" class="fi" placeholder="UMID No."
             value="<?= fv($emp,'gsis') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px; border-right:none;">
      <span class="lbl">(11.) Pag-IBIG ID No.</span>
      <input type="text" name="pagibig" class="fi" value="<?= fv($emp,'pagibig') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">(12.) PhilHealth No.</span>
      <input type="text" name="philhealth" class="fi" value="<?= fv($emp,'philhealth') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">(13.) PhilSys No. (PSN)</span>
      <input type="text" name="philsys_psn" class="fi" value="<?= fv($emp,'philsys_psn') ?>">
    </td>
  </tr>

  <!-- ROW: TIN | Agency Employee No -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000; padding:2px 3px;">
      <span class="lbl">(14.) TIN No.</span>
      <input type="text" name="tin" class="fi" value="<?= fv($emp,'tin') ?>">
    </td>
    <td colspan="5" style="padding:2px 3px;">
      <span class="lbl">(15.) Agency Employee No.</span>
      <input type="text" name="agency_employee_no" class="fi" value="<?= fv($emp,'agency_employee_no') ?>">
    </td>
  </tr>

  <!-- ROW: RESIDENTIAL ADDRESS label -->
  <tr>
    <td colspan="10" style="background:#e0e0e0; padding:2px 5px; font-size:7pt; font-weight:700;">
      (17.) RESIDENTIAL ADDRESS
    </td>
  </tr>
  <!-- Res: House/Block/Lot | Street | Subdivision -->
  <tr>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">House/Block/Lot No.</span>
      <input type="text" name="residential_house" class="fi" value="<?= fv($emp,'residential_house') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Street</span>
      <input type="text" name="residential_street" class="fi" value="<?= fv($emp,'residential_street') ?>">
    </td>
    <td colspan="5" style="padding:2px 3px;">
      <span class="lbl">Subdivision/Village</span>
      <input type="text" name="residential_subdivision" class="fi" value="<?= fv($emp,'residential_subdivision') ?>">
    </td>
  </tr>
  <!-- Res: Barangay | City/Municipality | Province | ZIP -->
  <tr>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Barangay</span>
      <input type="text" name="residential_barangay" class="fi" value="<?= fv($emp,'residential_barangay') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">City/Municipality</span>
      <input type="text" name="residential_city" class="fi" value="<?= fv($emp,'residential_city') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">Province</span>
      <input type="text" name="residential_province" class="fi" value="<?= fv($emp,'residential_province') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">ZIP Code</span>
      <input type="text" name="residential_zip" class="fi" value="<?= fv($emp,'residential_zip') ?>">
    </td>
  </tr>

  <!-- ROW: PERMANENT ADDRESS label -->
  <tr>
    <td colspan="10" style="background:#e0e0e0; padding:2px 5px; font-size:7pt; font-weight:700;">
      (18.) PERMANENT ADDRESS
      <label style="font-weight:400; margin-left:15px; font-size:6.5pt; text-transform:none; cursor:pointer;">
        <input type="checkbox" name="permanent_same" value="1"
               <?= !empty($emp['permanent_same']) ? 'checked' : '' ?>
               onchange="togglePermAddr(this)">
        Same as Residential Address
      </label>
    </td>
  </tr>
  <!-- Perm: House/Block/Lot | Street | Subdivision -->
  <tr id="permRow1">
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">House/Block/Lot No.</span>
      <input type="text" name="permanent_house" class="fi" value="<?= fv($emp,'permanent_house') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Street</span>
      <input type="text" name="permanent_street" class="fi" value="<?= fv($emp,'permanent_street') ?>">
    </td>
    <td colspan="5" style="padding:2px 3px;">
      <span class="lbl">Subdivision/Village</span>
      <input type="text" name="permanent_subdivision" class="fi" value="<?= fv($emp,'permanent_subdivision') ?>">
    </td>
  </tr>
  <!-- Perm: Barangay | City/Municipality | Province | ZIP -->
  <tr id="permRow2">
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Barangay</span>
      <input type="text" name="permanent_barangay" class="fi" value="<?= fv($emp,'permanent_barangay') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">City/Municipality</span>
      <input type="text" name="permanent_city" class="fi" value="<?= fv($emp,'permanent_city') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">Province</span>
      <input type="text" name="permanent_province" class="fi" value="<?= fv($emp,'permanent_province') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">ZIP Code</span>
      <input type="text" name="permanent_zip" class="fi" value="<?= fv($emp,'permanent_zip') ?>">
    </td>
  </tr>

  <!-- ROW: Telephone | Mobile | Email -->
  <tr>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">(19.) Telephone No.</span>
      <input type="text" name="telephone" class="fi" value="<?= fv($emp,'telephone') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">(20.) Mobile No.</span>
      <input type="text" name="mobile" class="fi" value="<?= fv($emp,'mobile') ?>">
    </td>
    <td colspan="5" style="padding:2px 3px;">
      <span class="lbl">(21.) E-Mail Address (if any)</span>
      <input type="email" name="email_address" class="fi" value="<?= fv($emp,'email_address') ?>">
    </td>
  </tr>
</table>

<!-- ──── II. FAMILY BACKGROUND ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="12" class="sec-hdr">II. &nbsp;Family Background</td>
  </tr>

  <!-- SPOUSE -->
  <!-- Sub-header: "SPOUSE'S NAME" -->
  <tr>
    <td colspan="12" style="background:#d8d8d8;padding:1px 5px;font-size:6.5pt;font-weight:700;text-transform:uppercase;">
      (22.) Spouse&rsquo;s Name &nbsp;<em style="font-weight:400;text-transform:none;">(if married)</em>
    </td>
  </tr>
  <tr>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Surname</span>
      <input type="text" name="spouse_surname" class="fi" style="text-transform:uppercase;"
             value="<?= fv($fam,'spouse_surname') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">First Name</span>
      <input type="text" name="spouse_firstname" class="fi" value="<?= fv($fam,'spouse_firstname') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Middle Name</span>
      <input type="text" name="spouse_middlename" class="fi" value="<?= fv($fam,'spouse_middlename') ?>">
    </td>
    <td colspan="1" style="padding:2px 3px;">
      <span class="lbl">Extension</span>
      <input type="text" name="spouse_extension" class="fi" value="<?= fv($fam,'spouse_extension') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">Telephone No.</span>
      <input type="text" name="spouse_telephone" class="fi" value="<?= fv($fam,'spouse_telephone') ?>">
    </td>
  </tr>
  <tr>
    <td colspan="4" style="padding:2px 3px;">
      <span class="lbl">Occupation/Nature of Work</span>
      <input type="text" name="spouse_occupation" class="fi" value="<?= fv($fam,'spouse_occupation') ?>">
    </td>
    <td colspan="4" style="padding:2px 3px;">
      <span class="lbl">Employer/Business Name</span>
      <input type="text" name="spouse_employer" class="fi" value="<?= fv($fam,'spouse_employer') ?>">
    </td>
    <td colspan="4" style="padding:2px 3px;">
      <span class="lbl">Business Address</span>
      <input type="text" name="spouse_business_address" class="fi" value="<?= fv($fam,'spouse_business_address') ?>">
    </td>
  </tr>

  <!-- FATHER -->
  <tr>
    <td colspan="12" style="background:#d8d8d8;padding:1px 5px;font-size:6.5pt;font-weight:700;text-transform:uppercase;">
      (24.) Father&rsquo;s Full Name
    </td>
  </tr>
  <tr>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Surname</span>
      <input type="text" name="father_surname" class="fi" style="text-transform:uppercase;"
             value="<?= fv($fam,'father_surname') ?>">
    </td>
    <td colspan="4" style="padding:2px 3px;">
      <span class="lbl">First Name</span>
      <input type="text" name="father_firstname" class="fi" value="<?= fv($fam,'father_firstname') ?>">
    </td>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Middle Name</span>
      <input type="text" name="father_middlename" class="fi" value="<?= fv($fam,'father_middlename') ?>">
    </td>
    <td colspan="2" style="padding:2px 3px;">
      <span class="lbl">Extension (Jr., Sr., III)</span>
      <input type="text" name="father_extension" class="fi" value="<?= fv($fam,'father_extension') ?>">
    </td>
  </tr>

  <!-- MOTHER -->
  <tr>
    <td colspan="12" style="background:#d8d8d8;padding:1px 5px;font-size:6.5pt;font-weight:700;text-transform:uppercase;">
      (25.) Mother&rsquo;s Maiden Name
    </td>
  </tr>
  <tr>
    <td colspan="3" style="padding:2px 3px;">
      <span class="lbl">Surname</span>
      <input type="text" name="mother_surname" class="fi" style="text-transform:uppercase;"
             value="<?= fv($fam,'mother_surname') ?>">
    </td>
    <td colspan="4" style="padding:2px 3px;">
      <span class="lbl">First Name</span>
      <input type="text" name="mother_firstname" class="fi" value="<?= fv($fam,'mother_firstname') ?>">
    </td>
    <td colspan="5" style="padding:2px 3px;">
      <span class="lbl">Middle Name</span>
      <input type="text" name="mother_middlename" class="fi" value="<?= fv($fam,'mother_middlename') ?>">
    </td>
  </tr>

  <!-- CHILDREN — (23.) -->
  <tr>
    <td colspan="8" class="col-hdr">(23.) Name of Children (Write Full Name and List All)</td>
    <td colspan="4" class="col-hdr">Date of Birth<br><em style="text-transform:none;font-weight:400;">(mm/dd/yyyy)</em></td>
  </tr>
  <tbody id="childrenBody">
    <?php
    $showChildRows = max(count($children), 6);
    for ($i = 0; $i < $showChildRows; $i++):
      $c = $children[$i] ?? [];
    ?>
    <tr id="child-row-<?= $i ?>">
      <td colspan="8" style="padding:1px 3px;">
        <?php if ($i < count($children)): ?>
          <button type="button" class="del-btn" onclick="delRow('child-row-<?= $i ?>')">&#10005;</button>
        <?php endif; ?>
        <input type="text" name="child_name[]" class="fi" value="<?= fv($c,'child_name') ?>">
      </td>
      <td colspan="4" style="padding:1px 3px;">
        <input type="date" name="child_dob[]" class="fi" value="<?= fv($c,'date_of_birth') ?>">
      </td>
    </tr>
    <?php endfor; ?>
  </tbody>
  <tr>
    <td colspan="12" style="padding:2px 4px;">
      <button type="button" class="add-btn" onclick="addChild()">&#43; Add Child</button>
    </td>
  </tr>
</table>

<div class="pg-sep">— Page 1 of 4 —</div>

<!-- ──── III. EDUCATIONAL BACKGROUND (26.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="6" class="sec-hdr">III. &nbsp;Educational Background</td>
  </tr>
  <!-- 2-row column header — matches CSC form exactly -->
  <tr>
    <td class="col-hdr" rowspan="2" style="width:13%; vertical-align:middle;">(26.) Level</td>
    <td class="col-hdr" rowspan="2" style="width:28%; vertical-align:middle;">
      Name of School<br>(Write in Full)
    </td>
    <td class="col-hdr" colspan="2" style="text-align:center; border-bottom:1px solid #888;">
      Period of Attendance
    </td>
    <td class="col-hdr" rowspan="2" style="width:14%; vertical-align:middle;">
      Highest Level/<br>Units Earned<br><em style="font-weight:400;text-transform:none;">(if not graduated)</em>
    </td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">
      Scholarship/Academic<br>Honors Received
    </td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:9%;">From</td>
    <td class="col-hdr" style="width:9%;">To</td>
  </tr>
  <?php
  $eduLevels2025 = ['Elementary','Secondary','Vocational/Trade Course','College','Graduate Studies'];
  foreach ($eduLevels2025 as $lvl):
    $mapKey = ($lvl === 'Vocational/Trade Course') ? 'Vocational' : $lvl;
    $e = $eduMap[$mapKey] ?? $eduMap[$lvl] ?? [];
  ?>
  <tr>
    <td style="font-size:7.5pt; font-weight:700; padding:2px 4px; vertical-align:middle;">
      <input type="hidden" name="edu_level[]" value="<?= htmlspecialchars($lvl, ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($lvl, ENT_QUOTES, 'UTF-8') ?>
    </td>
    <td style="padding:1px 3px;"><input type="text" name="edu_school[]" class="fi" value="<?= fv($e,'school') ?>"></td>
    <td style="padding:1px 3px;"><input type="text" name="edu_from[]"   class="fi" value="<?= fv($e,'from_year') ?>" placeholder="YYYY"></td>
    <td style="padding:1px 3px;"><input type="text" name="edu_to[]"     class="fi" value="<?= fv($e,'to_year') ?>"   placeholder="YYYY"></td>
    <td style="padding:1px 3px;"><input type="text" name="edu_units[]"  class="fi" value="<?= fv($e,'units_earned') ?>"></td>
    <td style="padding:1px 3px;"><input type="text" name="edu_honors[]" class="fi" value="<?= fv($e,'honors') ?>"></td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- ──── IV. CIVIL SERVICE ELIGIBILITY (27.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="6" class="sec-hdr">IV. &nbsp;Civil Service Eligibility</td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:33%;">
      (27.) Career Service/ RA 1080 (Board/Bar) Under Special Laws/<br>
      CES/CSEE/Category II/IV Eligibility and Eligibilities<br>for Uniformed Personnel
    </td>
    <td class="col-hdr" style="width:8%;">Rating<br><em style="font-weight:400;text-transform:none;">(if applicable)</em></td>
    <td class="col-hdr" style="width:13%;">Date of Examination/<br>Conferment</td>
    <td class="col-hdr" style="width:24%;">Place of Examination/<br>Conferment</td>
    <td class="col-hdr" style="width:13%;">License No.<br><em style="font-weight:400;text-transform:none;">(if applicable)</em></td>
    <td class="col-hdr">Valid<br>Until</td>
  </tr>
  <tbody id="eligBody">
    <?php
    $showElig = max(count($eligibility), 6);
    for ($i = 0; $i < $showElig; $i++):
      $el = $eligibility[$i] ?? [];
    ?>
    <tr id="elig-row-<?= $i ?>">
      <td style="padding:1px 3px;">
        <?php if ($i < count($eligibility)): ?>
          <button type="button" class="del-btn" onclick="delRow('elig-row-<?= $i ?>')">&#10005;</button>
        <?php endif; ?>
        <input type="text" name="elig_career[]" class="fi" value="<?= fv($el,'career_service') ?>">
      </td>
      <td style="padding:1px 3px;"><input type="text" name="elig_rating[]"    class="fi" value="<?= fv($el,'rating') ?>"></td>
      <td style="padding:1px 3px;"><input type="date" name="elig_exam_date[]" class="fi" value="<?= fv($el,'date_of_exam') ?>"></td>
      <td style="padding:1px 3px;"><input type="text" name="elig_place[]"     class="fi" value="<?= fv($el,'place_of_exam') ?>"></td>
      <td style="padding:1px 3px;"><input type="text" name="elig_license[]"   class="fi" value="<?= fv($el,'license_no') ?>"></td>
      <td style="padding:1px 3px;"><input type="date" name="elig_validity[]"  class="fi" value="<?= fv($el,'license_validity') ?>"></td>
    </tr>
    <?php endfor; ?>
  </tbody>
  <tr>
    <td colspan="6" style="padding:2px 4px;">
      <button type="button" class="add-btn" onclick="addEligibility()">&#43; Add Eligibility</button>
    </td>
  </tr>
</table>

<div class="pg-sep">— Page 2 of 4 —</div>

<!-- ──── V. WORK EXPERIENCE (28.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="6" class="sec-hdr">V. &nbsp;Work Experience</td>
  </tr>
  <tr>
    <td colspan="6" style="font-size:6.8pt; background:#f0f0f0; padding:2px 5px;">
      (28.) (Include private employment. Start from your recent work. Description of duties should be indicated in the attached Work Experience Sheet.)
    </td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:10%;">Inclusive Dates<br>From<br><em style="font-weight:400;text-transform:none;">(dd/mm/yyyy)</em></td>
    <td class="col-hdr" style="width:10%;">Inclusive Dates<br>To<br><em style="font-weight:400;text-transform:none;">(dd/mm/yyyy)</em></td>
    <td class="col-hdr" style="width:24%;">Position Title<br><em style="font-weight:400;text-transform:none;">(Write in full/Do not abbreviate)</em></td>
    <td class="col-hdr" style="width:32%;">Department / Agency / Office / Company<br><em style="font-weight:400;text-transform:none;">(Write in full/Do not abbreviate)</em></td>
    <td class="col-hdr" style="width:16%;">Status of<br>Appointment</td>
    <td class="col-hdr" style="width:8%;">Gov&rsquo;t<br>Service<br>(Y/N)</td>
  </tr>
  <tbody id="workBody">
    <?php
    $showWork = max(count($workExp), 14);
    for ($i = 0; $i < $showWork; $i++):
      $w = $workExp[$i] ?? [];
      $present = !empty($w['is_present']);
    ?>
    <tr id="work-row-<?= $i ?>">
      <td style="padding:1px 3px;">
        <input type="date" name="work_start[]" class="fi" value="<?= fv($w,'start_date') ?>">
      </td>
      <td style="padding:1px 3px;">
        <input type="date" name="work_end[]" class="fi" id="wend-<?= $i ?>"
               value="<?= fv($w,'end_date') ?>" <?= $present ? 'disabled' : '' ?>>
        <label style="font-size:6.2pt;cursor:pointer;display:block;margin-top:1px;">
          <input type="checkbox" name="work_present[<?= $i ?>]" value="1"
                 <?= $present ? 'checked' : '' ?>
                 onchange="togglePresent(this,'wend-<?= $i ?>')"> Present
        </label>
      </td>
      <td style="padding:1px 3px;">
        <?php if ($i < count($workExp)): ?>
          <button type="button" class="del-btn" onclick="delRow('work-row-<?= $i ?>')">&#10005;</button>
        <?php endif; ?>
        <input type="text" name="work_position[]" class="fi" value="<?= fv($w,'position_title') ?>">
      </td>
      <td style="padding:1px 3px;"><input type="text" name="work_dept[]"        class="fi" value="<?= fv($w,'department') ?>"></td>
      <td style="padding:1px 3px;"><input type="text" name="work_appointment[]" class="fi" value="<?= fv($w,'status_appointment') ?>"></td>
      <td style="padding:1px 3px; text-align:center;">
        <select name="work_gov[]" class="fi" style="width:auto;">
          <option value="Y" <?= crc($w,'is_government','Y') ?>>Y</option>
          <option value="N" <?= crc($w,'is_government','N') ?>>N</option>
        </select>
      </td>
    </tr>
    <?php endfor; ?>
  </tbody>
  <tr>
    <td colspan="6" style="padding:2px 4px;">
      <button type="button" class="add-btn" onclick="addWork()">&#43; Add Work Experience</button>
    </td>
  </tr>
</table>

<div class="pg-sep">— Page 3 of 4 —</div>

<!-- ──── VI. VOLUNTARY WORK (29.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="5" class="sec-hdr">VI. &nbsp;Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organization/s</td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:34%;">
      (29.) Name &amp; Address of Organization<br><em style="font-weight:400;text-transform:none;">(Write in full)</em>
    </td>
    <td class="col-hdr" style="width:10%;">Inclusive Dates<br>From</td>
    <td class="col-hdr" style="width:10%;">Inclusive Dates<br>To</td>
    <td class="col-hdr" style="width:8%;">No. of Hours</td>
    <td class="col-hdr">Position/Nature of Work</td>
  </tr>
  <tbody id="voluntaryBody">
    <?php
    $showVol = max(count($voluntaryWork), 7);
    for ($i = 0; $i < $showVol; $i++):
      $v = $voluntaryWork[$i] ?? [];
    ?>
    <tr id="vol-row-<?= $i ?>">
      <td style="padding:1px 3px;">
        <?php if ($i < count($voluntaryWork)): ?>
          <button type="button" class="del-btn" onclick="delRow('vol-row-<?= $i ?>')">&#10005;</button>
        <?php endif; ?>
        <input type="text" name="vol_org[]"     class="fi" value="<?= fv($v,'organization') ?>">
        <input type="text" name="vol_address[]" class="fi" placeholder="(address)"
               style="font-size:7pt;border-top:1px dotted #bbb;margin-top:1px;padding-top:1px;"
               value="<?= fv($v,'org_address') ?>">
      </td>
      <td style="padding:1px 3px;"><input type="date" name="vol_from[]" class="fi" value="<?= fv($v,'from_date') ?>"></td>
      <td style="padding:1px 3px;"><input type="date" name="vol_to[]"   class="fi" value="<?= fv($v,'to_date') ?>"></td>
      <td style="padding:1px 3px;"><input type="number" name="vol_hours[]" class="fi" value="<?= fv($v,'hours_count') ?>"></td>
      <td style="padding:1px 3px;"><input type="text" name="vol_position[]" class="fi" value="<?= fv($v,'position_nature') ?>"></td>
    </tr>
    <?php endfor; ?>
  </tbody>
  <tr>
    <td colspan="5" style="padding:2px 4px;">
      <button type="button" class="add-btn" onclick="addVoluntary()">&#43; Add</button>
    </td>
  </tr>
</table>

<!-- ──── VII. LEARNING AND DEVELOPMENT (30.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="6" class="sec-hdr">VII. &nbsp;Learning and Development (L&amp;D) Interventions / Training Programs Attended</td>
  </tr>
  <tr>
    <td colspan="6" style="font-size:6.8pt; background:#f0f0f0; padding:2px 5px;">(Start from the most recent L&amp;D/Training Program)</td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:33%;">
      (30.) Title of Learning and Development Interventions /<br>Training Programs<br>
      <em style="font-weight:400;text-transform:none;">(Write in full)</em>
    </td>
    <td class="col-hdr" style="width:9%;">Inclusive Dates<br>From</td>
    <td class="col-hdr" style="width:9%;">Inclusive Dates<br>To</td>
    <td class="col-hdr" style="width:7%;">No. of Hours</td>
    <td class="col-hdr" style="width:12%;">
      Type of LD<br>
      <em style="font-weight:400;text-transform:none;font-size:5.5pt;">(Managerial/Supervisory/<br>Technical/Foundation)</em>
    </td>
    <td class="col-hdr">Conducted/Sponsored By<br><em style="font-weight:400;text-transform:none;">(Write in full)</em></td>
  </tr>
  <tbody id="ldBody">
    <?php
    $showLd = max(count($ldRecords), 7);
    for ($i = 0; $i < $showLd; $i++):
      $l = $ldRecords[$i] ?? [];
    ?>
    <tr id="ld-row-<?= $i ?>">
      <td style="padding:1px 3px;">
        <?php if ($i < count($ldRecords)): ?>
          <button type="button" class="del-btn" onclick="delRow('ld-row-<?= $i ?>')">&#10005;</button>
        <?php endif; ?>
        <input type="text" name="ld_title[]" class="fi" value="<?= fv($l,'title') ?>">
      </td>
      <td style="padding:1px 3px;"><input type="date" name="ld_from[]" class="fi" value="<?= fv($l,'from_date') ?>"></td>
      <td style="padding:1px 3px;"><input type="date" name="ld_to[]"   class="fi" value="<?= fv($l,'to_date') ?>"></td>
      <td style="padding:1px 3px;"><input type="number" name="ld_hours[]" class="fi" value="<?= fv($l,'hours_count') ?>"></td>
      <td style="padding:1px 3px;">
        <select name="ld_type[]" class="fi">
          <?php foreach (['Managerial','Supervisory','Technical','Foundation'] as $t): ?>
            <option value="<?= $t ?>" <?= crc($l,'ld_type',$t) ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td style="padding:1px 3px;"><input type="text" name="ld_conducted[]" class="fi" value="<?= fv($l,'conducted_by') ?>"></td>
    </tr>
    <?php endfor; ?>
  </tbody>
  <tr>
    <td colspan="6" style="padding:2px 4px;">
      <button type="button" class="add-btn" onclick="addLD()">&#43; Add L&amp;D</button>
    </td>
  </tr>
</table>

<!-- ──── VIII. OTHER INFORMATION (31–33.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="3" class="sec-hdr">VIII. &nbsp;Other Information</td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:33.33%;">(31.) Special Skills and Hobbies</td>
    <td class="col-hdr" style="width:33.33%;">
      (32.) Non-Academic Distinctions/Recognition<br>
      <em style="font-weight:400;text-transform:none;">(Received from professional/civic/other organizations)</em>
    </td>
    <td class="col-hdr">
      (33.) Membership in Association/Organization<br>
      <em style="font-weight:400;text-transform:none;">(Write in full)</em>
    </td>
  </tr>
  <tr>
    <td style="padding:2px 3px; vertical-align:top;">
      <textarea name="special_skills" class="fi" rows="6"
                placeholder="e.g. Computer Programming&#10;Public Speaking"><?= fv($otherInfo,'special_skills') ?></textarea>
    </td>
    <td style="padding:2px 3px; vertical-align:top;">
      <textarea name="non_academic_distinctions" class="fi" rows="6"
                placeholder="e.g. Best Employee Award 2024"><?= fv($otherInfo,'non_academic_distinctions') ?></textarea>
    </td>
    <td style="padding:2px 3px; vertical-align:top;">
      <textarea name="org_memberships" class="fi" rows="6"
                placeholder="e.g. Philippine Computer Society"><?= fv($otherInfo,'org_memberships') ?></textarea>
    </td>
  </tr>
</table>

<div class="pg-sep">— Page 4 of 4 —</div>

<!-- ──── IX. BACKGROUND INFORMATION (34–40.) ──── -->
<?php
function bgRow($questions, $qk, $qtxt) {
  $ans = $questions[$qk] ?? 'No';
  $det = htmlspecialchars((string)($questions[$qk.'_details'] ?? ''), ENT_QUOTES, 'UTF-8');
  $dis = ($ans !== 'Yes') ? 'opacity:.3;pointer-events:none;' : '';
  echo '<tr>';
  echo '<td style="font-size:7.5pt;line-height:1.5;padding:3px 5px;">'.$qtxt.'</td>';
  echo '<td style="text-align:center;vertical-align:middle;width:14%;">';
  echo '<div class="rb" style="justify-content:center;flex-direction:column;gap:2px;">';
  echo '<label><input type="radio" name="'.$qk.'" value="Yes" '.($ans==='Yes'?'checked':'').' onchange="bgToggle(\''.addslashes($qk).'\',true)"> Yes</label>';
  echo '<label><input type="radio" name="'.$qk.'" value="No"  '.($ans!=='Yes'?'checked':'').' onchange="bgToggle(\''.addslashes($qk).'\',false)"> No</label>';
  echo '</div></td>';
  echo '<td id="bgd-'.$qk.'" style="'.$dis.';padding:2px 3px;vertical-align:top;">';
  echo '<textarea name="'.$qk.'_details" class="fi" rows="2" style="font-size:7pt;" placeholder="If YES, give details...">'.$det.'</textarea>';
  echo '</td></tr>';
}
?>
<table class="pf pf-join">
  <tr>
    <td colspan="3" class="sec-hdr">IX. &nbsp;Background Information</td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:62%;">Questions</td>
    <td class="col-hdr" style="width:14%;">Answer<br>(Yes/No)</td>
    <td class="col-hdr">If YES, give details</td>
  </tr>

  <?php bgRow($questions,'q34a',
    '34. a. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the Bureau or Department where you will be appointed, <b>within the third degree?</b>');
  bgRow($questions,'q34b',
    'b. within the <b>fourth degree</b> (for Local Government Unit &mdash; Career Employees)?');
  bgRow($questions,'q35a',
    '35. a. Have you ever been <b>found guilty of any administrative offense?</b>');
  ?>

  <!-- Q35b: special — has Date Filed + Status sub-fields -->
  <?php
  $ans35b = $questions['q35b'] ?? 'No';
  $det35b = htmlspecialchars((string)($questions['q35b_details'] ?? ''), ENT_QUOTES, 'UTF-8');
  $dis35b = ($ans35b !== 'Yes') ? 'opacity:.3;pointer-events:none;' : '';
  ?>
  <tr>
    <td style="font-size:7.5pt;line-height:1.5;padding:3px 5px;">
      b. Have you been <b>criminally charged</b> before any court?
    </td>
    <td style="text-align:center;vertical-align:middle;">
      <div class="rb" style="justify-content:center;flex-direction:column;gap:2px;">
        <label><input type="radio" name="q35b" value="Yes" <?= $ans35b==='Yes'?'checked':'' ?> onchange="bgToggle('q35b',true)"> Yes</label>
        <label><input type="radio" name="q35b" value="No"  <?= $ans35b!=='Yes'?'checked':'' ?> onchange="bgToggle('q35b',false)"> No</label>
      </div>
    </td>
    <td id="bgd-q35b" style="<?= $dis35b ?>; padding:2px 3px; vertical-align:top;">
      <div style="font-size:6.5pt; font-style:italic; margin-bottom:1px;">Date Filed:</div>
      <input type="text" name="q35b_date_filed" class="fi"
             style="border-bottom:1px solid #999;font-size:8pt;width:100%;margin-bottom:4px;"
             placeholder="mm/dd/yyyy"
             value="<?= htmlspecialchars((string)($questions['q35b_date_filed'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <div style="font-size:6.5pt; font-style:italic; margin-bottom:1px;">Status of Case/s:</div>
      <textarea name="q35b_details" class="fi" rows="2" style="font-size:7pt;"><?= $det35b ?></textarea>
    </td>
  </tr>

  <?php
  bgRow($questions,'q36',
    '36. Have you ever been <b>convicted</b> of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?');
  bgRow($questions,'q37',
    '37. Have you ever been <b>separated from the service</b> in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?');
  bgRow($questions,'q38a',
    '38. a. Have you ever been a <b>candidate in a national or local election</b> held within the last year (except Barangay election)?');
  bgRow($questions,'q38b',
    'b. Have you <b>resigned</b> from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?');
  bgRow($questions,'q39',
    '39. Have you acquired the status of an <b>immigrant or permanent resident</b> of another country?');
  ?>

  <!-- Q40 — 2025 restructure -->
  <tr>
    <td colspan="3" style="font-size:7.5pt;padding:3px 5px;background:#f5f5f5;line-height:1.5;">
      <b>40.</b> Pursuant to: (a) Indigenous People&rsquo;s Act (RA 8371); (b) Magna Carta for Disabled Persons
      (RA 7277, as amended); and (c) Expanded Solo Parents Welfare Act (RA 11861), please answer the following items:
    </td>
  </tr>

  <?php
  $q40b_ans = $questions['q40b'] ?? 'No';
  $q40b_det = htmlspecialchars((string)($questions['q40b_details'] ?? ''), ENT_QUOTES, 'UTF-8');
  $q40c_ans = $questions['q40c'] ?? 'No';
  $q40c_det = htmlspecialchars((string)($questions['q40c_details'] ?? ''), ENT_QUOTES, 'UTF-8');
  $q40a_ans = $questions['q40a'] ?? 'No';
  $q40a_det = htmlspecialchars((string)($questions['q40a_details'] ?? ''), ENT_QUOTES, 'UTF-8');
  ?>

  <!-- Q40a: member of indigenous group -->
  <tr>
    <td style="font-size:7.5pt;padding:3px 5px;">
      a. Are you a <b>member of any indigenous group?</b>
    </td>
    <td style="text-align:center;vertical-align:middle;">
      <div class="rb" style="justify-content:center;flex-direction:column;gap:2px;">
        <label><input type="radio" name="q40b" value="Yes" <?= $q40b_ans==='Yes'?'checked':'' ?> onchange="bgToggle('q40b_ip',true)"> Yes</label>
        <label><input type="radio" name="q40b" value="No"  <?= $q40b_ans!=='Yes'?'checked':'' ?> onchange="bgToggle('q40b_ip',false)"> No</label>
      </div>
    </td>
    <td id="bgd-q40b_ip" style="<?= $q40b_ans!=='Yes'?'opacity:.3;pointer-events:none;':'' ?>;padding:2px 3px;vertical-align:top;">
      <div style="font-size:6.5pt;font-style:italic;margin-bottom:1px;">If YES, please specify:</div>
      <input type="text" name="q40b_details" class="fi"
             style="border-bottom:1px solid #999;font-size:8pt;width:100%;"
             value="<?= $q40b_det ?>">
    </td>
  </tr>

  <!-- Q40b: person with disability -->
  <tr>
    <td style="font-size:7.5pt;padding:3px 5px;">
      b. Are you a <b>person with disability?</b>
    </td>
    <td style="text-align:center;vertical-align:middle;">
      <div class="rb" style="justify-content:center;flex-direction:column;gap:2px;">
        <label><input type="radio" name="q40c" value="Yes" <?= $q40c_ans==='Yes'?'checked':'' ?> onchange="bgToggle('q40c_pwd',true)"> Yes</label>
        <label><input type="radio" name="q40c" value="No"  <?= $q40c_ans!=='Yes'?'checked':'' ?> onchange="bgToggle('q40c_pwd',false)"> No</label>
      </div>
    </td>
    <td id="bgd-q40c_pwd" style="<?= $q40c_ans!=='Yes'?'opacity:.3;pointer-events:none;':'' ?>;padding:2px 3px;vertical-align:top;">
      <textarea name="q40c_details" class="fi" rows="2" style="font-size:7pt;"
                placeholder="If YES, give details..."><?= $q40c_det ?></textarea>
    </td>
  </tr>

  <!-- Q40c: solo parent -->
  <tr>
    <td style="font-size:7.5pt;padding:3px 5px;">
      c. Are you a <b>solo parent?</b>
    </td>
    <td style="text-align:center;vertical-align:middle;">
      <div class="rb" style="justify-content:center;flex-direction:column;gap:2px;">
        <label><input type="radio" name="q40a" value="Yes" <?= $q40a_ans==='Yes'?'checked':'' ?> onchange="bgToggle('q40a_solo',true)"> Yes</label>
        <label><input type="radio" name="q40a" value="No"  <?= $q40a_ans!=='Yes'?'checked':'' ?> onchange="bgToggle('q40a_solo',false)"> No</label>
      </div>
    </td>
    <td id="bgd-q40a_solo" style="<?= $q40a_ans!=='Yes'?'opacity:.3;pointer-events:none;':'' ?>;padding:2px 3px;vertical-align:top;">
      <textarea name="q40a_details" class="fi" rows="2" style="font-size:7pt;"
                placeholder="If YES, give details..."><?= $q40a_det ?></textarea>
    </td>
  </tr>
</table>

<!-- ──── X. CHARACTER REFERENCES (41.) ──── -->
<table class="pf pf-join">
  <tr>
    <td colspan="3" class="sec-hdr">X. &nbsp;Character References</td>
  </tr>
  <tr>
    <td colspan="3" style="font-size:6.8pt;padding:2px 5px;background:#f0f0f0;">
      (41.) (Persons not your relatives; not your superiors; known for at least one (1) year)
    </td>
  </tr>
  <tr>
    <td class="col-hdr" style="width:30%;">Name<br><em style="font-weight:400;text-transform:none;">(Write in full)</em></td>
    <td class="col-hdr" style="width:45%;">Office/Residential Address</td>
    <td class="col-hdr">Contact No. and/or Email</td>
  </tr>
  <?php foreach ($references as $r): ?>
  <tr>
    <td style="padding:2px 3px;"><input type="text" name="ref_name[]"    class="fi" value="<?= fv($r,'ref_name') ?>"></td>
    <td style="padding:2px 3px;"><input type="text" name="ref_address[]" class="fi" value="<?= fv($r,'ref_address') ?>"></td>
    <td style="padding:2px 3px;"><input type="text" name="ref_tel[]"     class="fi" value="<?= fv($r,'ref_tel') ?>"></td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- ──── XI. SIGNATURE / CERTIFICATION (42.) ──── -->
<table class="pf pf-join">
  <!-- Declaration text -->
  <tr>
    <td colspan="3" style="font-size:7pt;padding:5px 8px;line-height:1.6;background:#f9f9f9;">
      <b>42.</b> I declare under oath that I have personally accomplished this Personal Data Sheet which is a true,
      correct, and complete statement pursuant to the provisions of pertinent laws, rules, and regulations of the
      Republic of the Philippines. I authorize the <b>agency head/authorized representative</b> to
      verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its
      attachments shall cause the filing of administrative/criminal case/s against me.
    </td>
  </tr>

  <!-- Row: Govt ID | Signature area | Photo -->
  <tr>
    <!-- Government Issued ID section -->
    <td style="width:42%;padding:5px 7px;vertical-align:top;">
      <div style="font-size:6.5pt;font-weight:700;text-transform:uppercase;margin-bottom:3px;">
        Government Issued ID &mdash; Please indicate ID Number and Date of Issuance
      </div>
      <div style="margin-bottom:4px;">
        <span class="lbl">ID Type (e.g. Passport, Driver&rsquo;s License, SSS, UMID)</span>
        <input type="text" name="gov_id_type" class="fi"
               style="border-bottom:1px solid #999;font-size:8.5pt;"
               value="<?= fv($emp,'gov_id_type') ?>">
      </div>
      <div style="margin-bottom:4px;">
        <span class="lbl">ID No.</span>
        <input type="text" name="gov_id_number" class="fi"
               style="border-bottom:1px solid #999;font-size:8.5pt;"
               value="<?= fv($emp,'gov_id_number') ?>">
      </div>
      <div>
        <span class="lbl">Date / Place of Issuance</span>
        <input type="text" name="gov_id_issued" class="fi"
               style="border-bottom:1px solid #999;font-size:8.5pt;"
               value="<?= fv($emp,'gov_id_issued') ?>">
      </div>
    </td>

    <!-- Signature + Date + Thumbmark -->
    <td style="width:35%;padding:5px 8px;vertical-align:top;">
      <!-- Signature box -->
      <div style="border:1px solid #000;height:75px;display:flex;align-items:center;
                  justify-content:center;font-size:7pt;color:#777;margin-bottom:3px;">
        Sign inside the box
      </div>
      <!-- Printed name line -->
      <div style="border-top:1px solid #000;text-align:center;padding-top:2px;font-size:7pt;">
        <?= htmlspecialchars(
          strtoupper(trim(
            ($emp['last_name'] ?? '').', '.
            ($emp['first_name'] ?? '').' '.
            ($emp['middle_name'] ?? '')
          )),
        ENT_QUOTES, 'UTF-8') ?><br>
        <em style="font-size:6.5pt;">Signature over Printed Name of Employee</em>
      </div>
      <!-- Date accomplished -->
      <div style="margin-top:8px;border-top:1px solid #000;text-align:center;padding-top:2px;font-size:7pt;">
        <?= date('F j, Y') ?><br>
        <em style="font-size:6.5pt;">Date Accomplished</em>
      </div>
      <!-- Right Thumbmark -->
      <div class="photo-box" style="height:45px;width:70px;margin:6px auto 0;font-size:6pt;">
        RIGHT<br>THUMBMARK
      </div>
    </td>

    <!-- Photo box -->
    <td style="width:23%;padding:5px 8px;vertical-align:top;text-align:center;">
      <div class="photo-box" style="height:110px;width:90px;margin:0 auto 4px;font-size:7pt;">
        PHOTO<br>
        <em style="font-size:6pt;">(passport size<br>4.5cm x 3.5cm)</em>
      </div>
    </td>
  </tr>

  <!-- Notarization / Oath -->
  <tr>
    <td colspan="3" style="padding:5px 8px;font-size:7pt;line-height:1.8;background:#f5f5f5;">
      SUBSCRIBED AND SWORN to before me this&nbsp;
      <input type="text" name="oath_date" class="fi"
             style="display:inline;width:180px;border-bottom:1px solid #888;font-size:8pt;"
             placeholder="date, month, year"
             value="<?= fv($emp,'oath_date') ?>">,
      affiant exhibiting his/her validly issued government ID as indicated above.
      <br><br>
      <div style="width:220px;border-top:1px solid #000;padding-top:3px;text-align:center;margin-top:20px;font-size:7pt;">
        <em>(wet/e-signature/digital cert.)</em><br>
        <b>Person Administering Oath</b>
      </div>
    </td>
  </tr>

  <!-- Footer stamp -->
  <tr>
    <td colspan="3" style="text-align:right;padding:2px 8px;font-size:6.5pt;color:#555;background:#e8e8e8;letter-spacing:.3px;">
      CS FORM 212 (Revised 2025), Page 4 of 4
    </td>
  </tr>
</table>

</div><!-- /pds-paper -->

<!-- Bottom save bar -->
<div class="pds-bar no-print" style="margin-top:14px;">
  <button type="submit" name="action" value="save"
          class="btn-glass btn-glass-success" style="padding:9px 22px;font-size:.85rem;">
    <i class="fas fa-save me-2"></i>Save PDS
  </button>
  <button type="submit" name="action" value="submit"
          class="btn-glass" style="padding:9px 22px;font-size:.85rem;"
          onclick="return confirm('Submit PDS for review?')">
    <i class="fas fa-paper-plane me-2"></i>Submit PDS
  </button>
  <a href="<?= BASE_URL ?>/pds/print.php" target="_blank"
     class="btn-glass text-decoration-none" style="padding:9px 22px;font-size:.85rem;">
    <i class="fas fa-print me-2"></i>Print
  </a>
</div>
</form>
</div><!-- /pds-wrap -->

<script>
/* ── Permanent Address Toggle ── */
function togglePermAddr(cb) {
  ['permRow1','permRow2'].forEach(function(id){
    var r = document.getElementById(id);
    if (!r) return;
    r.style.opacity = cb.checked ? '.3' : '1';
    r.querySelectorAll('input').forEach(function(i){ i.disabled = cb.checked; });
  });
}
(function(){
  var cb = document.querySelector('input[name="permanent_same"]');
  if (cb && cb.checked) togglePermAddr(cb);
}());

/* ── Present checkbox ── */
function togglePresent(cb, endId) {
  var f = document.getElementById(endId);
  if (!f) return;
  f.disabled = cb.checked;
  if (cb.checked) f.value = '';
}

/* ── Background question toggle ── */
function bgToggle(key, show) {
  var el = document.getElementById('bgd-' + key);
  if (!el) return;
  el.style.opacity       = show ? '1' : '.3';
  el.style.pointerEvents = show ? '' : 'none';
}

/* ── Generic row delete ── */
function delRow(id) {
  var el = document.getElementById(id);
  if (el) {
    el.style.opacity = '0'; el.style.transition = 'opacity .12s';
    setTimeout(function(){ el.remove(); }, 130);
  }
}

/* ── Add Child ── */
var cIdx = <?= (int)count($children) ?>;
function addChild() {
  var tb = document.getElementById('childrenBody'), n = cIdx++, tr = document.createElement('tr');
  tr.id = 'child-row-' + n;
  tr.innerHTML =
    '<td colspan="8" style="padding:1px 3px;">' +
    '<button type="button" class="del-btn" onclick="delRow(\'child-row-'+n+'\')">&#10005;</button>' +
    '<input type="text" name="child_name[]" class="fi"></td>' +
    '<td colspan="4" style="padding:1px 3px;"><input type="date" name="child_dob[]" class="fi"></td>';
  tb.appendChild(tr);
}

/* ── Add Eligibility ── */
var eIdx = <?= (int)count($eligibility) ?>;
function addEligibility() {
  var tb = document.getElementById('eligBody'), n = eIdx++, tr = document.createElement('tr');
  tr.id = 'elig-row-' + n;
  tr.innerHTML =
    '<td style="padding:1px 3px;"><button type="button" class="del-btn" onclick="delRow(\'elig-row-'+n+'\')">&#10005;</button>' +
    '<input type="text" name="elig_career[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="elig_rating[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="elig_exam_date[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="elig_place[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="elig_license[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="elig_validity[]" class="fi"></td>';
  tb.appendChild(tr);
}

/* ── Add Work Experience ── */
var wIdx = <?= (int)count($workExp) ?>;
function addWork() {
  var tb = document.getElementById('workBody'), n = wIdx++, tr = document.createElement('tr');
  tr.id = 'work-row-' + n;
  tr.innerHTML =
    '<td style="padding:1px 3px;"><input type="date" name="work_start[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="work_end[]" class="fi" id="wend-'+n+'">' +
      '<label style="font-size:6.2pt;cursor:pointer;display:block;margin-top:1px;">' +
      '<input type="checkbox" name="work_present['+n+']" value="1" onchange="togglePresent(this,\'wend-'+n+'\')"> Present</label></td>' +
    '<td style="padding:1px 3px;"><button type="button" class="del-btn" onclick="delRow(\'work-row-'+n+'\')">&#10005;</button>' +
    '<input type="text" name="work_position[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="work_dept[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="work_appointment[]" class="fi"></td>' +
    '<td style="padding:1px 3px;text-align:center;"><select name="work_gov[]" class="fi" style="width:auto;">' +
    '<option value="Y">Y</option><option value="N">N</option></select></td>';
  tb.appendChild(tr);
}

/* ── Add Voluntary Work ── */
var vIdx = <?= (int)count($voluntaryWork) ?>;
function addVoluntary() {
  var tb = document.getElementById('voluntaryBody'), n = vIdx++, tr = document.createElement('tr');
  tr.id = 'vol-row-' + n;
  tr.innerHTML =
    '<td style="padding:1px 3px;"><button type="button" class="del-btn" onclick="delRow(\'vol-row-'+n+'\')">&#10005;</button>' +
    '<input type="text" name="vol_org[]" class="fi">' +
    '<input type="text" name="vol_address[]" class="fi" placeholder="(address)"' +
    ' style="font-size:7pt;border-top:1px dotted #bbb;margin-top:1px;padding-top:1px;"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="vol_from[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="vol_to[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="number" name="vol_hours[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="vol_position[]" class="fi"></td>';
  tb.appendChild(tr);
}

/* ── Add L&D ── */
var lIdx = <?= (int)count($ldRecords) ?>;
function addLD() {
  var tb = document.getElementById('ldBody'), n = lIdx++, tr = document.createElement('tr');
  tr.id = 'ld-row-' + n;
  tr.innerHTML =
    '<td style="padding:1px 3px;"><button type="button" class="del-btn" onclick="delRow(\'ld-row-'+n+'\')">&#10005;</button>' +
    '<input type="text" name="ld_title[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="ld_from[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="date" name="ld_to[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><input type="number" name="ld_hours[]" class="fi"></td>' +
    '<td style="padding:1px 3px;"><select name="ld_type[]" class="fi">' +
    '<option>Managerial</option><option>Supervisory</option><option>Technical</option><option>Foundation</option>' +
    '</select></td>' +
    '<td style="padding:1px 3px;"><input type="text" name="ld_conducted[]" class="fi"></td>';
  tb.appendChild(tr);
}
</script>

<?php require_once '../includes/footer.php'; ?>
