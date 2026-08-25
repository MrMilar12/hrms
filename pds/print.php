<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAuth();

if (isAdmin() && isset($_GET['emp_id'])) {
    $employeeId = (int)$_GET['emp_id'];
} else {
    $employeeId = (int)($_SESSION['employee_id'] ?? 0);
}
if (!$employeeId) { header('Location: ' . BASE_URL . '/pds/form.php'); exit; }

$s = $conn->prepare("SELECT e.*, u.email FROM employees e LEFT JOIN users u ON e.user_id=u.id WHERE e.id=?");
$s->bind_param('i', $employeeId); $s->execute();
$emp = $s->get_result()->fetch_assoc() ?? []; $s->close();

$s = $conn->prepare("SELECT * FROM family_background WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$fam = $s->get_result()->fetch_assoc() ?? []; $s->close();

$s = $conn->prepare("SELECT * FROM children WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$children = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$s = $conn->prepare("SELECT * FROM education WHERE employee_id=? ORDER BY FIELD(level,'Elementary','Secondary','Vocational','Vocational/Trade Course','College','Graduate Studies')");
$s->bind_param('i', $employeeId); $s->execute();
$education = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$s = $conn->prepare("SELECT * FROM eligibility WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$eligibility = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$s = $conn->prepare("SELECT * FROM work_experience WHERE employee_id=? ORDER BY start_date DESC");
$s->bind_param('i', $employeeId); $s->execute();
$workExp = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$s = $conn->prepare("SELECT * FROM voluntary_work WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$voluntaryWork = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$s = $conn->prepare("SELECT * FROM learning_development WHERE employee_id=? ORDER BY id");
$s->bind_param('i', $employeeId); $s->execute();
$ldRecords = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$s = $conn->prepare("SELECT * FROM other_info WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$otherInfo = $s->get_result()->fetch_assoc() ?? []; $s->close();

$s = $conn->prepare("SELECT * FROM pds_questions WHERE employee_id=?");
$s->bind_param('i', $employeeId); $s->execute();
$questions = $s->get_result()->fetch_assoc() ?? []; $s->close();

$s = $conn->prepare("SELECT * FROM references_info WHERE employee_id=? ORDER BY id LIMIT 3");
$s->bind_param('i', $employeeId); $s->execute();
$references = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

$eduMap = [];
foreach ($education as $e) { $eduMap[$e['level']] = $e; }
while (count($references) < 3) { $references[] = []; }

/* ── Helpers ─────────────────────────────────────────── */
function p($v)   { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function pf($v)  { return ($v && $v !== '0000-00-00') ? date('m/d/Y', strtotime($v)) : ''; }
/* nv: returns N/A when the value is empty (CSC requirement) */
function nv($v)  {
    $s = trim((string)($v ?? ''));
    return $s !== '' ? htmlspecialchars($s, ENT_QUOTES, 'UTF-8') : 'N/A';
}
function chk($val, $cmp) {
    return ((string)($val ?? '') === (string)$cmp) ? '&#9745;' : '&#9744;';
}

function bgPrintRow($questions, $qk, $qtxt) {
    $ans = $questions[$qk] ?? 'No';
    $det = trim((string)($questions[$qk . '_details'] ?? ''));
    $detHtml = $det !== '' ? htmlspecialchars($det, ENT_QUOTES, 'UTF-8') : 'N/A';
    echo '<tr>';
    echo '<td style="font-size:7pt;line-height:1.4;padding:2px 3px;">' . $qtxt . '</td>';
    echo '<td style="text-align:center;vertical-align:middle;white-space:nowrap;padding:2px;border-left:0.5pt solid #000;">';
    echo '<div style="display:flex;flex-direction:column;align-items:center;gap:0;font-size:7pt;">';
    echo '<span>' . ($ans === 'Yes' ? '&#9745;' : '&#9744;') . ' Yes</span>';
    echo '<span>' . ($ans !== 'Yes' ? '&#9745;' : '&#9744;') . ' No</span>';
    echo '</div></td>';
    echo '<td style="padding:2px 3px;vertical-align:top;font-size:7pt;border-left:0.5pt solid #000;">' . nl2br($detHtml) . '</td>';
    echo '</tr>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CS Form 212 (2025) &mdash; <?= p($emp['last_name']) ?>, <?= p($emp['first_name']) ?></title>
<style>
/* ── Force background colors to print ─────────────────────── */
* {
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
  color-adjust: exact !important;
  box-sizing: border-box;
  margin: 0; padding: 0;
}

/* ── Base ──────────────────────────────────────────────────── */
html, body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 7pt;
  color: #000;
  background: #aaa;
}

/* ── Print button (screen only) ────────────────────────────── */
.no-print {
  position: fixed; top: 12px; right: 16px; z-index: 9999;
  background: #1e40af; color: #fff; border: none;
  padding: 8px 20px; border-radius: 5px; cursor: pointer;
  font-size: 12px; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,.4);
}
.no-print:hover { background: #1e3a8a; }

/* ── Paper container (screen: simulates long bond with margins) */
.pds-paper {
  max-width: 7.5in;
  margin: 18px auto 40px;
  background: #fff;
  border: 1px solid #888;
  box-shadow: 0 3px 20px rgba(0,0,0,.55);
  padding: 0.45in 0.5in;
}

/* ── All tables ─────────────────────────────────────────────── */
.pf { width: 100%; border-collapse: collapse; table-layout: fixed; }
.pf td, .pf th {
  border: 0.5pt solid #000;
  padding: 1px 2px;
  vertical-align: top;
  word-wrap: break-word;
  overflow: hidden;
  font-size: 7pt;
}

/* Join adjacent section tables (remove double top-border) */
.pf-join { border-top: none; }
.pf-join > tbody > tr:first-child > td,
.pf-join > tbody > tr:first-child > th,
.pf-join > tr:first-child > td,
.pf-join > tr:first-child > th { border-top: none; }

/* ── Section header ─────────────────────────────────────────── */
.sec-hdr {
  background: #404040 !important; color: #fff !important;
  font-size: 7pt; font-weight: 700; text-transform: uppercase;
  letter-spacing: .3px; padding: 2px 4px !important;
}

/* ── Column header ──────────────────────────────────────────── */
.col-hdr {
  background: #c6c6c6 !important; font-weight: 700;
  font-size: 6pt; text-transform: uppercase;
  text-align: center; padding: 1px 2px !important; line-height: 1.2;
}

/* ── Tiny italic label above value ─────────────────────────── */
.lbl {
  font-size: 5.5pt; font-style: italic; color: #333;
  display: block; line-height: 1.1; text-transform: uppercase;
}

/* ── Field values ───────────────────────────────────────────── */
.fv      { display: block; font-size: 7.5pt; line-height: 1.2; }
.fv-name { display: block; font-size: 8.5pt; font-weight: 700; text-transform: uppercase; line-height: 1.15; }

/* ── Checkbox/Radio display ─────────────────────────────────── */
.opt-list { display: flex; flex-direction: column; gap: 0; font-size: 6.5pt; }
.opt-row  { display: flex; flex-wrap: wrap; gap: 0 5px; font-size: 6.5pt; }

/* ── Sub-headers within sections ───────────────────────────── */
.sub-hdr { background: #d8d8d8 !important; padding: 1px 4px !important; font-size: 6pt; font-weight: 700; text-transform: uppercase; }
.addr-band { background: #e0e0e0 !important; padding: 1px 4px !important; font-size: 6.5pt; font-weight: 700; }

/* ── Page separator (screen ornament, print page-break) ────── */
.pg-sep {
  display: block; margin: 0; padding: 2px 0;
  text-align: center; font-size: 6.5pt; color: #888;
  background: #efefef; border-top: 1.5px dashed #aaa;
}

/* ── Warning banner ─────────────────────────────────────────── */
.warn-bar {
  background: #fff9c4 !important; font-size: 6.5pt;
  line-height: 1.4; padding: 3px 8px;
  border-top: 0.5pt solid #000;
}

/* ── Letterhead ─────────────────────────────────────────────── */
.lh-title { font-size: 12pt; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; line-height: 1.1; }
.lh-sub   { font-size: 8pt; font-weight: 600; margin-top: 2px; }
.lh-code  { font-size: 7pt; color: #444; margin-top: 1px; }

/* ── Boxes (photo, thumbmark) ───────────────────────────────── */
.photo-box {
  border: 0.5pt solid #000;
  display: flex; align-items: center; justify-content: center;
  font-size: 6pt; text-align: center; color: #555; line-height: 1.3;
}

/* ── PRINT MEDIA ─────────────────────────────────────────────── */
@media print {
  .no-print { display: none !important; }
  html, body { background: #fff !important; font-size: 7pt !important; }

  .pds-paper {
    box-shadow: none !important; border: none !important;
    margin: 0 !important; max-width: 100% !important;
    padding: 0 !important;
  }

  /* Page separator becomes invisible + triggers page-break */
  .pg-sep {
    border: none !important; background: transparent !important;
    color: transparent !important; padding: 0 !important;
    height: 0 !important; overflow: hidden;
    page-break-after: always !important;
  }

  /* Avoid splitting rows across pages */
  .pf tr { page-break-inside: avoid; }

  @page {
    size: 8.5in 13in portrait;
    margin: 0.5in;
  }
}
</style>
</head>
<body>

<button class="no-print" onclick="window.print()">&#128438;&nbsp; Print PDS</button>

<div class="pds-paper">

<!-- ═══════════════ LETTERHEAD ════════════════════════════ -->
<table class="pf" style="border-bottom:1pt solid #000;">
  <colgroup><col style="width:75%"><col style="width:25%"></colgroup>
  <tr>
    <td style="border:none;border-right:0.5pt solid #000;padding:6px 16px 5px;text-align:center;">
      <div class="lh-title">Personal Data Sheet</div>
      <div class="lh-sub">Republic of the Philippines &mdash; Civil Service Commission</div>
      <div class="lh-code">CS Form No. 212 (Revised 2025)</div>
    </td>
    <td style="border:none;padding:5px 8px;vertical-align:middle;">
      <span class="lbl">Date Filed</span>
      <span class="fv"><?= nv(pf($emp['date_filed'] ?? '')) ?></span>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="warn-bar">
      <b>WARNING:</b> Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause
      the filing of administrative/criminal case/s against the person concerned.<br>
      <b>READ THE ATTACHED GUIDE TO FILLING OUT THE PDS BEFORE ACCOMPLISHING THE PDS FORM.</b>
      DO NOT ABBREVIATE. Print legibly. Tick appropriate boxes (&#10003;). Indicate N/A if not applicable.
    </td>
  </tr>
</table>

<!-- ═══════════════ I. PERSONAL INFORMATION — 10 data cols + photo ═════ -->
<table class="pf pf-join">
  <!--
    11 physical columns:
    · Cols 1–5  = left side (name fields): each ~8.5% = 42.5% total
    · Cols 6–10 = right side (birth/other): each ~8.5% = 42.5% total
    · Col  11   = photo box: 15%
    Rows 1-4 fill only cols 1-10 (photo rowspan covers col 11).
    Rows 5+  fill all 11 cols.
  -->
  <colgroup>
    <col style="width:8.5%"><col style="width:8.5%"><col style="width:8.5%">
    <col style="width:8.5%"><col style="width:8.5%">  <!-- cols 1-5: left 42.5% -->
    <col style="width:8.5%"><col style="width:8.5%"><col style="width:8.5%">
    <col style="width:8.5%"><col style="width:8.5%">  <!-- cols 6-10: right 42.5% -->
    <col style="width:15%">                            <!-- col 11: photo -->
  </colgroup>
  <tr><td colspan="11" class="sec-hdr">I. &nbsp;Personal Information</td></tr>

  <!-- Row 1: (1) Surname [1–5] | (4) Date of Birth [6–10] | Photo [11, rowspan=4] -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000;padding:1px 3px;">
      <span class="lbl">(1.) Surname</span>
      <span class="fv-name"><?= nv($emp['last_name']) ?></span>
    </td>
    <td colspan="5" style="padding:1px 3px;">
      <span class="lbl">(4.) Date of Birth &nbsp;<em style="font-style:normal;">(mm/dd/yyyy)</em></span>
      <span class="fv"><?= nv(pf($emp['birthdate'])) ?></span>
    </td>
    <td rowspan="4" style="text-align:center;vertical-align:middle;padding:6px 4px;
                           font-size:6pt;color:#777;line-height:1.5;border-left:0.5pt solid #000;">
      ID PICTURE<br>
      <em style="font-size:5pt;">(passport size<br>4.5cm &times; 3.5cm)</em>
    </td>
  </tr>

  <!-- Row 2: (2) First Name + Ext [1–5] | (5) Place of Birth [6–10] -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000;padding:1px 3px;">
      <span class="lbl">(2.) First Name
        <span style="float:right;font-size:5pt;">Name Extension (Jr., Sr., III)</span>
      </span>
      <div style="display:flex;align-items:baseline;gap:3px;">
        <span class="fv-name" style="flex:1;"><?= nv($emp['first_name']) ?></span>
        <span class="fv" style="width:52px;border-left:0.5pt solid #bbb;padding-left:2px;font-size:7.5pt;">
          <?= p($emp['name_extension'] ?? '') ?: '&nbsp;' ?>
        </span>
      </div>
    </td>
    <td colspan="5" style="padding:1px 3px;">
      <span class="lbl">(5.) Place of Birth</span>
      <span class="fv"><?= nv($emp['place_of_birth']) ?></span>
    </td>
  </tr>

  <!-- Row 3: (3) Middle Name [1–5] | (6) Sex [6–7] | (7) Civil Status [8–10] -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000;padding:1px 3px;">
      <span class="lbl">(3.) Middle Name</span>
      <span class="fv-name"><?= nv($emp['middle_name']) ?></span>
    </td>
    <td colspan="2" style="padding:1px 3px;border-right:0.5pt solid #000;">
      <span class="lbl">(6.) Sex at Birth</span>
      <div class="opt-list">
        <span><?= chk($emp['sex']??'','Male') ?> Male</span>
        <span><?= chk($emp['sex']??'','Female') ?> Female</span>
      </div>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">(7.) Civil Status</span>
      <div class="opt-list">
        <span><?= chk($emp['civil_status']??'','Single') ?> Single</span>
        <span><?= chk($emp['civil_status']??'','Married') ?> Married</span>
        <span><?= chk($emp['civil_status']??'','Widow/er') ?> Widow/er</span>
        <span><?= chk($emp['civil_status']??'','Separated') ?> Separated</span>
        <span><?= chk($emp['civil_status']??'','Solo Parent') ?> Solo Parent</span>
        <span><?= chk($emp['civil_status']??'','Others') ?> Others</span>
      </div>
    </td>
  </tr>

  <!-- Row 4: (8) Height [1] | (9) Weight [2] | (10) Blood Type [3] | (16) Citizenship [4–10] -->
  <tr>
    <td style="padding:1px 3px;">
      <span class="lbl">(8.) Height (m)</span>
      <span class="fv"><?= nv($emp['height']) ?></span>
    </td>
    <td style="padding:1px 3px;">
      <span class="lbl">(9.) Weight (kg)</span>
      <span class="fv"><?= nv($emp['weight']) ?></span>
    </td>
    <td style="padding:1px 3px;border-right:0.5pt solid #000;">
      <span class="lbl">(10.) Blood Type</span>
      <span class="fv"><?= nv($emp['blood_type']) ?></span>
    </td>
    <td colspan="7" style="padding:2px 3px;">
      <span class="lbl">(16.) Citizenship</span>
      <div style="font-size:7pt;">
        <?= chk(($emp['citizenship']??'Filipino')==='Dual'?'Dual':'Filipino','Filipino') ?>
        <b>Filipino</b>&nbsp;&nbsp;
        <?= chk($emp['citizenship']??'','Dual') ?> Dual Citizenship
      </div>
      <div style="font-size:5.5pt;margin-top:1px;">
        If dual:
        <?= chk($emp['dual_citizenship_type']??'','By birth') ?> By birth &nbsp;
        <?= chk($emp['dual_citizenship_type']??'','By naturalization') ?> By naturalization &nbsp;
        Country: <?= !empty($emp['dual_country']) ? '<b>'.p($emp['dual_country']).'</b>' : '___________' ?>
      </div>
    </td>
  </tr>

  <!-- Rows 5+: all 11 cols (photo rowspan ends after row 4) -->

  <!-- (10) UMID [1–3] | (11) Pag-IBIG [4–6] | (12) PhilHealth [7–8] | (13) PhilSys [9–11] -->
  <tr>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">(10.) UMID/GSIS ID No.</span>
      <span class="fv"><?= nv($emp['gsis']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">(11.) Pag-IBIG ID No.</span>
      <span class="fv"><?= nv($emp['pagibig']) ?></span>
    </td>
    <td colspan="2" style="padding:1px 3px;">
      <span class="lbl">(12.) PhilHealth No.</span>
      <span class="fv"><?= nv($emp['philhealth']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">(13.) PhilSys No. (PSN)</span>
      <span class="fv"><?= nv($emp['philsys_psn']) ?></span>
    </td>
  </tr>

  <!-- (14) TIN [1–5] | (15) Agency Employee No. [6–11] -->
  <tr>
    <td colspan="5" style="border-right:2px solid #000;padding:1px 3px;">
      <span class="lbl">(14.) TIN No.</span>
      <span class="fv"><?= nv($emp['tin']) ?></span>
    </td>
    <td colspan="6" style="padding:1px 3px;">
      <span class="lbl">(15.) Agency Employee No.</span>
      <span class="fv"><?= nv($emp['agency_employee_no']) ?></span>
    </td>
  </tr>

  <!-- Residential Address -->
  <tr><td colspan="11" class="addr-band">(17.) Residential Address</td></tr>
  <tr>
    <td colspan="2" style="padding:1px 3px;">
      <span class="lbl">House/Block/Lot No.</span>
      <span class="fv"><?= nv($emp['residential_house']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">Street</span>
      <span class="fv"><?= nv($emp['residential_street']) ?></span>
    </td>
    <td colspan="6" style="padding:1px 3px;">
      <span class="lbl">Subdivision/Village</span>
      <span class="fv"><?= nv($emp['residential_subdivision']) ?></span>
    </td>
  </tr>
  <tr>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">Barangay</span>
      <span class="fv"><?= nv($emp['residential_barangay']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">City/Municipality</span>
      <span class="fv"><?= nv($emp['residential_city']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">Province</span>
      <span class="fv"><?= nv($emp['residential_province']) ?></span>
    </td>
    <td colspan="2" style="padding:1px 3px;">
      <span class="lbl">ZIP Code</span>
      <span class="fv"><?= nv($emp['residential_zip']) ?></span>
    </td>
  </tr>

  <!-- Permanent Address -->
  <tr><td colspan="11" class="addr-band">(18.) Permanent Address</td></tr>
  <?php if (!empty($emp['permanent_same'])): ?>
  <tr>
    <td colspan="11" style="padding:1px 3px;font-style:italic;font-size:7pt;">Same as Residential Address</td>
  </tr>
  <?php else: ?>
  <tr>
    <td colspan="2" style="padding:1px 3px;">
      <span class="lbl">House/Block/Lot No.</span>
      <span class="fv"><?= nv($emp['permanent_house']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">Street</span>
      <span class="fv"><?= nv($emp['permanent_street']) ?></span>
    </td>
    <td colspan="6" style="padding:1px 3px;">
      <span class="lbl">Subdivision/Village</span>
      <span class="fv"><?= nv($emp['permanent_subdivision']) ?></span>
    </td>
  </tr>
  <tr>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">Barangay</span>
      <span class="fv"><?= nv($emp['permanent_barangay']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">City/Municipality</span>
      <span class="fv"><?= nv($emp['permanent_city']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">Province</span>
      <span class="fv"><?= nv($emp['permanent_province']) ?></span>
    </td>
    <td colspan="2" style="padding:1px 3px;">
      <span class="lbl">ZIP Code</span>
      <span class="fv"><?= nv($emp['permanent_zip']) ?></span>
    </td>
  </tr>
  <?php endif; ?>

  <!-- (19) Telephone | (20) Mobile | (21) Email -->
  <tr>
    <td colspan="2" style="padding:1px 3px;">
      <span class="lbl">(19.) Telephone No.</span>
      <span class="fv"><?= nv($emp['telephone']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 3px;">
      <span class="lbl">(20.) Mobile No.</span>
      <span class="fv"><?= nv($emp['mobile']) ?></span>
    </td>
    <td colspan="6" style="padding:1px 3px;">
      <span class="lbl">(21.) E-Mail Address (if any)</span>
      <span class="fv"><?= nv($emp['email_address'] ?: ($emp['email'] ?? '')) ?></span>
    </td>
  </tr>
</table>

<!-- ═══════════════ II. FAMILY BACKGROUND — 12-col ════════ -->
<table class="pf pf-join">
  <colgroup>
    <col style="width:8%"><col style="width:8.5%"><col style="width:9%">
    <col style="width:9%"><col style="width:9%"><col style="width:8%">
    <col style="width:7%"><col style="width:7.5%"><col style="width:8.5%">
    <col style="width:8.5%"><col style="width:8.5%"><col style="width:9.5%">
  </colgroup>
  <tr><td colspan="12" class="sec-hdr">II. &nbsp;Family Background</td></tr>

  <!-- Spouse (22) -->
  <tr><td colspan="12" class="sub-hdr">(22.) Spouse&rsquo;s Name &nbsp;<em style="font-weight:400;text-transform:none;">(if married)</em></td></tr>
  <tr>
    <td colspan="3" style="padding:1px 2px;">
      <span class="lbl">Surname</span>
      <span class="fv" style="text-transform:uppercase;"><?= nv($fam['spouse_surname']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 2px;">
      <span class="lbl">First Name</span>
      <span class="fv"><?= nv($fam['spouse_firstname']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 2px;">
      <span class="lbl">Middle Name</span>
      <span class="fv"><?= nv($fam['spouse_middlename']) ?></span>
    </td>
    <td style="padding:1px 2px;">
      <span class="lbl">Ext.</span>
      <span class="fv"><?= p($fam['spouse_extension'] ?? '') ?: '&nbsp;' ?></span>
    </td>
    <td colspan="2" style="padding:1px 2px;">
      <span class="lbl">Telephone No.</span>
      <span class="fv"><?= nv($fam['spouse_telephone']) ?></span>
    </td>
  </tr>
  <tr>
    <td colspan="4" style="padding:1px 2px;">
      <span class="lbl">Occupation/Nature of Work</span>
      <span class="fv"><?= nv($fam['spouse_occupation']) ?></span>
    </td>
    <td colspan="4" style="padding:1px 2px;">
      <span class="lbl">Employer/Business Name</span>
      <span class="fv"><?= nv($fam['spouse_employer']) ?></span>
    </td>
    <td colspan="4" style="padding:1px 2px;">
      <span class="lbl">Business Address</span>
      <span class="fv"><?= nv($fam['spouse_business_address']) ?></span>
    </td>
  </tr>

  <!-- Father (24) -->
  <tr><td colspan="12" class="sub-hdr">(24.) Father&rsquo;s Full Name</td></tr>
  <tr>
    <td colspan="3" style="padding:1px 2px;">
      <span class="lbl">Surname</span>
      <span class="fv" style="text-transform:uppercase;"><?= nv($fam['father_surname']) ?></span>
    </td>
    <td colspan="4" style="padding:1px 2px;">
      <span class="lbl">First Name</span>
      <span class="fv"><?= nv($fam['father_firstname']) ?></span>
    </td>
    <td colspan="3" style="padding:1px 2px;">
      <span class="lbl">Middle Name</span>
      <span class="fv"><?= nv($fam['father_middlename']) ?></span>
    </td>
    <td colspan="2" style="padding:1px 2px;">
      <span class="lbl">Extension (Jr., Sr., III)</span>
      <span class="fv"><?= p($fam['father_extension'] ?? '') ?: '&nbsp;' ?></span>
    </td>
  </tr>

  <!-- Mother (25) -->
  <tr><td colspan="12" class="sub-hdr">(25.) Mother&rsquo;s Maiden Name</td></tr>
  <tr>
    <td colspan="3" style="padding:1px 2px;">
      <span class="lbl">Surname</span>
      <span class="fv" style="text-transform:uppercase;"><?= nv($fam['mother_surname']) ?></span>
    </td>
    <td colspan="4" style="padding:1px 2px;">
      <span class="lbl">First Name</span>
      <span class="fv"><?= nv($fam['mother_firstname']) ?></span>
    </td>
    <td colspan="5" style="padding:1px 2px;">
      <span class="lbl">Middle Name</span>
      <span class="fv"><?= nv($fam['mother_middlename']) ?></span>
    </td>
  </tr>

  <!-- Children (23) -->
  <tr>
    <td colspan="8" class="col-hdr">(23.) Name of Children (Write Full Name and List All)</td>
    <td colspan="4" class="col-hdr">Date of Birth<br><em style="text-transform:none;font-weight:400;">(mm/dd/yyyy)</em></td>
  </tr>
  <?php
  $showChildRows = max(count($children), 6);
  for ($i = 0; $i < $showChildRows; $i++):
    $c     = $children[$i] ?? [];
    $cname = trim((string)($c['child_name'] ?? ''));
    $cdob  = pf($c['date_of_birth'] ?? '');
  ?>
  <tr style="height:16px;">
    <td colspan="8" style="padding:1px 2px;"><span class="fv"><?= $cname !== '' ? p($cname) : '&nbsp;' ?></span></td>
    <td colspan="4" style="padding:1px 2px;"><span class="fv"><?= $cdob  !== '' ? p($cdob)  : '&nbsp;' ?></span></td>
  </tr>
  <?php endfor; ?>
</table>

<div class="pg-sep">— Page 1 of 4 —</div>

<!-- ═══════════════ III. EDUCATION — 6-col (matching form.php) ════════════ -->
<table class="pf pf-join">
  <!-- 6 columns: Level | School | Period From | Period To | Units | Honors -->
  <colgroup>
    <col style="width:13%"><col style="width:28%">
    <col style="width:9%"><col style="width:9%">
    <col style="width:14%"><col style="width:27%">
  </colgroup>
  <tr><td colspan="6" class="sec-hdr">III. &nbsp;Educational Background</td></tr>
  <!-- 2-row column header — Period of Attendance spans From + To -->
  <tr>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">(26.) Level</td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">Name of School<br>(Write in Full)</td>
    <td class="col-hdr" colspan="2" style="text-align:center;">Period of Attendance</td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">Highest Level/<br>Units Earned<br><em style="font-weight:400;text-transform:none;">(if not graduated)</em></td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">Scholarship / Academic<br>Honors Received</td>
  </tr>
  <tr>
    <td class="col-hdr">From</td>
    <td class="col-hdr">To</td>
  </tr>
  <?php
  $eduLevels2025 = ['Elementary','Secondary','Vocational/Trade Course','College','Graduate Studies'];
  foreach ($eduLevels2025 as $lvl):
    $mapKey = ($lvl === 'Vocational/Trade Course') ? 'Vocational' : $lvl;
    $e = $eduMap[$mapKey] ?? $eduMap[$lvl] ?? [];
  ?>
  <tr style="height:17px;">
    <td style="font-size:7pt;font-weight:700;padding:1px 3px;vertical-align:middle;"><?= p($lvl) ?></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($e['school'] ?? '') ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($e['from_year'] ?? '') ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($e['to_year'] ?? '') ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($e['units_earned'] ?? '') ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($e['honors'] ?? '') ?></span></td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- ═══════════════ IV. CIVIL SERVICE ELIGIBILITY — 6-col ══ -->
<table class="pf pf-join">
  <!-- 6 columns matching form.php: Career Service | Rating | Date | Place | License No | Valid Until -->
  <colgroup>
    <col style="width:33%"><col style="width:8%"><col style="width:13%">
    <col style="width:24%"><col style="width:13%"><col style="width:9%">
  </colgroup>
  <tr><td colspan="6" class="sec-hdr">IV. &nbsp;Civil Service Eligibility</td></tr>
  <tr>
    <td class="col-hdr">
      (27.) Career Service/ RA 1080 (Board/Bar) Under Special Laws/<br>
      CES/CSEE/Category II/IV Eligibility and Eligibilities<br>for Uniformed Personnel
    </td>
    <td class="col-hdr">Rating<br><em style="font-weight:400;text-transform:none;">(if applicable)</em></td>
    <td class="col-hdr">Date of Examination/<br>Conferment</td>
    <td class="col-hdr">Place of Examination/<br>Conferment</td>
    <td class="col-hdr">License No.<br><em style="font-weight:400;text-transform:none;">(if applicable)</em></td>
    <td class="col-hdr">Valid<br>Until</td>
  </tr>
  <?php
  $showElig = max(count($eligibility), 6);
  for ($i = 0; $i < $showElig; $i++):
    $el      = $eligibility[$i] ?? [];
    $hasData = !empty($el['career_service']);
  ?>
  <tr style="height:16px;">
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? p($el['career_service']) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;text-align:center;"><span class="fv"><?= $hasData ? nv($el['rating'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv(pf($el['date_of_exam'] ?? '')) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv($el['place_of_exam'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv($el['license_no'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv(pf($el['license_validity'] ?? '')) : '&nbsp;' ?></span></td>
  </tr>
  <?php endfor; ?>
</table>

<div class="pg-sep">— Page 2 of 4 —</div>

<!-- ═══════════════ V. WORK EXPERIENCE — 6-col ════════════ -->
<table class="pf pf-join">
  <!-- 6 columns matching CSC Form 212 (2025): From | To | Position | Dept/Agency | Status | Gov't -->
  <colgroup>
    <col style="width:10%"><col style="width:10%"><col style="width:24%">
    <col style="width:32%"><col style="width:16%"><col style="width:8%">
  </colgroup>
  <tr><td colspan="6" class="sec-hdr">V. &nbsp;Work Experience</td></tr>
  <tr>
    <td colspan="6" style="font-size:6pt;background:#f0f0f0!important;padding:1px 4px;">
      (28.) (Include private employment. Start from your recent work. Description of duties should be indicated in the attached Work Experience Sheet.)
    </td>
  </tr>
  <tr>
    <td class="col-hdr">Inclusive Dates<br>From<br><em style="font-weight:400;text-transform:none;">(mm/dd/yyyy)</em></td>
    <td class="col-hdr">Inclusive Dates<br>To<br><em style="font-weight:400;text-transform:none;">(mm/dd/yyyy)</em></td>
    <td class="col-hdr">Position Title<br><em style="font-weight:400;text-transform:none;">(Write in full/Do not abbreviate)</em></td>
    <td class="col-hdr">Department / Agency / Office / Company<br><em style="font-weight:400;text-transform:none;">(Write in full/Do not abbreviate)</em></td>
    <td class="col-hdr">Status of<br>Appointment</td>
    <td class="col-hdr">Gov&rsquo;t<br>Service<br>(Y/N)</td>
  </tr>
  <?php
  $showWork = max(count($workExp), 14);
  for ($i = 0; $i < $showWork; $i++):
    $w       = $workExp[$i] ?? [];
    $hasData = !empty($w['position_title']);
    $toDate  = !empty($w['is_present']) ? 'Present' : pf($w['end_date'] ?? '');
  ?>
  <tr style="height:16px;">
    <td style="padding:1px 2px;font-size:6.5pt;"><span class="fv"><?= $hasData ? nv(pf($w['start_date'] ?? '')) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;font-size:6.5pt;"><span class="fv"><?= $hasData ? ($toDate !== '' ? p($toDate) : 'N/A') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? p($w['position_title']) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? p($w['department'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv($w['status_appointment'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;text-align:center;"><span class="fv"><?= $hasData ? nv($w['is_government'] ?? '') : '&nbsp;' ?></span></td>
  </tr>
  <?php endfor; ?>
</table>

<div class="pg-sep">— Page 3 of 4 —</div>

<!-- ═══════════════ VI. VOLUNTARY WORK — 5-col ════════════ -->
<table class="pf pf-join">
  <!-- 5 columns matching form.php: Name+Address | From | To | Hours | Position -->
  <colgroup>
    <col style="width:34%"><col style="width:10%">
    <col style="width:10%"><col style="width:8%"><col style="width:38%">
  </colgroup>
  <tr>
    <td colspan="5" class="sec-hdr">VI. &nbsp;Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organization/s</td>
  </tr>
  <tr>
    <td class="col-hdr">(29.) Name &amp; Address of Organization<br><em style="font-weight:400;text-transform:none;">(Write in full)</em></td>
    <td class="col-hdr">Inclusive Dates<br>From</td>
    <td class="col-hdr">Inclusive Dates<br>To</td>
    <td class="col-hdr">No. of<br>Hours</td>
    <td class="col-hdr">Position / Nature of Work</td>
  </tr>
  <?php
  $showVol = max(count($voluntaryWork), 7);
  for ($i = 0; $i < $showVol; $i++):
    $v       = $voluntaryWork[$i] ?? [];
    $hasData = !empty($v['organization']);
    $orgAddr = trim(p($v['organization'] ?? ''));
    if ($hasData && !empty($v['org_address'])) $orgAddr .= '<br><em style="font-size:6pt;">'. p($v['org_address']) .'</em>';
  ?>
  <tr style="height:18px;">
    <td style="padding:1px 2px;"><?= $hasData ? $orgAddr : '&nbsp;' ?></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv(pf($v['from_date'] ?? '')) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv(pf($v['to_date'] ?? '')) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;text-align:center;"><span class="fv"><?= $hasData ? nv($v['hours_count'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv($v['position_nature'] ?? '') : '&nbsp;' ?></span></td>
  </tr>
  <?php endfor; ?>
</table>

<!-- ═══════════════ VII. LEARNING & DEVELOPMENT — 7-col ══ -->
<table class="pf pf-join">
  <!--
    7 columns: Title | [Inclusive Dates super-header: From | To] | Hours | Type of LD | Conducted By
    The "Inclusive Dates" super-header + its 2 sub-cols = 3 logical entries counted by user as 7
  -->
  <colgroup>
    <col style="width:31%"><col style="width:8%"><col style="width:8%">
    <col style="width:7%"><col style="width:13%"><col style="width:33%">
  </colgroup>
  <tr>
    <td colspan="6" class="sec-hdr">VII. &nbsp;Learning and Development (L&amp;D) Interventions / Training Programs Attended</td>
  </tr>
  <tr>
    <td colspan="6" style="font-size:6pt;background:#f0f0f0!important;padding:1px 4px;">(Start from the most recent L&amp;D/Training Program)</td>
  </tr>
  <!-- 2-row header: Inclusive Dates spans From + To cols -->
  <tr>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">
      (30.) Title of Learning and Development Interventions / Training Programs<br>(Write in full)
    </td>
    <td class="col-hdr" colspan="2">Inclusive Dates</td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">No. of<br>Hours</td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">
      Type of L&amp;D<br>
      <em style="font-weight:400;text-transform:none;font-size:5pt;">(Managerial/Supervisory/<br>Technical/Foundation)</em>
    </td>
    <td class="col-hdr" rowspan="2" style="vertical-align:middle;">Conducted / Sponsored By<br>(Write in full)</td>
  </tr>
  <tr>
    <td class="col-hdr">From</td>
    <td class="col-hdr">To</td>
  </tr>
  <?php
  $showLd = max(count($ldRecords), 7);
  for ($i = 0; $i < $showLd; $i++):
    $l       = $ldRecords[$i] ?? [];
    $hasData = !empty($l['title']);
  ?>
  <tr style="height:16px;">
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? p($l['title']) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv(pf($l['from_date'] ?? '')) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv(pf($l['to_date'] ?? '')) : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;text-align:center;"><span class="fv"><?= $hasData ? nv($l['hours_count'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv($l['ld_type'] ?? '') : '&nbsp;' ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= $hasData ? nv($l['conducted_by'] ?? '') : '&nbsp;' ?></span></td>
  </tr>
  <?php endfor; ?>
</table>

<!-- ═══════════════ VIII. OTHER INFORMATION — 3-col ════════ -->
<table class="pf pf-join">
  <colgroup>
    <col style="width:33.33%"><col style="width:33.33%"><col style="width:33.34%">
  </colgroup>
  <tr><td colspan="3" class="sec-hdr">VIII. &nbsp;Other Information</td></tr>
  <tr>
    <td class="col-hdr">(31.) Special Skills and Hobbies</td>
    <td class="col-hdr">(32.) Non-Academic Distinctions/Recognition<br><em style="font-weight:400;text-transform:none;">(Received from professional/civic/other organizations)</em></td>
    <td class="col-hdr">(33.) Membership in Association/Organization<br><em style="font-weight:400;text-transform:none;">(Write in full)</em></td>
  </tr>
  <tr>
    <td style="padding:2px 3px;vertical-align:top;min-height:60px;">
      <span style="font-size:7pt;white-space:pre-wrap;"><?= nv($otherInfo['special_skills'] ?? '') ?></span>
    </td>
    <td style="padding:2px 3px;vertical-align:top;">
      <span style="font-size:7pt;white-space:pre-wrap;"><?= nv($otherInfo['non_academic_distinctions'] ?? '') ?></span>
    </td>
    <td style="padding:2px 3px;vertical-align:top;">
      <span style="font-size:7pt;white-space:pre-wrap;"><?= nv($otherInfo['org_memberships'] ?? '') ?></span>
    </td>
  </tr>
</table>

<div class="pg-sep">— Page 4 of 4 —</div>

<!-- ═══════════════ IX. BACKGROUND INFORMATION (34–40.) ══ -->
<table class="pf pf-join">
  <colgroup>
    <col style="width:63%"><col style="width:14%"><col style="width:23%">
  </colgroup>
  <tr><td colspan="3" class="sec-hdr">IX. &nbsp;Background Information</td></tr>
  <tr>
    <td class="col-hdr">Questions</td>
    <td class="col-hdr">Answer<br>(Yes / No)</td>
    <td class="col-hdr">If YES, give details</td>
  </tr>

  <?php
  bgPrintRow($questions, 'q34a',
    '34. a. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the Bureau or Department where you will be appointed, <b>within the third degree?</b>');
  bgPrintRow($questions, 'q34b',
    'b. within the <b>fourth degree</b> (for Local Government Unit &mdash; Career Employees)?');
  bgPrintRow($questions, 'q35a',
    '35. a. Have you ever been <b>found guilty of any administrative offense?</b>');
  ?>

  <!-- Q35b — special: Date Filed + Status -->
  <?php
  $ans35b = $questions['q35b'] ?? 'No';
  $det35b = trim((string)($questions['q35b_details'] ?? ''));
  $df35b  = trim((string)($questions['q35b_date_filed'] ?? ''));
  ?>
  <tr>
    <td style="font-size:7pt;line-height:1.4;padding:2px 3px;">
      b. Have you been <b>criminally charged</b> before any court?
    </td>
    <td style="text-align:center;vertical-align:middle;white-space:nowrap;padding:2px;border-left:0.5pt solid #000;">
      <div style="display:flex;flex-direction:column;align-items:center;gap:0;font-size:7pt;">
        <span><?= $ans35b === 'Yes' ? '&#9745;' : '&#9744;' ?> Yes</span>
        <span><?= $ans35b !== 'Yes' ? '&#9745;' : '&#9744;' ?> No</span>
      </div>
    </td>
    <td style="padding:2px 3px;vertical-align:top;font-size:7pt;border-left:0.5pt solid #000;">
      <?php if ($ans35b === 'Yes'): ?>
        <div><em style="font-size:5.5pt;">Date Filed:</em> <?= $df35b !== '' ? p($df35b) : 'N/A' ?></div>
        <div style="margin-top:1px;"><em style="font-size:5.5pt;">Status:</em> <?= $det35b !== '' ? htmlspecialchars($det35b, ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
      <?php else: ?>N/A<?php endif; ?>
    </td>
  </tr>

  <?php
  bgPrintRow($questions, 'q36',
    '36. Have you ever been <b>convicted</b> of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?');
  bgPrintRow($questions, 'q37',
    '37. Have you ever been <b>separated from the service</b> in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?');
  bgPrintRow($questions, 'q38a',
    '38. a. Have you ever been a <b>candidate in a national or local election</b> held within the last year (except Barangay election)?');
  bgPrintRow($questions, 'q38b',
    'b. Have you <b>resigned</b> from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?');
  bgPrintRow($questions, 'q39',
    '39. Have you acquired the status of an <b>immigrant or permanent resident</b> of another country?');
  ?>

  <!-- Q40 preamble -->
  <tr>
    <td colspan="3" style="font-size:7pt;padding:2px 4px;background:#f5f5f5!important;line-height:1.4;">
      <b>40.</b> Pursuant to: (a) Indigenous People&rsquo;s Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277, as amended); and (c) Expanded Solo Parents Welfare Act (RA 11861), please answer the following items:
    </td>
  </tr>

  <?php
  // Q40a — indigenous group (stored in q40b)
  $q40b_ans = $questions['q40b'] ?? 'No';
  $q40b_det = trim((string)($questions['q40b_details'] ?? ''));
  ?>
  <tr>
    <td style="font-size:7pt;padding:2px 3px;">a. Are you a <b>member of any indigenous group?</b></td>
    <td style="text-align:center;vertical-align:middle;white-space:nowrap;padding:2px;border-left:0.5pt solid #000;">
      <div style="display:flex;flex-direction:column;align-items:center;gap:0;font-size:7pt;">
        <span><?= $q40b_ans === 'Yes' ? '&#9745;' : '&#9744;' ?> Yes</span>
        <span><?= $q40b_ans !== 'Yes' ? '&#9745;' : '&#9744;' ?> No</span>
      </div>
    </td>
    <td style="padding:2px 3px;vertical-align:top;font-size:7pt;border-left:0.5pt solid #000;">
      <?php if ($q40b_ans === 'Yes'): ?>
        <em style="font-size:5.5pt;">Please specify:</em> <?= $q40b_det !== '' ? p($q40b_det) : 'N/A' ?>
      <?php else: ?>N/A<?php endif; ?>
    </td>
  </tr>

  <?php
  // Q40b — PWD (stored in q40c)
  $q40c_ans = $questions['q40c'] ?? 'No';
  $q40c_det = trim((string)($questions['q40c_details'] ?? ''));
  ?>
  <tr>
    <td style="font-size:7pt;padding:2px 3px;">b. Are you a <b>person with disability?</b></td>
    <td style="text-align:center;vertical-align:middle;white-space:nowrap;padding:2px;border-left:0.5pt solid #000;">
      <div style="display:flex;flex-direction:column;align-items:center;gap:0;font-size:7pt;">
        <span><?= $q40c_ans === 'Yes' ? '&#9745;' : '&#9744;' ?> Yes</span>
        <span><?= $q40c_ans !== 'Yes' ? '&#9745;' : '&#9744;' ?> No</span>
      </div>
    </td>
    <td style="padding:2px 3px;vertical-align:top;font-size:7pt;border-left:0.5pt solid #000;">
      <?= $q40c_ans === 'Yes' && $q40c_det !== '' ? p($q40c_det) : 'N/A' ?>
    </td>
  </tr>

  <?php
  // Q40c — solo parent (stored in q40a)
  $q40a_ans = $questions['q40a'] ?? 'No';
  $q40a_det = trim((string)($questions['q40a_details'] ?? ''));
  ?>
  <tr>
    <td style="font-size:7pt;padding:2px 3px;">c. Are you a <b>solo parent?</b></td>
    <td style="text-align:center;vertical-align:middle;white-space:nowrap;padding:2px;border-left:0.5pt solid #000;">
      <div style="display:flex;flex-direction:column;align-items:center;gap:0;font-size:7pt;">
        <span><?= $q40a_ans === 'Yes' ? '&#9745;' : '&#9744;' ?> Yes</span>
        <span><?= $q40a_ans !== 'Yes' ? '&#9745;' : '&#9744;' ?> No</span>
      </div>
    </td>
    <td style="padding:2px 3px;vertical-align:top;font-size:7pt;border-left:0.5pt solid #000;">
      <?= $q40a_ans === 'Yes' && $q40a_det !== '' ? p($q40a_det) : 'N/A' ?>
    </td>
  </tr>
</table>

<!-- ═══════════════ X. CHARACTER REFERENCES — 3-col ═══════ -->
<table class="pf pf-join">
  <colgroup>
    <col style="width:30%"><col style="width:45%"><col style="width:25%">
  </colgroup>
  <tr><td colspan="3" class="sec-hdr">X. &nbsp;Character References</td></tr>
  <tr>
    <td colspan="3" style="font-size:6pt;background:#f0f0f0!important;padding:1px 4px;">
      (41.) (Persons not your relatives; not your superiors; known for at least one (1) year)
    </td>
  </tr>
  <tr>
    <td class="col-hdr">Name<br>(Write in full)</td>
    <td class="col-hdr">Office/Residential Address</td>
    <td class="col-hdr">Contact No. and/or Email</td>
  </tr>
  <?php foreach ($references as $r): ?>
  <tr style="height:18px;">
    <td style="padding:1px 2px;"><span class="fv"><?= nv($r['ref_name'] ?? '') ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($r['ref_address'] ?? '') ?></span></td>
    <td style="padding:1px 2px;"><span class="fv"><?= nv($r['ref_tel'] ?? '') ?></span></td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- ═══════════════ XI. CERTIFICATION / SIGNATURE (42.) ═══ -->
<table class="pf pf-join" style="table-layout:fixed;">
  <colgroup>
    <col style="width:44%">
    <col style="width:35%">
    <col style="width:21%">
  </colgroup>

  <!-- Declaration -->
  <tr>
    <td colspan="3" style="font-size:7pt;padding:4px 7px;line-height:1.5;background:#f9f9f9!important;">
      <b>42.</b> I declare under oath that I have personally accomplished this Personal Data Sheet which is a true,
      correct, and complete statement pursuant to the provisions of pertinent laws, rules, and regulations of the
      Republic of the Philippines. I authorize the <b>agency head/authorized representative</b> to verify/validate
      the contents stated herein. I agree that any misrepresentation made in this document and its attachments
      shall cause the filing of administrative/criminal case/s against me.
    </td>
  </tr>

  <!-- ID | Signature+Date+Thumbmark | Photo — all in one row, explicit cell widths come from colgroup -->
  <tr>
    <!-- (A) Government Issued ID -->
    <td style="padding:5px 7px;vertical-align:top;border-right:0.5pt solid #000;">
      <div style="font-size:6pt;font-weight:700;text-transform:uppercase;margin-bottom:5px;">
        Government Issued ID &mdash; Please indicate ID Number and Date of Issuance
      </div>
      <div style="margin-bottom:8px;">
        <span class="lbl">ID Type (e.g.&nbsp;Passport, Driver&rsquo;s License, SSS, UMID)</span>
        <span style="display:block;border-bottom:0.5pt solid #000;min-height:13px;padding-bottom:1px;font-size:7.5pt;">
          <?= p($emp['gov_id_type'] ?? '') ?: 'N/A' ?>
        </span>
      </div>
      <div style="margin-bottom:8px;">
        <span class="lbl">ID No.</span>
        <span style="display:block;border-bottom:0.5pt solid #000;min-height:13px;padding-bottom:1px;font-size:7.5pt;">
          <?= p($emp['gov_id_number'] ?? '') ?: 'N/A' ?>
        </span>
      </div>
      <div>
        <span class="lbl">Date / Place of Issuance</span>
        <span style="display:block;border-bottom:0.5pt solid #000;min-height:13px;padding-bottom:1px;font-size:7.5pt;">
          <?= p($emp['gov_id_issued'] ?? '') ?: 'N/A' ?>
        </span>
      </div>
    </td>

    <!-- (B) Signature box → Printed Name → Date Accomplished → Thumbmark (all stacked, no flex) -->
    <td style="padding:5px 7px;vertical-align:top;border-right:0.5pt solid #000;">
      <!-- Signature area: a plain bordered box (no flex — print-safe) -->
      <div style="border:0.5pt solid #000;height:65px;width:100%;margin-bottom:0;"></div>
      <!-- Printed name + label immediately below signature box -->
      <div style="border-top:0.5pt solid #000;text-align:center;padding:2px 2px 1px;font-size:7pt;line-height:1.3;">
        <b><?= htmlspecialchars(strtoupper(trim(
              ($emp['last_name']   ?? '') . ', ' .
              ($emp['first_name']  ?? '') . ' ' .
              ($emp['middle_name'] ?? '')
            )), ENT_QUOTES, 'UTF-8') ?></b><br>
        <em style="font-size:5.5pt;">Signature over Printed Name of Employee</em>
      </div>
      <!-- Date Accomplished -->
      <div style="margin-top:6px;border-top:0.5pt solid #000;text-align:center;
                  padding:2px 2px 1px;font-size:7pt;line-height:1.3;">
        &nbsp;<br>
        <em style="font-size:5.5pt;">Date Accomplished</em>
      </div>
      <!-- Right Thumbmark box — centred, below date -->
      <div style="border:0.5pt solid #000;height:48px;width:64px;
                  margin:6px auto 0;text-align:center;vertical-align:middle;
                  font-size:5.5pt;padding-top:16px;">
        RIGHT<br>THUMBMARK
      </div>
    </td>

    <!-- (C) 2×2 Passport-size Photo -->
    <td style="padding:5px 6px;vertical-align:top;text-align:center;">
      <div style="border:0.5pt solid #000;height:115px;width:88px;
                  margin:0 auto;text-align:center;vertical-align:middle;
                  font-size:6.5pt;padding-top:44px;">
        PHOTO<br>
        <em style="font-size:5.5pt;">(passport size<br>4.5cm &times; 3.5cm)</em>
      </div>
    </td>
  </tr>

  <!-- Notarization -->
  <tr>
    <td colspan="3" style="padding:6px 8px;font-size:7pt;line-height:1.8;background:#f5f5f5!important;">
      SUBSCRIBED AND SWORN to before me this&nbsp;
      <span style="display:inline-block;min-width:155px;border-bottom:0.5pt solid #000;font-size:7.5pt;">
        <?= p($emp['oath_date'] ?? '') ?: '&nbsp;' ?>
      </span>,
      affiant exhibiting his/her validly issued government ID as indicated above.
      <div style="width:210px;border-top:0.5pt solid #000;padding-top:3px;
                  text-align:center;margin-top:28px;font-size:7pt;line-height:1.4;">
        <em>(wet/e-signature/digital cert.)</em><br>
        <b>Person Administering Oath</b>
      </div>
    </td>
  </tr>

  <!-- Footer stamp -->
  <tr>
    <td colspan="3" style="text-align:right;padding:2px 8px;font-size:6pt;
        color:#444;background:#e8e8e8!important;letter-spacing:.2px;">
      CS FORM 212 (Revised 2025), Page 4 of 4
    </td>
  </tr>
</table>

</div><!-- /pds-paper -->

<script>
window.addEventListener('load', function () {
  setTimeout(function () { window.print(); }, 700);
});
</script>
</body>
</html>
