<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

$error   = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $pass2   = $_POST['password2'] ?? '';
    $role    = 'employee'; // Default role; admins must be created via installer

    if (empty($name) || empty($email) || empty($pass)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $stmt->close();
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param('ssss', $name, $email, $hashed, $role);
            if ($stmt2->execute()) {
                $newUserId = $conn->insert_id;
                // Auto-create a blank employee record linked to this user
                $empNo = 'EMP-' . str_pad($newUserId, 5, '0', STR_PAD_LEFT);
                $s = $conn->prepare("INSERT INTO employees (user_id, employee_no) VALUES (?, ?)");
                $s->bind_param('is', $newUserId, $empNo);
                $s->execute();
                $s->close();
                $stmt2->close();
                header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
                $stmt2->close();
            }
        }
        if ($stmt->num_rows > 0) { $stmt->close(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — <?= SITE_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="max-width:480px;">
    <div class="logo">
      <i class="fas fa-user-plus" style="font-size:2.5rem;color:#60a5fa;"></i>
      <h2 class="mt-2">Create Account</h2>
      <p style="color:var(--text-muted);font-size:.875rem;"><?= SITE_NAME ?></p>
    </div>

    <?php if ($error): ?>
      <div class="alert-glass"><i class="fas fa-exclamation-triangle me-2"></i><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
      <div class="mb-3">
        <label class="form-label-glass"><i class="fas fa-user me-1"></i>Full Name</label>
        <input type="text" name="name" class="form-control form-control-glass"
               value="<?= h($_POST['name'] ?? '') ?>" placeholder="Juan Dela Cruz" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label-glass"><i class="fas fa-envelope me-1"></i>Email Address</label>
        <input type="email" name="email" class="form-control form-control-glass"
               value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label-glass"><i class="fas fa-lock me-1"></i>Password</label>
        <input type="password" name="password" class="form-control form-control-glass"
               placeholder="Min. 6 characters" required>
      </div>
      <div class="mb-4">
        <label class="form-label-glass"><i class="fas fa-lock me-1"></i>Confirm Password</label>
        <input type="password" name="password2" class="form-control form-control-glass"
               placeholder="Repeat password" required>
      </div>
      <button type="submit" class="btn-glass w-100" style="padding:13px;font-size:1rem;">
        <i class="fas fa-user-plus me-2"></i>Create Account
      </button>
    </form>

    <p class="text-center mt-4" style="color:var(--text-muted);font-size:.875rem;">
      Already have an account?
      <a href="<?= BASE_URL ?>/auth/login.php" style="color:#60a5fa;">Login here</a>
    </p>
  </div>
</div>
</body>
</html>
