<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name']??'');
    $phone = trim($_POST['phone']??'');
    $address = trim($_POST['address']??'');
    $notes = trim($_POST['notes']??'');
    
    if($id){
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, address=?, notes=? WHERE id=?");
        $stmt->execute([$name, $phone, $address, $notes, $id]);
        $message = "Cập nhật nhà cung cấp thành công!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO suppliers(name, phone, address, notes) VALUES(?,?,?,?)");
        $stmt->execute([$name, $phone, $address, $notes]);
        $message = "Thêm nhà cung cấp mới thành công!";
    }
    redirect('/?page=suppliers');
}

if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id=?"); 
    $stmt->execute([(int)$_GET['id']]);
    $message = "Xóa nhà cung cấp thành công!";
    redirect('/?page=suppliers');
}

$title = 'Quản lý nhà cung cấp';
$rows = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">
      <i class="fas fa-truck me-2 text-primary"></i>Quản lý nhà cung cấp
    </h2>
    <p class="text-muted mb-0">Quản lý danh sách nhà cung cấp và thông tin liên hệ</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="/?page=export&type=suppliers">
      <i class="fas fa-download me-2"></i>Xuất CSV
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">
      <i class="fas fa-plus me-2"></i>Thêm nhà cung cấp
    </button>
  </div>
</div>

<!-- Suppliers Table -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-list me-2"></i>Danh sách nhà cung cấp (<?= count($rows) ?> nhà cung cấp)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="60">#</th>
            <th>Tên nhà cung cấp</th>
            <th width="120">Điện thoại</th>
            <th>Địa chỉ</th>
            <th width="200">Ghi chú</th>
            <th width="150" class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($rows)): ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="fas fa-truck fa-2x mb-3 d-block"></i>
                Chưa có nhà cung cấp nào. Hãy thêm nhà cung cấp đầu tiên!
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><span class="badge bg-primary"><?= $r['id'] ?></span></td>
                <td><strong><?= h($r['name']) ?></strong></td>
                <td>
                  <?php if($r['phone']): ?>
                    <a href="tel:<?= h($r['phone']) ?>" class="text-decoration-none">
                      <i class="fas fa-phone me-1"></i><?= h($r['phone']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($r['address']): ?>
                    <span title="<?= h($r['address']) ?>">
                      <i class="fas fa-map-marker-alt me-1"></i>
                      <?= strlen($r['address']) > 50 ? substr(h($r['address']), 0, 50) . '...' : h($r['address']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($r['notes']): ?>
                    <span class="text-muted" title="<?= h($r['notes']) ?>">
                      <?= strlen($r['notes']) > 30 ? substr(h($r['notes']), 0, 30) . '...' : h($r['notes']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" 
                            data-edit='<?= json_encode($r,JSON_HEX_APOS|JSON_HEX_AMP) ?>' 
                            title="Sửa nhà cung cấp">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a class="btn btn-outline-danger btn-delete" 
                       href="/?page=suppliers&action=delete&id=<?= $r['id'] ?>" 
                       title="Xóa nhà cung cấp">
                      <i class="fas fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="supplierForm" method="post">
        <input type="hidden" name="id">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-truck me-2"></i>
            <span id="modalTitle">Thêm nhà cung cấp mới</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label required">
                <i class="fas fa-building me-1"></i>Tên nhà cung cấp
              </label>
              <input class="form-control" name="name" required placeholder="Nhập tên nhà cung cấp">
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-phone me-1"></i>Số điện thoại
              </label>
              <input class="form-control" name="phone" placeholder="0123456789">
            </div>
            <div class="col-12">
              <label class="form-label">
                <i class="fas fa-map-marker-alt me-1"></i>Địa chỉ
              </label>
              <input class="form-control" name="address" placeholder="Nhập địa chỉ nhà cung cấp">
            </div>
            <div class="col-12">
              <label class="form-label">
                <i class="fas fa-sticky-note me-1"></i>Ghi chú
              </label>
              <textarea class="form-control" name="notes" rows="3" placeholder="Thông tin bổ sung về nhà cung cấp..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Lưu nhà cung cấp
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById("supplierModal").addEventListener("show.bs.modal", e => {
  const b = e.relatedTarget; 
  const modalTitle = document.getElementById("modalTitle");
  
  if(!b) { 
    document.getElementById("supplierForm").reset(); 
    modalTitle.textContent = "Thêm nhà cung cấp mới";
    return; 
  }
  
  const d = b.getAttribute("data-edit");
  if(d){ 
    const o = JSON.parse(d); 
    for(const k in o){
      const el = document.querySelector(`#supplierForm [name=${k}]`); 
      if(el) el.value = o[k] ?? ''; 
    }
    modalTitle.textContent = "Sửa nhà cung cấp";
  }
});
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
