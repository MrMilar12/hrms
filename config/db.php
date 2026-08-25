<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/hrms');
define('SITE_NAME', 'HRMS Philippines');

$conn = new mysqli('localhost', 'root', '', 'hrms');

if ($conn->connect_error) {
    die('
    <div style="font-family:Arial,sans-serif;padding:60px 40px;background:#0f172a;color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center">
        <div style="text-align:center">
            <h2 style="color:#f87171;margin-bottom:12px;">&#9888; Database Connection Failed</h2>
            <p style="color:#94a3b8;">Please <a href="' . BASE_URL . '/install/install.php" style="color:#60a5fa;">run the installer</a> to set up the database.</p>
        </div>
    </div>');
}

$conn->set_charset('utf8mb4');
