<?php
$error   = '';
$success = '';

if (isset($_POST['install'])) {
    $db_host      = trim($_POST['db_host'] ?? 'localhost');
    $db_user      = trim($_POST['db_user'] ?? 'root');
    $db_pass      = $_POST['db_pass'] ?? '';
    $admin_name   = trim($_POST['admin_name'] ?? '');
    $admin_email  = trim($_POST['admin_email'] ?? '');
    $admin_pass   = $_POST['admin_password'] ?? '';
    $admin_pass2  = $_POST['admin_password2'] ?? '';

    if (empty($admin_name) || empty($admin_email) || empty($admin_pass)) {
        $error = 'All administrator fields are required.';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } elseif (strlen($admin_pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($admin_pass !== $admin_pass2) {
        $error = 'Passwords do not match.';
    } elseif (file_exists('installed.lock')) {
        $error = 'System is already installed. Delete <code>install/installed.lock</code> to reinstall.';
    } else {
        $conn = new mysqli($db_host, $db_user, $db_pass);
        if ($conn->connect_error) {
            $error = 'Cannot connect to MySQL server. Check host/username/password.';
        } else {
            $sqlFile = file_get_contents('../database.sql');
            if ($sqlFile === false) {
                $error = 'Cannot read database.sql. Ensure it exists in the project root.';
            } else {
                if ($conn->multi_query($sqlFile)) {
                    // Flush all result sets
                    do {
                        if ($res = $conn->store_result()) {
                            $res->free();
                        }
                    } while ($conn->more_results() && $conn->next_result());

                    $conn2 = new mysqli($db_host, $db_user, $db_pass, 'hrms');
                    if ($conn2->connect_error) {
                        $error = 'Database created but could not reconnect: ' . htmlspecialchars($conn2->connect_error);
                    } else {
                        $conn2->set_charset('utf8mb4');
                        $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
                        $stmt = $conn2->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                        $stmt->bind_param('sss', $admin_name, $admin_email, $hashed);
                        if ($stmt->execute()) {
                            file_put_contents('installed.lock', date('Y-m-d H:i:s'));
                            $success = 'HRMS installed successfully! Admin account created.';
                        } else {
                            if ($conn2->errno === 1062) {
                                $success = 'Database already set up. Admin email may already exist. <a href="../auth/login.php">Try logging in</a>.';
                            } else {
                                $error = 'Failed to create admin: ' . htmlspecialchars($stmt->error);
                            }
                        }
                        $stmt->close();
                        $conn2->close();
                    }
                } else {
                    $error = 'SQL execution failed: ' . htmlspecialchars($conn->error);
                }
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>HRMS Installer</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="max-width:520px;">
    <div class="logo">
      <i class="fas fa-building" style="font-size:2.5rem;color:#60a5fa;"></i>
      <h2 class="mt-2">HRMS Installer</h2>
      <p style="color:var(--text-muted);font-size:.875rem;">Human Resource Management System</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-glass mb-4"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert-glass alert-glass-success mb-4">
        <?= $success ?>
        <?php if (file_exists('installed.lock')): ?>
          <br><a href="../auth/login.php" style="color:#86efac;font-weight:600;">&#8594; Go to Login</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" autocomplete="off">
      <p class="section-title">Database Settings</p>
      <div class="mb-3">
        <label class="form-label-glass">MySQL Host</label>
        <input type="text" name="db_host" class="form-control form-control-glass"
               value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
      </div>
      <div class="row g-3 mb-3">
        <div class="col">
          <label class="form-label-glass">Username</label>
          <input type="text" name="db_user" class="form-control form-control-glass"
                 value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
        </div>
        <div class="col">
          <label class="form-label-glass">Password</label>
          <input type="password" name="db_pass" class="form-control form-control-glass" placeholder="(leave blank if none)">
        </div>
      </div>

      <p class="section-title mt-4">Administrator Account</p>
      <div class="mb-3">
        <label class="form-label-glass">Full Name</label>
        <input type="text" name="admin_name" class="form-control form-control-glass"
               value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label-glass">Email Address</label>
        <input type="email" name="admin_email" class="form-control form-control-glass"
               value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
      </div>
      <div class="row g-3 mb-4">
        <div class="col">
          <label class="form-label-glass">Password</label>
          <input type="password" name="admin_password" class="form-control form-control-glass"
                 placeholder="Min. 6 characters" required>
        </div>
        <div class="col">
          <label class="form-label-glass">Confirm Password</label>
          <input type="password" name="admin_password2" class="form-control form-control-glass" required>
        </div>
      </div>

      <button type="submit" name="install" class="btn-glass w-100"
              style="padding:14px;font-size:1rem;">
        <i class="fas fa-download me-2"></i>Install HRMS System
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
