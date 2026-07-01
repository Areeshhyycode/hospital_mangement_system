<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $patient = (int) ($_POST['patient_id'] ?? 0);
        $status  = in_array($_POST['status'] ?? '', ['unpaid','paid','partial'], true) ? $_POST['status'] : 'unpaid';
        $descs   = $_POST['description'] ?? [];
        $qtys    = $_POST['quantity'] ?? [];
        $prices  = $_POST['unit_price'] ?? [];

        if (!$patient) {
            flash('Please select a patient.', 'error');
        } else {
            global $db;
            $db->begin_transaction();
            try {
                q("INSERT INTO bills (patient_id, total_amount, status) VALUES (?,0,?)", 'is', [$patient, $status]);
                $bid = $db->insert_id;

                $total = 0.0;
                foreach ($descs as $i => $desc) {
                    $desc = trim($desc);
                    if ($desc === '') continue;
                    $qty   = max(1, (int) ($qtys[$i] ?? 1));
                    $price = max(0, (float) ($prices[$i] ?? 0));
                    $total += $qty * $price;
                    q("INSERT INTO bill_items (bill_id, description, quantity, unit_price) VALUES (?,?,?,?)",
                      'isid', [$bid, $desc, $qty, $price]);
                }
                q("UPDATE bills SET total_amount=? WHERE id=?", 'di', [$total, $bid]);
                $db->commit();
                flash('Bill created.');
            } catch (mysqli_sql_exception $ex) {
                $db->rollback();
                flash('Could not create bill: ' . $ex->getMessage(), 'error');
            }
        }
    } elseif ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', ['unpaid','paid','partial'], true) ? $_POST['status'] : 'unpaid';
        q("UPDATE bills SET status=? WHERE id=?", 'si', [$status, (int) $_POST['id']]);
        flash('Bill status updated.');
    } elseif ($action === 'delete') {
        q("DELETE FROM bills WHERE id=?", 'i', [(int) $_POST['id']]);
        flash('Bill deleted.');
    }
    header('Location: billing.php');
    exit;
}

$patients = fetch_all('SELECT id, full_name FROM patients ORDER BY full_name');

$viewId = (int) ($_GET['view'] ?? 0);
$view = null;
if ($viewId) {
    $view = fetch_one(
        "SELECT b.*, p.full_name patient FROM bills b JOIN patients p ON p.id=b.patient_id WHERE b.id=?",
        'i', [$viewId]);
    if ($view) $view['items'] = fetch_all('SELECT * FROM bill_items WHERE bill_id=?', 'i', [$viewId]);
}

$bills = fetch_all(
    "SELECT b.*, p.full_name patient FROM bills b
       JOIN patients p ON p.id = b.patient_id
      ORDER BY b.created_at DESC"
);

$active = 'billing';
$pageTitle = 'Billing';
require __DIR__ . '/includes/header.php';
?>
<?php if ($view): ?>
  <div class="panel">
    <div class="panel-head">
      <h2>Invoice #<?= (int) $view['id'] ?></h2>
      <a class="btn btn-sm btn-secondary" href="billing.php">&larr; Back</a>
    </div>
    <p><strong>Patient:</strong> <?= e($view['patient']) ?> &nbsp;|&nbsp;
       <strong>Date:</strong> <?= date('d M Y', strtotime($view['created_at'])) ?> &nbsp;|&nbsp;
       <strong>Status:</strong> <span class="badge badge-<?= e($view['status']) ?>"><?= e($view['status']) ?></span></p>
    <table>
      <thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($view['items'] as $it):
        $amt = $it['quantity'] * $it['unit_price']; ?>
        <tr><td><?= e($it['description']) ?></td><td><?= (int) $it['quantity'] ?></td>
            <td><?= CURRENCY ?> <?= number_format($it['unit_price'], 2) ?></td>
            <td><?= CURRENCY ?> <?= number_format($amt, 2) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><th colspan="3" style="text-align:right">Total</th>
        <th><?= CURRENCY ?> <?= number_format((float) $view['total_amount'], 2) ?></th></tr></tfoot>
    </table>
  </div>
<?php else: ?>
  <div class="panel">
    <h2>New bill</h2>
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
      <div><label>Status</label>
        <select name="status">
          <option value="unpaid">unpaid</option>
          <option value="paid">paid</option>
          <option value="partial">partial</option>
        </select></div>

      <div class="full"><label>Line items</label>
        <table id="itemTable">
          <thead><tr><th>Description</th><th style="width:80px">Qty</th><th style="width:130px">Unit price</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td><input name="description[]" placeholder="Consultation fee"></td>
              <td><input name="quantity[]" type="number" min="1" value="1"></td>
              <td><input name="unit_price[]" type="number" step="0.01" min="0" value="0"></td>
              <td><button type="button" class="btn btn-sm btn-danger" onclick="rmRow(this)">✕</button></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="btn btn-sm btn-secondary" style="margin-top:8px" onclick="addRow()">+ Add item</button>
      </div>

      <div class="full"><button class="btn">Create bill</button></div>
    </form>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Bills (<?= count($bills) ?>)</h2></div>
    <table>
      <thead><tr><th>#</th><th>Date</th><th>Patient</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$bills): ?>
        <tr><td colspan="6" class="empty">No bills yet.</td></tr>
      <?php else: foreach ($bills as $b): ?>
        <tr>
          <td><?= (int) $b['id'] ?></td>
          <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
          <td><?= e($b['patient']) ?></td>
          <td><?= CURRENCY ?> <?= number_format((float) $b['total_amount'], 2) ?></td>
          <td>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="id" value="<?= $b['id'] ?>">
              <select name="status" onchange="this.form.submit()" style="padding:3px 6px;font-size:12px">
                <?php foreach (['unpaid','partial','paid'] as $s): ?>
                  <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td class="row-actions actions">
            <a class="btn btn-sm btn-secondary" href="billing.php?view=<?= $b['id'] ?>">View</a>
            <form method="post" onsubmit="return confirm('Delete bill?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $b['id'] ?>">
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
      var tb=document.querySelector('#itemTable tbody');
      var tr=tb.rows[0].cloneNode(true);
      tr.querySelectorAll('input').forEach(function(i){ i.value = (i.name==='quantity[]')?'1':(i.name==='unit_price[]'?'0':''); });
      tb.appendChild(tr);
    }
    function rmRow(btn){
      var tb=document.querySelector('#itemTable tbody');
      if(tb.rows.length>1) btn.closest('tr').remove();
    }
  </script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
