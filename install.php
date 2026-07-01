<?php
// ============================================================
// One-time setup: creates (or resets) the default admin login.
// Visit http://localhost/Hospital%20Management%20System/install.php
// once, then DELETE this file.
// ============================================================
require_once __DIR__ . '/includes/db.php';

$username = 'admin';
$password = 'admin123';
$fullName = 'System Administrator';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Upsert the admin user.
q(
    "INSERT INTO users (username, password_hash, full_name, role)
     VALUES (?, ?, ?, 'admin')
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash),
                             full_name = VALUES(full_name), role = 'admin'",
    'sss',
    [$username, $hash, $fullName]
);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Install</title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="login-wrap">
  <h1>✅ Setup complete</h1>
  <p>The admin account is ready.</p>
  <table>
    <tr><th>Username</th><td><?= htmlspecialchars($username) ?></td></tr>
    <tr><th>Password</th><td><?= htmlspecialchars($password) ?></td></tr>
  </table>
  <p class="hint" style="margin-top:16px">
    For security, <strong>delete install.php</strong> now, then
    <a href="login.php">go to login</a>.
  </p>
</div>
</body></html>
