<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
$active = $active ?? '';
$pageTitle = $pageTitle ?? APP_NAME;
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="brand">🏥 <?= e(APP_NAME) ?></div>
  <div class="userbox">
    <span><?= e($u['name']) ?> (<?= e($u['role']) ?>)</span>
    <a class="btn btn-sm" href="logout.php">Logout</a>
  </div>
</header>
<div class="layout">
  <nav class="sidebar">
    <?php
      $links = [
        'index'         => ['Dashboard',    '📊'],
        'doctors'       => ['Doctors',      '🩺'],
        'patients'      => ['Patients',     '🧑'],
        'appointments'  => ['Appointments', '📅'],
        'prescriptions' => ['Prescriptions','💊'],
        'billing'       => ['Billing',      '💳'],
        'reports'       => ['Reports',      '📈'],
      ];
      foreach ($links as $slug => [$label, $icon]):
    ?>
      <a class="navlink <?= $active === $slug ? 'active' : '' ?>"
         href="<?= $slug ?>.php"><span><?= $icon ?></span> <?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <main class="content">
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
