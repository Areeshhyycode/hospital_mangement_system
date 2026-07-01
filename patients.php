<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id     = (int) ($_POST['id'] ?? 0);
        $name   = trim($_POST['full_name'] ?? '');
        $gender = in_array($_POST['gender'] ?? '', ['male','female','other'], true) ? $_POST['gender'] : null;
        $dob    = ($_POST['dob'] ?? '') !== '' ? $_POST['dob'] : null;
        $phone  = trim($_POST['phone'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $addr   = trim($_POST['address'] ?? '');
        $blood  = trim($_POST['blood_group'] ?? '');

        if ($name === '') {
            flash('Patient name is required.', 'error');
        } elseif ($id > 0) {
            q("UPDATE patients SET full_name=?, gender=?, dob=?, phone=?, email=?, address=?, blood_group=? WHERE id=?",
              'sssssssi', [$name, $gender, $dob, $phone, $email, $addr, $blood, $id]);
            flash('Patient updated.');
        } else {
            q("INSERT INTO patients (full_name, gender, dob, phone, email, address, blood_group) VALUES (?,?,?,?,?,?,?)",
              'sssssss', [$name, $gender, $dob, $phone, $email, $addr, $blood]);
            flash('Patient registered.');
        }
    } elseif ($action === 'delete') {
        q("DELETE FROM patients WHERE id=?", 'i', [(int) $_POST['id']]);
        flash('Patient removed.');
    }
    header('Location: patients.php');
    exit;
}

$search  = trim($_GET['q'] ?? '');
$editId  = (int) ($_GET['edit'] ?? 0);
$editing = $editId ? fetch_one('SELECT * FROM patients WHERE id=?', 'i', [$editId]) : null;

if ($search !== '') {
    $patients = fetch_all("SELECT * FROM patients WHERE full_name LIKE ? OR phone LIKE ? ORDER BY full_name",
        'ss', ["%$search%", "%$search%"]);
} else {
    $patients = fetch_all('SELECT * FROM patients ORDER BY full_name');
}

function age_from(?string $dob): string {
    if (!$dob) return '—';
    return (string) (new DateTime($dob))->diff(new DateTime('today'))->y . ' yrs';
}

$active = 'patients';
$pageTitle = 'Patients';
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
  <h2><?= $editing ? 'Edit patient' : 'Register patient' ?></h2>
  <form method="post" class="grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div><label>Full name *</label>
      <input name="full_name" required value="<?= e($editing['full_name'] ?? '') ?>"></div>
    <div><label>Gender</label>
      <select name="gender">
        <?php foreach (['' => '—', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= (($editing['gender'] ?? '') === $v) ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label>Date of birth</label>
      <input name="dob" type="date" value="<?= e($editing['dob'] ?? '') ?>"></div>
    <div><label>Blood group</label>
      <input name="blood_group" maxlength="5" placeholder="e.g. O+" value="<?= e($editing['blood_group'] ?? '') ?>"></div>
    <div><label>Phone</label>
      <input name="phone" value="<?= e($editing['phone'] ?? '') ?>"></div>
    <div><label>Email</label>
      <input name="email" type="email" value="<?= e($editing['email'] ?? '') ?>"></div>
    <div class="full"><label>Address</label>
      <input name="address" value="<?= e($editing['address'] ?? '') ?>"></div>
    <div class="full">
      <button class="btn" type="submit"><?= $editing ? 'Update' : 'Register' ?></button>
      <?php if ($editing): ?><a class="btn btn-secondary" href="patients.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>Patients (<?= count($patients) ?>)</h2>
    <form method="get" class="actions">
      <input name="q" placeholder="Search name or phone" value="<?= e($search) ?>" style="width:220px">
      <button class="btn btn-sm">Search</button>
      <?php if ($search): ?><a class="btn btn-sm btn-secondary" href="patients.php">Clear</a><?php endif; ?>
    </form>
  </div>
  <table>
    <thead><tr><th>Name</th><th>Gender</th><th>Age</th><th>Blood</th><th>Contact</th><th></th></tr></thead>
    <tbody>
    <?php if (!$patients): ?>
      <tr><td colspan="6" class="empty">No patients found.</td></tr>
    <?php else: foreach ($patients as $p): ?>
      <tr>
        <td><?= e($p['full_name']) ?></td>
        <td><?= e(ucfirst($p['gender'] ?? '—')) ?></td>
        <td><?= age_from($p['dob']) ?></td>
        <td><?= e($p['blood_group'] ?? '—') ?></td>
        <td><?= e($p['phone'] ?? '') ?></td>
        <td class="row-actions actions">
          <a class="btn btn-sm btn-secondary" href="patients.php?edit=<?= $p['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete patient and all their records?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
