<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $patient   = (int) ($_POST['patient_id'] ?? 0);
        $doctor    = (int) ($_POST['doctor_id'] ?? 0);
        $appt      = ($_POST['appointment_id'] ?? '') !== '' ? (int) $_POST['appointment_id'] : null;
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $notes     = trim($_POST['notes'] ?? '');

        $meds = $_POST['medicine']  ?? [];
        $dose = $_POST['dosage']    ?? [];
        $freq = $_POST['frequency'] ?? [];
        $dur  = $_POST['duration']  ?? [];

        if (!$patient || !$doctor) {
            flash('Patient and doctor are required.', 'error');
        } else {
            global $db;
            $db->begin_transaction();
            try {
                q("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, diagnosis, notes)
                   VALUES (?,?,?,?,?)", 'iiiss', [$appt, $patient, $doctor, $diagnosis, $notes]);
                $pid = $db->insert_id;

                foreach ($meds as $i => $m) {
                    $m = trim($m);
                    if ($m === '') continue;
                    q("INSERT INTO prescription_items (prescription_id, medicine, dosage, frequency, duration)
                       VALUES (?,?,?,?,?)", 'issss',
                       [$pid, $m, trim($dose[$i] ?? ''), trim($freq[$i] ?? ''), trim($dur[$i] ?? '')]);
                }
                $db->commit();
                flash('Prescription saved.');
            } catch (mysqli_sql_exception $ex) {
                $db->rollback();
                flash('Could not save prescription: ' . $ex->getMessage(), 'error');
            }
        }
    } elseif ($action === 'delete') {
        q("DELETE FROM prescriptions WHERE id=?", 'i', [(int) $_POST['id']]);
        flash('Prescription deleted.');
    }
    header('Location: prescriptions.php');
    exit;
}

$patients = fetch_all('SELECT id, full_name FROM patients ORDER BY full_name');
$doctors  = fetch_all('SELECT id, full_name FROM doctors ORDER BY full_name');

$viewId = (int) ($_GET['view'] ?? 0);
$view   = null;
if ($viewId) {
    $view = fetch_one(
        "SELECT pr.*, p.full_name patient, d.full_name doctor
           FROM prescriptions pr
           JOIN patients p ON p.id = pr.patient_id
           JOIN doctors  d ON d.id = pr.doctor_id
          WHERE pr.id = ?", 'i', [$viewId]);
    if ($view) {
        $view['items'] = fetch_all('SELECT * FROM prescription_items WHERE prescription_id=?', 'i', [$viewId]);
    }
}

$list = fetch_all(
    "SELECT pr.id, pr.diagnosis, pr.issued_at, p.full_name patient, d.full_name doctor,
            (SELECT COUNT(*) FROM prescription_items pi WHERE pi.prescription_id = pr.id) items
       FROM prescriptions pr
       JOIN patients p ON p.id = pr.patient_id
       JOIN doctors  d ON d.id = pr.doctor_id
      ORDER BY pr.issued_at DESC"
);

$active = 'prescriptions';
$pageTitle = 'Prescriptions';
require __DIR__ . '/includes/header.php';
?>
<?php if ($view): ?>
  <div class="panel">
    <div class="panel-head">
      <h2>Prescription #<?= (int) $view['id'] ?></h2>
      <a class="btn btn-sm btn-secondary" href="prescriptions.php">&larr; Back</a>
    </div>
    <p><strong>Patient:</strong> <?= e($view['patient']) ?> &nbsp;|&nbsp;
       <strong>Doctor:</strong> <?= e($view['doctor']) ?> &nbsp;|&nbsp;
       <strong>Date:</strong> <?= date('d M Y', strtotime($view['issued_at'])) ?></p>
    <p><strong>Diagnosis:</strong> <?= e($view['diagnosis'] ?: '—') ?></p>
    <?php if ($view['notes']): ?><p><strong>Notes:</strong> <?= nl2br(e($view['notes'])) ?></p><?php endif; ?>
    <table>
      <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th></tr></thead>
      <tbody>
      <?php if (!$view['items']): ?>
        <tr><td colspan="4" class="empty">No medicines listed.</td></tr>
      <?php else: foreach ($view['items'] as $it): ?>
        <tr><td><?= e($it['medicine']) ?></td><td><?= e($it['dosage'] ?: '—') ?></td>
            <td><?= e($it['frequency'] ?: '—') ?></td><td><?= e($it['duration'] ?: '—') ?></td></tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="panel">
    <h2>New prescription</h2>
    <form method="post" class="grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <div><label>Patient *</label>
        <select name="patient_id" required>
          <option value="">— select —</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= $p['id'] ?>"><?= e($p['full_name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label>Doctor *</label>
        <select name="doctor_id" required>
          <option value="">— select —</option>
          <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="full"><label>Diagnosis</label>
        <input name="diagnosis" placeholder="e.g. Seasonal flu"></div>
      <div class="full"><label>Notes</label>
        <textarea name="notes" placeholder="Advice, follow-up, etc."></textarea></div>

      <div class="full"><label>Medicines</label>
        <table id="medTable">
          <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td><input name="medicine[]" placeholder="Paracetamol 500mg"></td>
              <td><input name="dosage[]" placeholder="1 tablet"></td>
              <td><input name="frequency[]" placeholder="3x/day"></td>
              <td><input name="duration[]" placeholder="5 days"></td>
              <td><button type="button" class="btn btn-sm btn-danger" onclick="rmRow(this)">✕</button></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="btn btn-sm btn-secondary" style="margin-top:8px" onclick="addRow()">+ Add medicine</button>
      </div>

      <div class="full"><button class="btn">Save prescription</button></div>
    </form>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Recent prescriptions (<?= count($list) ?>)</h2></div>
    <table>
      <thead><tr><th>Date</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Meds</th><th></th></tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="empty">No prescriptions yet.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['issued_at'])) ?></td>
          <td><?= e($r['patient']) ?></td>
          <td><?= e($r['doctor']) ?></td>
          <td><?= e($r['diagnosis'] ?: '—') ?></td>
          <td><?= (int) $r['items'] ?></td>
          <td class="row-actions actions">
            <a class="btn btn-sm btn-secondary" href="prescriptions.php?view=<?= $r['id'] ?>">View</a>
            <form method="post" onsubmit="return confirm('Delete prescription?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    function addRow(){
      var tb = document.querySelector('#medTable tbody');
      var tr = tb.rows[0].cloneNode(true);
      tr.querySelectorAll('input').forEach(function(i){ i.value=''; });
      tb.appendChild(tr);
    }
    function rmRow(btn){
      var tb = document.querySelector('#medTable tbody');
      if (tb.rows.length > 1) btn.closest('tr').remove();
      else btn.closest('tr').querySelectorAll('input').forEach(function(i){ i.value=''; });
    }
  </script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
