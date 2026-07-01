<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

// Appointments per doctor
$byDoctor = fetch_all(
    "SELECT d.full_name doctor, COUNT(a.id) total,
            SUM(a.status='completed') completed,
            SUM(a.status='cancelled') cancelled
       FROM doctors d
       LEFT JOIN appointments a ON a.doctor_id = d.id
      GROUP BY d.id
      ORDER BY total DESC"
);

// Revenue by month (paid bills)
$byMonth = fetch_all(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym,
            SUM(total_amount) revenue, COUNT(*) bills
       FROM bills WHERE status='paid'
      GROUP BY ym ORDER BY ym DESC LIMIT 12"
);

// Patients by gender
$byGender = fetch_all("SELECT COALESCE(gender,'unknown') g, COUNT(*) c FROM patients GROUP BY g");

// Billing status summary
$billStatus = fetch_all(
    "SELECT status, COUNT(*) c, COALESCE(SUM(total_amount),0) amt FROM bills GROUP BY status");

// Busiest departments (by appointments through their doctors)
$byDept = fetch_all(
    "SELECT dep.name dept, COUNT(a.id) appts
       FROM departments dep
       LEFT JOIN doctors d ON d.department_id = dep.id
       LEFT JOIN appointments a ON a.doctor_id = d.id
      GROUP BY dep.id ORDER BY appts DESC"
);

$active = 'reports';
$pageTitle = 'Reports';
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
  <h2>Appointments per doctor</h2>
  <table>
    <thead><tr><th>Doctor</th><th>Total</th><th>Completed</th><th>Cancelled</th></tr></thead>
    <tbody>
    <?php if (!$byDoctor): ?><tr><td colspan="4" class="empty">No data.</td></tr>
    <?php else: foreach ($byDoctor as $r): ?>
      <tr><td><?= e($r['doctor']) ?></td><td><?= (int) $r['total'] ?></td>
          <td><?= (int) $r['completed'] ?></td><td><?= (int) $r['cancelled'] ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h2>Revenue by month (paid)</h2>
  <table>
    <thead><tr><th>Month</th><th>Bills</th><th>Revenue</th></tr></thead>
    <tbody>
    <?php if (!$byMonth): ?><tr><td colspan="3" class="empty">No paid bills yet.</td></tr>
    <?php else: foreach ($byMonth as $r): ?>
      <tr><td><?= e($r['ym']) ?></td><td><?= (int) $r['bills'] ?></td>
          <td><?= CURRENCY ?> <?= number_format((float) $r['revenue'], 2) ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="cards">
  <div class="panel" style="margin:0">
    <h2>Patients by gender</h2>
    <table><tbody>
      <?php foreach ($byGender as $r): ?>
        <tr><td><?= e(ucfirst($r['g'])) ?></td><td style="text-align:right"><?= (int) $r['c'] ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="panel" style="margin:0">
    <h2>Billing summary</h2>
    <table><tbody>
      <?php foreach ($billStatus as $r): ?>
        <tr><td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
            <td style="text-align:right"><?= (int) $r['c'] ?> &middot; <?= CURRENCY ?> <?= number_format((float) $r['amt']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="panel" style="margin:0">
    <h2>Busiest departments</h2>
    <table><tbody>
      <?php foreach ($byDept as $r): ?>
        <tr><td><?= e($r['dept']) ?></td><td style="text-align:right"><?= (int) $r['appts'] ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
