<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $patient  = (int) ($_POST['patient_id'] ?? 0);
        $doctor   = (int) ($_POST['doctor_id'] ?? 0);
        $when     = trim($_POST['scheduled_at'] ?? '');
        $reason   = trim($_POST['reason'] ?? '');
        $status   = in_array($_POST['status'] ?? '', ['scheduled','completed','cancelled','no_show'], true)
                    ? $_POST['status'] : 'scheduled';

        if (!$patient || !$doctor || $when === '') {
            flash('Patient, doctor and date/time are required.', 'error');
        } else {
            $dt = date('Y-m-d H:i:s', strtotime($when));
            try {
                if ($id > 0) {
                    q("UPDATE appointments SET patient_id=?, doctor_id=?, scheduled_at=?, reason=?, status=? WHERE id=?",
                      'iisssi', [$patient, $doctor, $dt, $reason, $status, $id]);
                    flash('Appointment updated.');
                } else {
                    q("INSERT INTO appointments (patient_id, doctor_id, scheduled_at, reason, status) VALUES (?,?,?,?,?)",
                      'iisss', [$patient, $doctor, $dt, $reason, $status]);
                    flash('Appointment scheduled.');
                }
            } catch (mysqli_sql_exception $ex) {
                if ($ex->getCode() === 1062) {
                    flash('That doctor already has an appointment at that exact time.', 'error');
                } else {
                    flash('Could not save appointment: ' . $ex->getMessage(), 'error');
                }
            }
        }
    } elseif ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', ['scheduled','completed','cancelled','no_show'], true)
                  ? $_POST['status'] : 'scheduled';
        q("UPDATE appointments SET status=? WHERE id=?", 'si', [$status, (int) $_POST['id']]);
        flash('Status updated.');
    } elseif ($action === 'delete') {
        q("DELETE FROM appointments WHERE id=?", 'i', [(int) $_POST['id']]);
        flash('Appointment deleted.');
    }
    header('Location: appointments.php');
    exit;
}

$patients = fetch_all('SELECT id, full_name FROM patients ORDER BY full_name');
$doctors  = fetch_all('SELECT id, full_name FROM doctors ORDER BY full_name');
$editId   = (int) ($_GET['edit'] ?? 0);
$editing  = $editId ? fetch_one('SELECT * FROM appointments WHERE id=?', 'i', [$editId]) : null;

$filter = $_GET['when'] ?? 'upcoming';
$where  = match ($filter) {
    'today' => "WHERE DATE(a.scheduled_at) = CURDATE()",
    'past'  => "WHERE a.scheduled_at < NOW()",
    'all'   => "",
    default => "WHERE a.scheduled_at >= CURDATE()",
};

$appts = fetch_all(
    "SELECT a.*, p.full_name patient, d.full_name doctor
       FROM appointments a
       JOIN patients p ON p.id = a.patient_id
       JOIN doctors  d ON d.id = a.doctor_id
       $where
      ORDER BY a.scheduled_at " . ($filter === 'past' ? 'DESC' : 'ASC')
);

$active = 'appointments';
$pageTitle = 'Appointments';
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
  <h2><?= $editing ? 'Edit appointment' : 'Schedule appointment' ?></h2>
  <form method="post" class="grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div><label>Patient *</label>
      <select name="patient_id" required>
        <option value="">— select —</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (($editing['patient_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
            <?= e($p['full_name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label>Doctor *</label>
      <select name="doctor_id" required>
        <option value="">— select —</option>
        <?php foreach ($doctors as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (($editing['doctor_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
            <?= e($d['full_name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label>Date &amp; time *</label>
      <input name="scheduled_at" type="datetime-local" required
             value="<?= e($editing ? date('Y-m-d\TH:i', strtotime($editing['scheduled_at'])) : '') ?>"></div>
    <div><label>Status</label>
      <select name="status">
        <?php foreach (['scheduled','completed','cancelled','no_show'] as $s): ?>
          <option value="<?= $s ?>" <?= (($editing['status'] ?? 'scheduled') === $s) ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="full"><label>Reason / notes</label>
      <input name="reason" value="<?= e($editing['reason'] ?? '') ?>"></div>
    <div class="full">
      <button class="btn"><?= $editing ? 'Update' : 'Schedule' ?></button>
      <?php if ($editing): ?><a class="btn btn-secondary" href="appointments.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>Appointments</h2>
    <div class="actions">
      <?php foreach (['upcoming'=>'Upcoming','today'=>'Today','past'=>'Past','all'=>'All'] as $k=>$l): ?>
        <a class="btn btn-sm <?= $filter===$k ? '' : 'btn-secondary' ?>" href="appointments.php?when=<?= $k ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <table>
    <thead><tr><th>When</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$appts): ?>
      <tr><td colspan="6" class="empty">No appointments.</td></tr>
    <?php else: foreach ($appts as $a): ?>
      <tr>
        <td><?= date('d M Y, h:i A', strtotime($a['scheduled_at'])) ?></td>
        <td><?= e($a['patient']) ?></td>
        <td><?= e($a['doctor']) ?></td>
        <td><?= e($a['reason'] ?? '—') ?></td>
        <td>
          <form method="post" style="display:flex;gap:4px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <select name="status" onchange="this.form.submit()" style="padding:3px 6px;font-size:12px">
              <?php foreach (['scheduled','completed','cancelled','no_show'] as $s): ?>
                <option value="<?= $s ?>" <?= $a['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td class="row-actions actions">
          <a class="btn btn-sm btn-secondary" href="appointments.php?edit=<?= $a['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete appointment?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
