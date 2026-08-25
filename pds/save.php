<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pds/form.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// === Helper: sanitize string input ===
function clean($val) {
    $v = trim((string)($val ?? ''));
    return $v === '' ? null : $v;
}
function cleanDate($val) {
    $v = trim((string)($val ?? ''));
    return (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) ? $v : null;
}

$conn->begin_transaction();

try {
    // ================================================================
    // 1. UPSERT EMPLOYEE (uses user_id UNIQUE key)
    // ================================================================
    $perm_same    = isset($_POST['permanent_same']) ? 1 : 0;
    $emp_no       = clean($_POST['agency_employee_no']);

    $stmt = $conn->prepare("
        INSERT INTO employees
          (user_id, last_name, first_name, middle_name, name_extension,
           birthdate, place_of_birth, sex, civil_status,
           height, weight, blood_type, citizenship,
           gsis, pagibig, philhealth, philsys_psn, tin, agency_employee_no,
           telephone, mobile, email_address,
           residential_house, residential_street, residential_subdivision,
           residential_barangay, residential_city, residential_province, residential_zip,
           permanent_same,
           permanent_house, permanent_street, permanent_subdivision,
           permanent_barangay, permanent_city, permanent_province, permanent_zip,
           gov_id_type, gov_id_number, gov_id_issued)
        VALUES (?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?,?,?, ?,?,?, ?,?,?,?,?,?,?, ?, ?,?,?,?,?,?,?, ?,?,?)
        ON DUPLICATE KEY UPDATE
          last_name=VALUES(last_name), first_name=VALUES(first_name),
          middle_name=VALUES(middle_name), name_extension=VALUES(name_extension),
          birthdate=VALUES(birthdate), place_of_birth=VALUES(place_of_birth),
          sex=VALUES(sex), civil_status=VALUES(civil_status),
          height=VALUES(height), weight=VALUES(weight), blood_type=VALUES(blood_type),
          citizenship=VALUES(citizenship),
          gsis=VALUES(gsis), pagibig=VALUES(pagibig), philhealth=VALUES(philhealth),
          philsys_psn=VALUES(philsys_psn), tin=VALUES(tin), agency_employee_no=VALUES(agency_employee_no),
          telephone=VALUES(telephone), mobile=VALUES(mobile), email_address=VALUES(email_address),
          residential_house=VALUES(residential_house), residential_street=VALUES(residential_street),
          residential_subdivision=VALUES(residential_subdivision),
          residential_barangay=VALUES(residential_barangay),
          residential_city=VALUES(residential_city), residential_province=VALUES(residential_province),
          residential_zip=VALUES(residential_zip),
          permanent_same=VALUES(permanent_same),
          permanent_house=VALUES(permanent_house), permanent_street=VALUES(permanent_street),
          permanent_subdivision=VALUES(permanent_subdivision),
          permanent_barangay=VALUES(permanent_barangay),
          permanent_city=VALUES(permanent_city), permanent_province=VALUES(permanent_province),
          permanent_zip=VALUES(permanent_zip),
          gov_id_type=VALUES(gov_id_type), gov_id_number=VALUES(gov_id_number),
          gov_id_issued=VALUES(gov_id_issued)
    ");

    $b_last_name    = clean($_POST['last_name']);
    $b_first_name   = clean($_POST['first_name']);
    $b_middle_name  = clean($_POST['middle_name']);
    $b_name_ext     = clean($_POST['name_extension']);
    $b_birthdate    = cleanDate($_POST['birthdate']);
    $b_pob          = clean($_POST['place_of_birth']);
    $b_sex          = clean($_POST['sex']);
    $b_civil        = clean($_POST['civil_status']);
    $b_height       = clean($_POST['height']);
    $b_weight       = clean($_POST['weight']);
    $b_blood        = clean($_POST['blood_type']);
    $b_citizen      = clean($_POST['citizenship']);
    $b_gsis         = clean($_POST['gsis']);
    $b_pagibig      = clean($_POST['pagibig']);
    $b_philhealth   = clean($_POST['philhealth']);
    $b_philsys      = clean($_POST['philsys_psn'] ?? '');
    $b_tin          = clean($_POST['tin']);
    $b_agency_no    = clean($_POST['agency_employee_no']);
    $b_telephone    = clean($_POST['telephone']);
    $b_mobile       = clean($_POST['mobile']);
    $b_email        = clean($_POST['email_address']);
    $b_res_house    = clean($_POST['residential_house']);
    $b_res_street   = clean($_POST['residential_street']);
    $b_res_subdiv   = clean($_POST['residential_subdivision']);
    $b_res_brgy     = clean($_POST['residential_barangay']);
    $b_res_city     = clean($_POST['residential_city']);
    $b_res_prov     = clean($_POST['residential_province']);
    $b_res_zip      = clean($_POST['residential_zip']);
    $b_perm_house   = clean($_POST['permanent_house']);
    $b_perm_street  = clean($_POST['permanent_street']);
    $b_perm_subdiv  = clean($_POST['permanent_subdivision']);
    $b_perm_brgy    = clean($_POST['permanent_barangay']);
    $b_perm_city    = clean($_POST['permanent_city']);
    $b_perm_prov    = clean($_POST['permanent_province']);
    $b_perm_zip     = clean($_POST['permanent_zip']);
    $b_gov_id_type  = clean($_POST['gov_id_type'] ?? '');
    $b_gov_id_no    = clean($_POST['gov_id_number'] ?? '');
    $b_gov_id_iss   = clean($_POST['gov_id_issued'] ?? '');
    // format: i + 28s (user_id through res_zip) + i (perm_same) + 10s (perm fields + gov id) = 40
    $stmt->bind_param('isssssssssssssssssssssssssssissssssssss',
        $userId,
        $b_last_name, $b_first_name, $b_middle_name, $b_name_ext,
        $b_birthdate, $b_pob, $b_sex, $b_civil,
        $b_height, $b_weight, $b_blood, $b_citizen,
        $b_gsis, $b_pagibig, $b_philhealth, $b_philsys, $b_tin, $b_agency_no,
        $b_telephone, $b_mobile, $b_email,
        $b_res_house, $b_res_street, $b_res_subdiv, $b_res_brgy,
        $b_res_city, $b_res_prov, $b_res_zip,
        $perm_same,
        $b_perm_house, $b_perm_street, $b_perm_subdiv, $b_perm_brgy,
        $b_perm_city, $b_perm_prov, $b_perm_zip,
        $b_gov_id_type, $b_gov_id_no, $b_gov_id_iss
    );
    $stmt->execute();
    $stmt->close();

    // Get employee_id
    $s = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $s->bind_param('i', $userId);
    $s->execute();
    $empRow = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$empRow) { throw new Exception('Employee record not found after save.'); }
    $employeeId = (int)$empRow['id'];

    // Update session
    $_SESSION['employee_id'] = $employeeId;

    // ================================================================
    // 2. FAMILY BACKGROUND (UPSERT)
    // ================================================================
    $stmt = $conn->prepare("
        INSERT INTO family_background
          (employee_id, spouse_surname, spouse_firstname, spouse_middlename, spouse_extension,
           spouse_occupation, spouse_employer, spouse_business_address, spouse_telephone,
           father_surname, father_firstname, father_middlename, father_extension,
           mother_surname, mother_firstname, mother_middlename)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          spouse_surname=VALUES(spouse_surname), spouse_firstname=VALUES(spouse_firstname),
          spouse_middlename=VALUES(spouse_middlename), spouse_extension=VALUES(spouse_extension),
          spouse_occupation=VALUES(spouse_occupation), spouse_employer=VALUES(spouse_employer),
          spouse_business_address=VALUES(spouse_business_address),
          spouse_telephone=VALUES(spouse_telephone),
          father_surname=VALUES(father_surname), father_firstname=VALUES(father_firstname),
          father_middlename=VALUES(father_middlename), father_extension=VALUES(father_extension),
          mother_surname=VALUES(mother_surname), mother_firstname=VALUES(mother_firstname),
          mother_middlename=VALUES(mother_middlename)
    ");
    $f_sp_sur   = clean($_POST['spouse_surname']);
    $f_sp_first = clean($_POST['spouse_firstname']);
    $f_sp_mid   = clean($_POST['spouse_middlename']);
    $f_sp_ext   = clean($_POST['spouse_extension']);
    $f_sp_occ   = clean($_POST['spouse_occupation']);
    $f_sp_emp   = clean($_POST['spouse_employer']);
    $f_sp_biz   = clean($_POST['spouse_business_address']);
    $f_sp_tel   = clean($_POST['spouse_telephone']);
    $f_fa_sur   = clean($_POST['father_surname']);
    $f_fa_first = clean($_POST['father_firstname']);
    $f_fa_mid   = clean($_POST['father_middlename']);
    $f_fa_ext   = clean($_POST['father_extension']);
    $f_mo_sur   = clean($_POST['mother_surname']);
    $f_mo_first = clean($_POST['mother_firstname']);
    $f_mo_mid   = clean($_POST['mother_middlename']);
    $stmt->bind_param('isssssssssssssss',
        $employeeId,
        $f_sp_sur, $f_sp_first, $f_sp_mid, $f_sp_ext,
        $f_sp_occ, $f_sp_emp, $f_sp_biz, $f_sp_tel,
        $f_fa_sur, $f_fa_first, $f_fa_mid, $f_fa_ext,
        $f_mo_sur, $f_mo_first, $f_mo_mid
    );
    $stmt->execute();
    $stmt->close();

    // ================================================================
    // 3. CHILDREN (delete + re-insert)
    // ================================================================
    $del = $conn->prepare("DELETE FROM children WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['child_name'])) {
        $stmt = $conn->prepare("INSERT INTO children (employee_id, child_name, date_of_birth) VALUES (?,?,?)");
        foreach ($_POST['child_name'] as $i => $cn) {
            $cn  = clean($cn);
            $dob = cleanDate($_POST['child_dob'][$i] ?? '');
            if ($cn) {
                $stmt->bind_param('iss', $employeeId, $cn, $dob);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    // ================================================================
    // 4. EDUCATION (delete + re-insert each level)
    // ================================================================
    $del = $conn->prepare("DELETE FROM education WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['edu_level'])) {
        $stmt = $conn->prepare("INSERT INTO education
            (employee_id, level, school, degree, from_year, to_year, units_earned, year_graduated, honors)
            VALUES (?,?,?,?,?,?,?,?,?)");
        foreach ($_POST['edu_level'] as $i => $level) {
            $school  = clean($_POST['edu_school'][$i] ?? '');
            if (!$school) continue;
            $degree  = clean($_POST['edu_degree'][$i] ?? '');
            $from    = clean($_POST['edu_from'][$i] ?? '');
            $to      = clean($_POST['edu_to'][$i] ?? '');
            $units   = clean($_POST['edu_units'][$i] ?? '');
            $grad    = clean($_POST['edu_graduated'][$i] ?? '');
            $honors  = clean($_POST['edu_honors'][$i] ?? '');
            $stmt->bind_param('issssssss', $employeeId, $level, $school, $degree, $from, $to, $units, $grad, $honors);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ================================================================
    // 5. ELIGIBILITY
    // ================================================================
    $del = $conn->prepare("DELETE FROM eligibility WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['elig_career'])) {
        $stmt = $conn->prepare("INSERT INTO eligibility
            (employee_id, career_service, rating, date_of_exam, place_of_exam, license_no, license_validity)
            VALUES (?,?,?,?,?,?,?)");
        foreach ($_POST['elig_career'] as $i => $cs) {
            $cs = clean($cs);
            if (!$cs) continue;
            $rating    = clean($_POST['elig_rating'][$i] ?? '');
            $examDate  = cleanDate($_POST['elig_exam_date'][$i] ?? '');
            $place     = clean($_POST['elig_place'][$i] ?? '');
            $licNo     = clean($_POST['elig_license'][$i] ?? '');
            $licVal    = cleanDate($_POST['elig_validity'][$i] ?? '');
            $stmt->bind_param('issssss', $employeeId, $cs, $rating, $examDate, $place, $licNo, $licVal);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ================================================================
    // 6. WORK EXPERIENCE
    // ================================================================
    $del = $conn->prepare("DELETE FROM work_experience WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['work_position'])) {
        $stmt = $conn->prepare("INSERT INTO work_experience
            (employee_id, start_date, end_date, is_present, position_title, department,
             status_appointment, is_government)
            VALUES (?,?,?,?,?,?,?,?)");
        foreach ($_POST['work_position'] as $i => $pos) {
            $pos = clean($pos);
            if (!$pos) continue;
            $start   = cleanDate($_POST['work_start'][$i] ?? '');
            $present = !empty($_POST['work_present'][$i]) ? 1 : 0;
            $end     = $present ? null : cleanDate($_POST['work_end'][$i] ?? '');
            $dept    = clean($_POST['work_dept'][$i] ?? '');
            $appoint = clean($_POST['work_appointment'][$i] ?? '');
            $gov     = clean($_POST['work_gov'][$i] ?? '') ?: 'N';
            $stmt->bind_param('issiisss',
                $employeeId, $start, $end, $present, $pos, $dept, $appoint, $gov);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ================================================================
    // 7. VOLUNTARY WORK
    // ================================================================
    $del = $conn->prepare("DELETE FROM voluntary_work WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['vol_org'])) {
        $stmt = $conn->prepare("INSERT INTO voluntary_work
            (employee_id, organization, org_address, from_date, to_date, hours_count, position_nature)
            VALUES (?,?,?,?,?,?,?)");
        foreach ($_POST['vol_org'] as $i => $org) {
            $org = clean($org);
            if (!$org) continue;
            $addr  = clean($_POST['vol_address'][$i] ?? '');
            $from  = cleanDate($_POST['vol_from'][$i] ?? '');
            $to    = cleanDate($_POST['vol_to'][$i] ?? '');
            $hours = (int)($_POST['vol_hours'][$i] ?? 0) ?: null;
            $pos   = clean($_POST['vol_position'][$i] ?? '');
            $stmt->bind_param('issssis', $employeeId, $org, $addr, $from, $to, $hours, $pos);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ================================================================
    // 8. LEARNING & DEVELOPMENT
    // ================================================================
    $del = $conn->prepare("DELETE FROM learning_development WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['ld_title'])) {
        $stmt = $conn->prepare("INSERT INTO learning_development
            (employee_id, title, from_date, to_date, hours_count, ld_type, conducted_by)
            VALUES (?,?,?,?,?,?,?)");
        foreach ($_POST['ld_title'] as $i => $title) {
            $title = clean($title);
            if (!$title) continue;
            $from    = cleanDate($_POST['ld_from'][$i] ?? '');
            $to      = cleanDate($_POST['ld_to'][$i] ?? '');
            $hours   = (int)($_POST['ld_hours'][$i] ?? 0) ?: null;
            $type    = clean($_POST['ld_type'][$i] ?? '') ?: 'Technical';
            $condBy  = clean($_POST['ld_conducted'][$i] ?? '');
            $stmt->bind_param('isssiss', $employeeId, $title, $from, $to, $hours, $type, $condBy);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ================================================================
    // 9. OTHER INFO (UPSERT)
    // ================================================================
    $stmt = $conn->prepare("
        INSERT INTO other_info (employee_id, special_skills, non_academic_distinctions, org_memberships)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE
          special_skills=VALUES(special_skills),
          non_academic_distinctions=VALUES(non_academic_distinctions),
          org_memberships=VALUES(org_memberships)
    ");
    $oi_skills = clean($_POST['special_skills']);
    $oi_dists  = clean($_POST['non_academic_distinctions']);
    $oi_orgs   = clean($_POST['org_memberships']);
    $stmt->bind_param('isss', $employeeId, $oi_skills, $oi_dists, $oi_orgs);
    $stmt->execute();
    $stmt->close();

    // ================================================================
    // 10. PDS QUESTIONS (UPSERT)
    // ================================================================
    $qKeys = ['q34a','q34b','q35a','q35b','q36','q37','q38a','q38b','q39','q40a','q40b','q40c'];
    $qVals = [];
    foreach ($qKeys as $k) {
        $qVals[$k] = (isset($_POST[$k]) && $_POST[$k] === 'Yes') ? 'Yes' : 'No';
        $qVals[$k . '_details'] = clean($_POST[$k . '_details'] ?? '');
    }
    // Prepend Date Filed to q35b_details
    $dateFiled = clean($_POST['q35b_date_filed'] ?? '');
    if ($dateFiled) {
        $suffix = $qVals['q35b_details'] ? ("\n" . $qVals['q35b_details']) : '';
        $qVals['q35b_details'] = 'Date Filed: ' . $dateFiled . $suffix;
    }

    $stmt = $conn->prepare("
        INSERT INTO pds_questions
          (employee_id,q34a,q34a_details,q34b,q34b_details,q35a,q35a_details,q35b,q35b_details,
           q36,q36_details,q37,q37_details,q38a,q38a_details,q38b,q38b_details,q39,q39_details,
           q40a,q40a_details,q40b,q40b_details,q40c,q40c_details)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          q34a=VALUES(q34a),q34a_details=VALUES(q34a_details),
          q34b=VALUES(q34b),q34b_details=VALUES(q34b_details),
          q35a=VALUES(q35a),q35a_details=VALUES(q35a_details),
          q35b=VALUES(q35b),q35b_details=VALUES(q35b_details),
          q36=VALUES(q36),q36_details=VALUES(q36_details),
          q37=VALUES(q37),q37_details=VALUES(q37_details),
          q38a=VALUES(q38a),q38a_details=VALUES(q38a_details),
          q38b=VALUES(q38b),q38b_details=VALUES(q38b_details),
          q39=VALUES(q39),q39_details=VALUES(q39_details),
          q40a=VALUES(q40a),q40a_details=VALUES(q40a_details),
          q40b=VALUES(q40b),q40b_details=VALUES(q40b_details),
          q40c=VALUES(q40c),q40c_details=VALUES(q40c_details)
    ");
    $stmt->bind_param('issssssssssssssssssssssss',
        $employeeId,
        $qVals['q34a'], $qVals['q34a_details'],
        $qVals['q34b'], $qVals['q34b_details'],
        $qVals['q35a'], $qVals['q35a_details'],
        $qVals['q35b'], $qVals['q35b_details'],
        $qVals['q36'],  $qVals['q36_details'],
        $qVals['q37'],  $qVals['q37_details'],
        $qVals['q38a'], $qVals['q38a_details'],
        $qVals['q38b'], $qVals['q38b_details'],
        $qVals['q39'],  $qVals['q39_details'],
        $qVals['q40a'], $qVals['q40a_details'],
        $qVals['q40b'], $qVals['q40b_details'],
        $qVals['q40c'], $qVals['q40c_details']
    );
    $stmt->execute();
    $stmt->close();

    // ================================================================
    // 11. REFERENCES
    // ================================================================
    $del = $conn->prepare("DELETE FROM references_info WHERE employee_id = ?");
    $del->bind_param('i', $employeeId); $del->execute(); $del->close();

    if (!empty($_POST['ref_name'])) {
        $stmt = $conn->prepare("INSERT INTO references_info (employee_id, ref_name, ref_address, ref_tel) VALUES (?,?,?,?)");
        foreach ($_POST['ref_name'] as $i => $rn) {
            $rn = clean($rn);
            if (!$rn) continue;
            $addr = clean($_POST['ref_address'][$i] ?? '');
            $tel  = clean($_POST['ref_tel'][$i] ?? '');
            $stmt->bind_param('isss', $employeeId, $rn, $addr, $tel);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ================================================================
    // 12. PDS STATUS — mark submitted if action=submit
    // ================================================================
    $isSubmit = (isset($_POST['action']) && $_POST['action'] === 'submit') ? 1 : 0;
    $submitTime = $isSubmit ? date('Y-m-d H:i:s') : null;

    $stmt = $conn->prepare("
        INSERT INTO pds_status (employee_id, is_submitted, submitted_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
          is_submitted = IF(VALUES(is_submitted)=1, 1, is_submitted),
          submitted_at = IF(VALUES(is_submitted)=1, VALUES(submitted_at), submitted_at)
    ");
    $stmt->bind_param('iis', $employeeId, $isSubmit, $submitTime);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header('Location: ' . BASE_URL . '/pds/form.php?saved=1');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $msg = urlencode('Save failed: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/pds/form.php?error=' . $msg);
    exit;
}
