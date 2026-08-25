<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been deactivated. Contact administrator.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Link employee_id to session if applicable
                $empId = getEmployeeId($conn, $user['id']);
                $_SESSION['employee_id'] = $empId;

                if (in_array($user['role'], ['admin', 'hr'])) {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . '/employee/dashboard.php');
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — <?= SITE_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="logo">
      <i class="fas fa-building" style="font-size:2.8rem;color:#60a5fa;"></i>
      <h2 class="mt-2"><?= SITE_NAME ?></h2>
      <p style="color:var(--text-muted);font-size:.875rem;">Human Resource Management System</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-glass"><i class="fas fa-exclamation-triangle me-2"></i><?= h($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
      <div class="alert-glass alert-glass-success"><i class="fas fa-check-circle me-2"></i>Account created! You can now log in.</div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
      <div class="mb-3">
        <label class="form-label-glass"><i class="fas fa-envelope me-1"></i>Email Address</label>
        <input type="email" name="email" class="form-control form-control-glass"
               value="<?= h($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label-glass"><i class="fas fa-lock me-1"></i>Password</label>
        <div class="position-relative">
          <input type="password" name="password" id="pwdField"
                 class="form-control form-control-glass" placeholder="••••••••" required>
          <button type="button" onclick="togglePwd('pwdField',this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-glass w-100" style="padding:13px;font-size:1rem;">
        <i class="fas fa-sign-in-alt me-2"></i>Login
      </button>
    </form>

    <p class="text-center mt-4" style="color:var(--text-muted);font-size:.875rem;">
      Don't have an account?
      <a href="<?= BASE_URL ?>/auth/register.php" style="color:#60a5fa;">Register here</a>
    </p>
  </div>
</div>
<script>
function togglePwd(fieldId, btn) {
  const f = document.getElementById(fieldId);
  const icon = btn.querySelector('i');
  if (f.type === 'password') {
    f.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    f.type = 'password';
    icon.className = 'fas fa-eye';
  }
}
</script>
</body>
</html>
