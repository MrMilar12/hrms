<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'hr') {
        header('Location: /hrms/admin/dashboard.php');
    } else {
        header('Location: /hrms/employee/dashboard.php');
    }
    exit;
}

header('Location: /hrms/auth/login.php');
exit;
