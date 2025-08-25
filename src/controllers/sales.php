<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../models/stock.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $date=$_POST['sale_date']; $customer=(int)($_POST['customer_id']??0); $inv=trim($_POST['invoice_no']??''); $notes=trim($_POST['notes']??'');
    $stmt=$pdo->prepare("INSERT INTO sales(sale_date,customer_id,invoice_no,notes) VALUES(?,?,?,?)");
    $stmt->execute([$date,$customer,$inv,$notes]);
    $sid = $pdo->lastInsertId();

    $pids = $_POST['product_id']??[]; $qtys=$_POST['qty']??[]; $prices=$_POST['unit_price']??[];
    for($i=0;$i<count($pids);$i++){
        $pp=(int)$pids[$i]; $qq=(float)$qtys[$i]; $pr=(float)$prices[$i];
        if($pp && $qq>0){
            $stmt=$pdo->prepare("INSERT INTO sale_items(sale_id,product_id,qty,unit_price) VALUES(?,?,?,?)");
            $stmt->execute([$sid,$pp,$qq,$pr]);
        }
    }
    redirect('/?page=sales');
}
if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt=$pdo->prepare("DELETE FROM sales WHERE id=?"); $stmt->execute([(int)$_GET['id']]);
    redirect('/?page=sales');
}

$title='Xuất hàng / Hóa đơn';
$rows = $pdo->query("SELECT s.*, c.name customer, COALESCE(SUM(si.qty*si.unit_price),0) total
                     FROM sales s LEFT JOIN customers c ON c.id=s.customer_id
                     LEFT JOIN sale_items si ON si.sale_id=s.id
                     GROUP BY s.id ORDER BY s.id DESC")->fetchAll();
$prods = $pdo->query("SELECT id,name FROM products ORDER BY name")->fetchAll();
$customers = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll();

include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Xuất hàng / Hóa đơn</h3>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal">Tạo hóa đơn</button>
</div>
<div class="mb-3"><a class="btn btn-secondary" href="/?page=export&type=sales">Xuất CSV</a></div>

<div class="table-responsive">
  <table class="table table-striped">
    <thead><tr><th>ID</th><th>Ngày</th><th>Khách hàng</th><th>Hóa đơn</th><th class="text-end">Tổng</th><th></th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= h($r['sale_date']) ?></td>
          <td><?= h($r['customer']) ?></td>
          <td><?= h($r['invoice_no']) ?></td>
          <td class="text-end"><?= number_format($r['total'],2) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa hóa đơn?')" href="/?page=sales&action=delete&id=<?= $r['id'] ?>">Xóa</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="saleModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header"><h5 class="modal-title">Tạo hóa đơn</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label required">Ngày</label><input type="date" class="form-control" name="sale_date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-4"><label class="form-label">Khách hàng</label>
              <select class="form-select" name="customer_id"><option value="">-- Chọn --</option>
                <?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Số hóa đơn</label><input class="form-control" name="invoice_no" placeholder="VD: HD-001"></div>
            <div class="col-md-12"><label class="form-label">Ghi chú</label><input class="form-control" name="notes"></div>
          </div>
          <div class="table-responsive">
            <table class="table" id="saleItems"><thead><tr><th style="width:40%">Sản phẩm</th><th>Tồn</th><th>Số lượng</th><th>Đơn giá</th><th></th></tr></thead><tbody></tbody></table>
            <button type="button" class="btn btn-outline-primary" onclick="addSaleRow()">+ Thêm dòng</button>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function addSaleRow(){
  const tbody=document.querySelector('#saleItems tbody');
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><select class="form-select" name="product_id[]"><option value="">-- Chọn --</option>
    <?php foreach($prods as $p): ?><option value="<?= $p['id'] ?>"><?= h($p['name']) ?></option><?php endforeach; ?></select></td>
    <td class="align-middle">-</td>
    <td><input type="number" step="0.001" min="0" class="form-control" name="qty[]"></td>
    <td><input type="number" step="0.01" min="0" class="form-control" name="unit_price[]"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">X</button></td>`;
  tbody.appendChild(tr);
}
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
