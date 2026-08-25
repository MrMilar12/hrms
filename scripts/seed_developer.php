<?php
// Creates the first protected Developer account.
// Usage: php scripts/seed_developer.php [username] [email] [password]

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$username = $argv[1] ?? 'developer';
$email = $argv[2] ?? 'developer@hris.local';
$generatedPassword = !isset($argv[3]);
$password = $argv[3] ?? ('Dev!' . bin2hex(random_bytes(8)));

if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) die("Invalid username.\n");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) die("Invalid email.\n");
if (strlen($password) < 12) die("Developer password must contain at least 12 characters.\n");

$pdo = Database::getInstance();
$roleId = $pdo->query("SELECT id FROM roles WHERE name = 'Developer'")->fetchColumn();
if (!$roleId) die("Developer role not found. Run migration 010 first.\n");

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $email]);
if ($stmt->fetchColumn()) die("A user already has that username or email.\n");

$insert = $pdo->prepare(
    "INSERT INTO users (employee_id, username, email, password_hash, role_id, status)
     VALUES (NULL, ?, ?, ?, ?, 'active')"
);
$insert->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), $roleId]);

echo "Developer account created.\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n";
if ($generatedPassword) echo "Store this generated password securely; it will not be shown again.\n";
