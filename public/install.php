<?php
// Web-based installer: checks requirements, creates the database, runs schema/seed,
// creates the admin account, and writes config/app.php. Refuses to re-run once installed.

require_once __DIR__ . '/../config/constants.php';

session_start();

$lockFile = STORAGE_PATH . '/installed.lock';
$alreadyInstalled = is_file($lockFile);

if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

// Required: installation cannot proceed without these.
$requirements = [
    'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL extension' => extension_loaded('pdo_mysql'),
    'database/schema.sql present' => is_file(BASE_PATH . '/database/schema.sql'),
    'database/seed.sql present' => is_file(BASE_PATH . '/database/seed.sql'),
    'database/seed_current_data.sql present' => is_file(BASE_PATH . '/database/seed_current_data.sql'),
    'config/ folder writable' => is_writable(CONFIG_PATH),
    'storage/ folder writable' => is_writable(STORAGE_PATH),
];
$requirementsMet = !in_array(false, $requirements, true);

// Recommended: app still installs without these, but some features will be limited.
$recommended = [
    'GD extension (needed later for photo/task-attachment thumbnails)' => extension_loaded('gd'),
    'uploads/ folder writable' => is_writable(UPLOADS_PATH),
];

function runSqlFile(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    // Strip line comments, then split on statement-terminating semicolons.
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Tolerate re-running against an already-provisioned database.
            if (!str_contains($e->getMessage(), 'already exists') && !str_contains($e->getMessage(), 'Duplicate')) {
                throw $e;
            }
        }
    }
}

if ($requirementsMet && !$alreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['install_csrf'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please reload the page and try again.';
    }

    $dbHost = trim($_POST['db_host'] ?? '');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $dataset = ($_POST['dataset'] ?? 'current') === 'starter' ? 'starter' : 'current';

    $adminUsername = trim($_POST['admin_username'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        $errors[] = 'Database host, name, and user are required.';
    }
    if ($adminUsername === '' || $adminEmail === '' || $adminPassword === '') {
        $errors[] = 'Admin username, email, and password are required.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email must be a valid email address.';
    }
    if (strlen($adminPassword) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }
    if ($adminPassword !== $adminPasswordConfirm) {
        $errors[] = 'Admin password confirmation does not match.';
    }

    if (!$errors) {
        try {
            $bootstrap = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $bootstrap->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbName) . '` CHARACTER SET utf8mb4');

            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            runSqlFile($pdo, BASE_PATH . '/database/schema.sql');
            runSqlFile($pdo, BASE_PATH . ($dataset === 'current' ? '/database/seed_current_data.sql' : '/database/seed.sql'));

            // Never leave credentials copied from the snapshot active on a fresh install.
            if ($dataset === 'current') {
                $pdo->exec("UPDATE users SET status = 'inactive', two_factor_secret = NULL, two_factor_enabled = 0, failed_login_attempts = 0, locked_until = NULL");
            }

            // Create/update the admin account with the submitted credentials.
            $roleId = $pdo->query("SELECT id FROM roles WHERE name = 'Admin'")->fetchColumn();
            $hash = password_hash($adminPassword, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$adminUsername]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $update = $pdo->prepare('UPDATE users SET email = ?, password_hash = ?, role_id = ?, status = "active" WHERE id = ?');
                $update->execute([$adminEmail, $hash, $roleId, $existingId]);
            } else {
                $employeeId = $pdo->query('SELECT id FROM employees ORDER BY id LIMIT 1')->fetchColumn() ?: null;
                $insert = $pdo->prepare('INSERT INTO users (employee_id, username, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, "active")');
                $insert->execute([$employeeId, $adminUsername, $adminEmail, $hash, $roleId]);
            }

            // Persist the real DB credentials for the app to use afterward.
            $appConfig = "<?php\n// Environment-based settings (dev/prod). Do not commit real secrets in prod.\n\n"
                . "return [\n"
                . "    'env' => 'production',\n"
                . "    'debug' => false,\n"
                . "    'db' => [\n"
                . "        'host' => " . var_export($dbHost, true) . ",\n"
                . "        'port' => " . var_export($dbPort, true) . ",\n"
                . "        'name' => " . var_export($dbName, true) . ",\n"
                . "        'user' => " . var_export($dbUser, true) . ",\n"
                . "        'pass' => " . var_export($dbPass, true) . ",\n"
                . "        'charset' => 'utf8mb4',\n"
                . "    ],\n"
                . "    'timezone' => 'Asia/Manila',\n"
                . "];\n";
            file_put_contents(CONFIG_PATH . '/app.php', $appConfig);

            file_put_contents($lockFile, 'Installed at ' . date('c') . " by {$adminUsername}\n");

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HRMS &mdash; Installation</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/glass.css?v=<?= rawurlencode(CSS_ASSET_VERSION) ?>">
</head>
<body class="auth-page">
<div class="auth-card glass-strong" style="width:560px; max-width:94vw; padding:2.5rem;">
    <div class="sidebar-brand" style="padding:0 0 1rem;">
        <span class="brand-icon" style="width:34px;height:34px;border-radius:14px;background:var(--glass-bg-ultralight);display:flex;align-items:center;justify-content:center;">&#10024;</span>
        <span class="brand-text" style="margin-left:0.6rem;"><strong>HRMS</strong><span>Installation Wizard</span></span>
    </div>

    <?php if ($alreadyInstalled): ?>
        <div class="alert alert-success">HRMS is already installed.</div>
        <p style="color:var(--text-secondary); font-size:0.88rem;">
            To reinstall, delete <code>storage/installed.lock</code> first (this will not erase your database).
        </p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/login">Go to Login</a>

    <?php elseif ($success): ?>
        <div class="alert alert-success">Installation complete!</div>
        <p style="color:var(--text-secondary); font-size:0.88rem;">
            The database has been provisioned and your admin account is ready.
            For security, delete or restrict access to <code>public/install.php</code> now.
        </p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/login">Go to Login</a>

    <?php else: ?>
        <p class="subtitle">Set up the database and create your administrator account.</p>

        <?php if (!$requirementsMet): ?>
            <div class="alert alert-error">Some required items are missing. Fix these, then reload the page.</div>
        <?php endif; ?>

        <table style="margin-bottom:1.25rem;">
            <?php foreach ($requirements as $label => $met): ?>
                <tr>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td><span class="badge <?= $met ? 'badge-done' : 'badge-cancelled' ?>"><?= $met ? 'OK' : 'Missing' ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php foreach ($recommended as $label => $met): ?>
                <tr>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td><span class="badge <?= $met ? 'badge-done' : 'badge-in-progress' ?>"><?= $met ? 'OK' : 'Recommended' ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($requirementsMet): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['install_csrf']) ?>">

            <h3 style="margin-bottom:0.25rem;">Database</h3>
            <p style="margin:0 0 0.75rem; font-size:0.8rem; color:var(--text-muted);">The database will be created automatically if it doesn't exist yet.</p>
            <div class="form-row">
                <div class="form-group"><label>Host</label><input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '127.0.0.1') ?>" required></div>
                <div class="form-group"><label>Port</label><input name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Database Name</label><input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'hris') ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>DB Username</label><input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required></div>
                <div class="form-group"><label>DB Password</label><input type="password" name="db_pass" value="" placeholder="Leave blank if none"></div>
            </div>

            <h3 style="margin:1.25rem 0 0.25rem;">Installation Data</h3>
            <p style="margin:0 0 0.75rem; font-size:0.8rem; color:var(--text-muted);">Restore the exported working dataset or begin with only basic reference records.</p>
            <div class="form-group">
                <label><input type="radio" name="dataset" value="current" <?= ($_POST['dataset'] ?? 'current') === 'current' ? 'checked' : '' ?>> Current snapshot (10,004 employees and completed PDS sample data)</label>
                <label><input type="radio" name="dataset" value="starter" <?= ($_POST['dataset'] ?? '') === 'starter' ? 'checked' : '' ?>> Starter data only</label>
            </div>

            <h3 style="margin:1.25rem 0 0.25rem;">Administrator Account</h3>
            <p style="margin:0 0 0.75rem; font-size:0.8rem; color:var(--text-muted);">You'll use these credentials to sign in once setup finishes.</p>
            <div class="form-row">
                <div class="form-group"><label>Username</label><input name="admin_username" value="<?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? 'admin@hris.local') ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Password</label><input type="password" name="admin_password" required minlength="8" placeholder="At least 8 characters"></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="admin_password_confirm" required minlength="8"></div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:0.5rem;">Install HRMS</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
