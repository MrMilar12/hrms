<?php
function requireAuth($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    if ($role !== null && $_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/employee/dashboard.php?error=unauthorized');
        exit;
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'hr'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'hr']);
}

function getEmployeeId($conn, $userId) {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    if ($row = $result->fetch_assoc()) {
        return (int)$row['id'];
    }
    return null;
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
