<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = fetch_one('SELECT * FROM users WHERE username = ? LIMIT 1', 's', [$username]);
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login &middot; <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <h1>🏥 <?= e(APP_NAME) ?></h1>
    <p>Sign in to continue</p>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label>Username</label>
      <input type="text" name="username" required autofocus>
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn" style="width:100%;margin-top:18px" type="submit">Sign in</button>
    </form>
    <p class="hint" style="margin-top:16px">Default: <code>admin</code> / <code>admin123</code> (run install.php first)</p>
  </div>
</body>
</html>
