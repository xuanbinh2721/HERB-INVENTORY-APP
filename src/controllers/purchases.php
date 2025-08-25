<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $date=$_POST['purchase_date']; $supplier=(int)($_POST['supplier_id']??0); $ref=trim($_POST['ref_no']??''); $notes=trim($_POST['notes']??'');
    $stmt=$pdo->prepare("INSERT INTO purchases(purchase_date,supplier_id,ref_no,notes) VALUES(?,?,?,?)");
    $stmt->execute([$date,$supplier,$ref,$notes]);
    $pid = $pdo->lastInsertId();

    $pids = $_POST['product_id']??[]; $qtys=$_POST['qty']??[]; $costs=$_POST['unit_cost']??[];
    for($i=0;$i<count($pids);$i++){
        $pp=(int)$pids[$i]; $qq=(float)$qtys[$i]; $cc=(float)$costs[$i];
        if($pp && $qq>0){
            $stmt=$pdo->prepare("INSERT INTO purchase_items(purchase_id,product_id,qty,unit_cost) VALUES(?,?,?,?)");
            $stmt->execute([$pid,$pp,$qq,$cc]);
        }
    }
    redirect('/?page=purchases');
}
if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt=$pdo->prepare("DELETE FROM purchases WHERE id=?"); $stmt->execute([(int)$_GET['id']]);
    redirect('/?page=purchases');
}

$title='Nhập hàng';
$rows = $pdo->query("SELECT p.*, s.name supplier, COALESCE(SUM(pi.qty*pi.unit_cost),0) total
                     FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id
                     LEFT JOIN purchase_items pi ON pi.purchase_id=p.id
                     GROUP BY p.id ORDER BY p.id DESC")->fetchAll();
$prods = $pdo->query("SELECT id,name FROM products ORDER BY name")->fetchAll();
$suppliers = $pdo->query("SELECT id,name FROM suppliers ORDER BY name")->fetchAll();

include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Nhập hàng</h3>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal">Tạo phiếu nhập</button>
</div>
<div class="mb-3"><a class="btn btn-secondary" href="/?page=export&type=purchases">Xuất CSV</a></div>

<div class="table-responsive">
  <table class="table table-striped">
    <thead><tr><th>ID</th><th>Ngày</th><th>Nhà cung cấp</th><th>Ref</th><th class="text-end">Tổng</th><th></th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= h($r['purchase_date']) ?></td>
          <td><?= h($r['supplier']) ?></td>
          <td><?= h($r['ref_no']) ?></td>
          <td class="text-end"><?= number_format($r['total'],2) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa phiếu nhập?')" href="/?page=purchases&action=delete&id=<?= $r['id'] ?>">Xóa</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="purchaseModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header"><h5 class="modal-title">Tạo phiếu nhập</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label required">Ngày</label><input type="date" class="form-control" name="purchase_date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-5"><label class="form-label">Nhà cung cấp</label>
              <select class="form-select" name="supplier_id"><option value="">-- Chọn --</option>
                <?php foreach($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2"><label class="form-label">Ref</label><input class="form-control" name="ref_no"></div>
            <div class="col-md-12"><label class="form-label">Ghi chú</label><input class="form-control" name="notes"></div>
          </div>
          <div class="table-responsive">
            <table class="table" id="purchaseItems"><thead><tr><th style="width:40%">Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th></th></tr></thead><tbody></tbody></table>
            <button type="button" class="btn btn-outline-primary" onclick="addPurchaseRow()">+ Thêm dòng</button>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function addPurchaseRow(){
  const tbody=document.querySelector('#purchaseItems tbody');
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><select class="form-select" name="product_id[]"><option value="">-- Chọn --</option>
    <?php foreach($prods as $p): ?><option value="<?= $p['id'] ?>"><?= h($p['name']) ?></option><?php endforeach; ?></select></td>
    <td><input type="number" step="0.001" min="0" class="form-control" name="qty[]"></td>
    <td><input type="number" step="0.01" min="0" class="form-control" name="unit_cost[]"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">X</button></td>`;
  tbody.appendChild(tr);
}
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
