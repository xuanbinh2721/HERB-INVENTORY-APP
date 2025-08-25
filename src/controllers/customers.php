<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name']??''); $unit=trim($_POST['unit']??'kg'); $sku=trim($_POST['sku']??'');
    $cost=(float)($_POST['cost_hint']??0); $price=(float)($_POST['price_hint']??0); $notes=trim($_POST['notes']??'');
    if($id){
        $stmt=$pdo->prepare("UPDATE customers SET name=?, unit=?, sku=?, cost_hint=?, price_hint=?, notes=? WHERE id=?");
        $stmt->execute([$name,$unit,$sku,$cost,$price,$notes,$id]);
    } else {
        $stmt=$pdo->prepare("INSERT INTO customers(name,unit,sku,cost_hint,price_hint,notes) VALUES(?,?,?,?,?,?)");
        $stmt->execute([$name,$unit,$sku,$cost,$price,$notes]);
    }
    redirect('/?page=customers');
}
if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt=$pdo->prepare("DELETE FROM customers WHERE id=?"); $stmt->execute([(int)$_GET['id']]);
    redirect('/?page=customers');
}

$title = 'Khách hàng';
$rows = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Khách hàng</h3>
  <div class="d-flex gap-2">
    <a class="btn btn-secondary" href="/?page=export&type=customers">Xuất CSV</a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">Thêm</button>
  </div>
</div>
<div class="table-responsive">
  <table class="table table-hover">
    <thead><tr><th>ID</th><th>Tên</th><th>Điện thoại</th><th>Địa chỉ</th><th>Ghi chú</th><th></th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= h($r['name']) ?></td>
          <td><?= h($r['phone']) ?></td>
          <td><?= h($r['address']) ?></td>
          <td><?= h($r['notes']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="#" data-bs-toggle="modal" data-bs-target="#productModal" data-edit='<?= json_encode($r,JSON_HEX_APOS|JSON_HEX_AMP) ?>'>Sửa</a>
            <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa?')" href="/?page=customers&action=delete&id=<?= $r['id'] ?>">Xóa</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="productForm" method="post">
        <input type="hidden" name="id">
        <div class="modal-header">
          <h5 class="modal-title">Khách hàng</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6"><label class="form-label required">Tên</label><input class="form-control" name="name" required></div>
          <div class="col-md-6"><label class="form-label">Điện thoại</label><input class="form-control" name="phone"></div>
          <div class="col-md-6"><label class="form-label">Địa chỉ</label><input class="form-control" name="address"></div>
          <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="notes"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button class="btn btn-primary">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById("productModal").addEventListener("show.bs.modal", e => {
  const b = e.relatedTarget; if(!b) { document.getElementById("productForm").reset(); return; }
  const d = b.getAttribute("data-edit");
  if(d){ const o = JSON.parse(d); for(const k in o){ const el=document.querySelector(`#productForm [name=${k}]`); if(el) el.value=o[k]??''; } }
});
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
