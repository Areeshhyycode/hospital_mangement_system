<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$stats = [
    'doctors'      => (int) fetch_one('SELECT COUNT(*) c FROM doctors')['c'],
    'patients'     => (int) fetch_one('SELECT COUNT(*) c FROM patients')['c'],
    'appointments' => (int) fetch_one('SELECT COUNT(*) c FROM appointments')['c'],
    'unpaid'       => (int) fetch_one("SELECT COUNT(*) c FROM bills WHERE status <> 'paid'")['c'],
];

$today = fetch_all(
    "SELECT a.scheduled_at, a.status, p.full_name patient, d.full_name doctor
       FROM appointments a
       JOIN patients p ON p.id = a.patient_id
       JOIN doctors  d ON d.id = a.doctor_id
      WHERE DATE(a.scheduled_at) = CURDATE()
      ORDER BY a.scheduled_at"
);

$revenue = (float) fetch_one("SELECT COALESCE(SUM(total_amount),0) s FROM bills WHERE status='paid'")['s'];

$active = 'index';
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="cards">
  <div class="card"><div class="num"><?= $stats['doctors'] ?></div><div class="lbl">Doctors</div></div>
  <div class="card"><div class="num"><?= $stats['patients'] ?></div><div class="lbl">Patients</div></div>
  <div class="card"><div class="num"><?= $stats['appointments'] ?></div><div class="lbl">Appointments</div></div>
  <div class="card"><div class="num"><?= $stats['unpaid'] ?></div><div class="lbl">Unpaid bills</div></div>
  <div class="card"><div class="num"><?= CURRENCY ?> <?= number_format($revenue) ?></div><div class="lbl">Revenue (paid)</div></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>Today's appointments</h2>
    <a class="btn btn-sm" href="appointments.php">Manage &rarr;</a></div>
  <table>
    <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (!$today): ?>
        <tr><td colspan="4" class="empty">No appointments scheduled for today.</td></tr>
      <?php else: foreach ($today as $r): ?>
        <tr>
          <td><?= date('h:i A', strtotime($r['scheduled_at'])) ?></td>
          <td><?= e($r['patient']) ?></td>
          <td><?= e($r['doctor']) ?></td>
          <td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
