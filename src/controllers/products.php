<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name']??''); $unit=trim($_POST['unit']??'kg'); $sku=trim($_POST['sku']??'');
    $cost=(float)($_POST['cost_hint']??0); $price=(float)($_POST['price_hint']??0); $notes=trim($_POST['notes']??'');
    if($id){
        $stmt=$pdo->prepare("UPDATE products SET name=?, unit=?, sku=?, cost_hint=?, price_hint=?, notes=? WHERE id=?");
        $stmt->execute([$name,$unit,$sku,$cost,$price,$notes,$id]);
        $message = "Cập nhật sản phẩm thành công!";
    } else {
        $stmt=$pdo->prepare("INSERT INTO products(name,unit,sku,cost_hint,price_hint,notes) VALUES(?,?,?,?,?,?)");
        $stmt->execute([$name,$unit,$sku,$cost,$price,$notes]);
        $message = "Thêm sản phẩm mới thành công!";
    }
    redirect('/?page=products');
}
if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt=$pdo->prepare("DELETE FROM products WHERE id=?"); $stmt->execute([(int)$_GET['id']]);
    $message = "Xóa sản phẩm thành công!";
    redirect('/?page=products');
}

$title = 'Quản lý sản phẩm';
$rows = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">
      <i class="fas fa-box me-2 text-primary"></i>Quản lý sản phẩm
    </h2>
    <p class="text-muted mb-0">Quản lý danh sách sản phẩm và thông tin chi tiết</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="/?page=export&type=products">
      <i class="fas fa-download me-2"></i>Xuất CSV
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
      <i class="fas fa-plus me-2"></i>Thêm sản phẩm
    </button>
  </div>
</div>

<!-- Products Table -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-list me-2"></i>Danh sách sản phẩm (<?= count($rows) ?> sản phẩm)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="60">#</th>
            <th width="120">SKU</th>
            <th>Tên sản phẩm</th>
            <th width="80">ĐVT</th>
            <th width="120" class="text-end">Giá vốn</th>
            <th width="120" class="text-end">Giá bán</th>
            <th width="200">Ghi chú</th>
            <th width="150" class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($rows)): ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="fas fa-box-open fa-2x mb-3 d-block"></i>
                Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên!
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><span class="badge bg-primary"><?= $r['id'] ?></span></td>
                <td><code class="bg-light px-2 py-1 rounded"><?= h($r['sku']) ?></code></td>
                <td><strong><?= h($r['name']) ?></strong></td>
                <td><span class="badge bg-secondary"><?= h($r['unit']) ?></span></td>
                <td class="text-end"><?= number_format($r['cost_hint'], 0) ?> VNĐ</td>
                <td class="text-end"><?= number_format($r['price_hint'], 0) ?> VNĐ</td>
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
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#productModal" 
                            data-edit='<?= json_encode($r,JSON_HEX_APOS|JSON_HEX_AMP) ?>' 
                            title="Sửa sản phẩm">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a class="btn btn-outline-danger btn-delete" 
                       href="/?page=products&action=delete&id=<?= $r['id'] ?>" 
                       title="Xóa sản phẩm">
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="productForm" method="post">
        <input type="hidden" name="id">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-box me-2"></i>
            <span id="modalTitle">Thêm sản phẩm mới</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label required">
                <i class="fas fa-tag me-1"></i>Tên sản phẩm
              </label>
              <input class="form-control" name="name" required placeholder="Nhập tên sản phẩm">
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-barcode me-1"></i>SKU
              </label>
              <input class="form-control" name="sku" placeholder="Mã sản phẩm">
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-ruler me-1"></i>Đơn vị tính
              </label>
              <select class="form-select" name="unit">
                <option value="kg">Kilogram (kg)</option>
                <option value="g">Gram (g)</option>
                <option value="l">Lít (l)</option>
                <option value="ml">Milliliter (ml)</option>
                <option value="cái">Cái</option>
                <option value="hộp">Hộp</option>
                <option value="gói">Gói</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-dollar-sign me-1"></i>Giá vốn gợi ý
              </label>
              <div class="input-group">
                <input type="number" step="1000" class="form-control" name="cost_hint" placeholder="0">
                <span class="input-group-text">VNĐ</span>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-tag me-1"></i>Giá bán gợi ý
              </label>
              <div class="input-group">
                <input type="number" step="1000" class="form-control" name="price_hint" placeholder="0">
                <span class="input-group-text">VNĐ</span>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">
                <i class="fas fa-sticky-note me-1"></i>Ghi chú
              </label>
              <textarea class="form-control" name="notes" rows="3" placeholder="Thông tin bổ sung về sản phẩm..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Lưu sản phẩm
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById("productModal").addEventListener("show.bs.modal", e => {
  const b = e.relatedTarget; 
  const modalTitle = document.getElementById("modalTitle");
  
  if(!b) { 
    document.getElementById("productForm").reset(); 
    modalTitle.textContent = "Thêm sản phẩm mới";
    return; 
  }
  
  const d = b.getAttribute("data-edit");
  if(d){ 
    const o = JSON.parse(d); 
    for(const k in o){
      const el = document.querySelector(`#productForm [name=${k}]`); 
      if(el) el.value = o[k] ?? ''; 
    }
    modalTitle.textContent = "Sửa sản phẩm";
  }
});
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
