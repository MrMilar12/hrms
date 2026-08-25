<?php
// CLI script to create/reset the default admin user with a real bcrypt hash.
// Usage: php scripts/seed_admin.php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$username = 'admin';
$email = 'admin@hris.local';
$password = 'Admin@12345'; // change after first login
$employeeId = 1;

$pdo = Database::getInstance();

$roleId = $pdo->query("SELECT id FROM roles WHERE name = 'Admin'")->fetchColumn();
if (!$roleId) {
    die("Admin role not found. Run database/seed.sql first.\n");
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $update = $pdo->prepare('UPDATE users SET password_hash = ?, role_id = ?, status = "active" WHERE id = ?');
    $update->execute([$hash, $roleId, $existing]);
    echo "Admin user updated.\n";
} else {
    $insert = $pdo->prepare('INSERT INTO users (employee_id, username, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, "active")');
    $insert->execute([$employeeId, $username, $email, $hash, $roleId]);
    echo "Admin user created.\n";
}

echo "Username: {$username}\nPassword: {$password}\n";
