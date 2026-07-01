<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

// ---- Handle actions ---------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = (int) ($_POST['id'] ?? 0);
        $name    = trim($_POST['full_name'] ?? '');
        $dept    = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;
        $spec    = trim($_POST['specialty'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $fee     = (float) ($_POST['consult_fee'] ?? 0);

        if ($name === '') {
            flash('Doctor name is required.', 'error');
        } elseif ($id > 0) {
            q("UPDATE doctors SET full_name=?, department_id=?, specialty=?, phone=?, email=?, consult_fee=? WHERE id=?",
              'sisssdi', [$name, $dept, $spec, $phone, $email, $fee, $id]);
            flash('Doctor updated.');
        } else {
            q("INSERT INTO doctors (full_name, department_id, specialty, phone, email, consult_fee) VALUES (?,?,?,?,?,?)",
              'sisssd', [$name, $dept, $spec, $phone, $email, $fee]);
            flash('Doctor added.');
        }
    } elseif ($action === 'delete') {
        q("DELETE FROM doctors WHERE id=?", 'i', [(int) $_POST['id']]);
        flash('Doctor removed.');
    }
    header('Location: doctors.php');
    exit;
}

$departments = fetch_all('SELECT id, name FROM departments ORDER BY name');
$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId ? fetch_one('SELECT * FROM doctors WHERE id=?', 'i', [$editId]) : null;

$doctors = fetch_all(
    "SELECT d.*, dep.name dept FROM doctors d
       LEFT JOIN departments dep ON dep.id = d.department_id
      ORDER BY d.full_name"
);

$active = 'doctors';
$pageTitle = 'Doctors';
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
  <h2><?= $editing ? 'Edit doctor' : 'Add doctor' ?></h2>
  <form method="post" class="grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div><label>Full name *</label>
      <input name="full_name" required value="<?= e($editing['full_name'] ?? '') ?>"></div>
    <div><label>Department</label>
      <select name="department_id">
        <option value="">— none —</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (($editing['department_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
            <?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label>Specialty</label>
      <input name="specialty" value="<?= e($editing['specialty'] ?? '') ?>"></div>
    <div><label>Consultation fee (<?= CURRENCY ?>)</label>
      <input name="consult_fee" type="number" step="0.01" min="0" value="<?= e($editing['consult_fee'] ?? '0') ?>"></div>
    <div><label>Phone</label>
      <input name="phone" value="<?= e($editing['phone'] ?? '') ?>"></div>
    <div><label>Email</label>
      <input name="email" type="email" value="<?= e($editing['email'] ?? '') ?>"></div>
    <div class="full">
      <button class="btn" type="submit"><?= $editing ? 'Update' : 'Add' ?> doctor</button>
      <?php if ($editing): ?><a class="btn btn-secondary" href="doctors.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All doctors (<?= count($doctors) ?>)</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Department</th><th>Specialty</th><th>Fee</th><th>Contact</th><th></th></tr></thead>
    <tbody>
    <?php if (!$doctors): ?>
      <tr><td colspan="6" class="empty">No doctors yet.</td></tr>
    <?php else: foreach ($doctors as $d): ?>
      <tr>
        <td><?= e($d['full_name']) ?></td>
        <td><?= e($d['dept'] ?? '—') ?></td>
        <td><?= e($d['specialty'] ?? '—') ?></td>
        <td><?= CURRENCY ?> <?= number_format((float) $d['consult_fee']) ?></td>
        <td><?= e($d['phone'] ?? '') ?><?= $d['email'] ? '<br>' . e($d['email']) : '' ?></td>
        <td class="row-actions actions">
          <a class="btn btn-sm btn-secondary" href="doctors.php?edit=<?= $d['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this doctor?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $d['id'] ?>">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
