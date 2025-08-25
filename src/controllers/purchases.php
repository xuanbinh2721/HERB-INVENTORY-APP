<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $date = $_POST['purchase_date']; 
    $supplier = (int)($_POST['supplier_id']??0); 
    $ref = trim($_POST['ref_no']??''); 
    $notes = trim($_POST['notes']??'');
    
    // Validation
    if(empty($date)) {
        $error = "Vui lòng chọn ngày nhập hàng";
    } else {
        // Insert purchase header
        $stmt = $pdo->prepare("INSERT INTO purchases(purchase_date,supplier_id,ref_no,notes) VALUES(?,?,?,?)");
        $stmt->execute([$date, $supplier, $ref, $notes]);
        $pid = $pdo->lastInsertId();

        // Insert purchase items
        $pids = $_POST['product_id']??[]; 
        $qtys = $_POST['qty']??[]; 
        $costs = $_POST['unit_cost']??[];
        $total_items = 0;
        
        for($i=0; $i<count($pids); $i++){
            $pp = (int)$pids[$i]; 
            $qq = (float)$qtys[$i]; 
            $cc = (float)$costs[$i];
            
            if($pp && $qq > 0 && $cc > 0){
                $stmt = $pdo->prepare("INSERT INTO purchase_items(purchase_id,product_id,qty,unit_cost) VALUES(?,?,?,?)");
                $stmt->execute([$pid, $pp, $qq, $cc]);
                $total_items++;
            }
        }
        
        if($total_items > 0) {
            $message = "Tạo phiếu nhập thành công với $total_items sản phẩm!";
        } else {
            $error = "Vui lòng thêm ít nhất một sản phẩm";
        }
    }
    
    if(!isset($error)) {
        redirect('/?page=purchases');
    }
}

if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt = $pdo->prepare("DELETE FROM purchases WHERE id=?"); 
    $stmt->execute([(int)$_GET['id']]);
    $message = "Xóa phiếu nhập thành công!";
    redirect('/?page=purchases');
}

$title = 'Quản lý nhập hàng';
$rows = $pdo->query("
    SELECT p.*, s.name supplier, COALESCE(SUM(pi.qty*pi.unit_cost),0) total, COUNT(pi.id) as item_count
    FROM purchases p 
    LEFT JOIN suppliers s ON s.id=p.supplier_id
    LEFT JOIN purchase_items pi ON pi.purchase_id=p.id
    GROUP BY p.id 
    ORDER BY p.id DESC
")->fetchAll();

$prods = $pdo->query("SELECT id,name,sku,unit FROM products ORDER BY name")->fetchAll();
$suppliers = $pdo->query("SELECT id,name FROM suppliers ORDER BY name")->fetchAll();

include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">
      <i class="fas fa-shopping-cart me-2 text-primary"></i>Quản lý nhập hàng
    </h2>
    <p class="text-muted mb-0">Quản lý phiếu nhập hàng và chi tiết sản phẩm</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="/?page=export&type=purchases">
      <i class="fas fa-download me-2"></i>Xuất CSV
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal">
      <i class="fas fa-plus me-2"></i>Tạo phiếu nhập
    </button>
  </div>
</div>

<?php if(isset($error)): ?>
  <div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($error) ?>
  </div>
<?php endif; ?>

<!-- Purchases Table -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-list me-2"></i>Danh sách phiếu nhập (<?= count($rows) ?> phiếu)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="60">#</th>
            <th width="100">Ngày</th>
            <th>Nhà cung cấp</th>
            <th width="120">Số phiếu</th>
            <th width="100" class="text-center">Số SP</th>
            <th width="120" class="text-end">Tổng tiền</th>
            <th width="150" class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($rows)): ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="fas fa-shopping-cart fa-2x mb-3 d-block"></i>
                Chưa có phiếu nhập nào. Hãy tạo phiếu nhập đầu tiên!
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><span class="badge bg-primary"><?= $r['id'] ?></span></td>
                <td><strong><?= date('d/m/Y', strtotime($r['purchase_date'])) ?></strong></td>
                <td>
                  <?php if($r['supplier']): ?>
                    <i class="fas fa-truck me-1"></i><?= h($r['supplier']) ?>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($r['ref_no']): ?>
                    <code><?= h($r['ref_no']) ?></code>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-info"><?= $r['item_count'] ?></span>
                </td>
                <td class="text-end fw-bold"><?= number_format($r['total'], 0) ?> VNĐ</td>
                <td class="text-center">
                  <a class="btn btn-outline-danger btn-sm btn-delete" 
                     href="/?page=purchases&action=delete&id=<?= $r['id'] ?>" 
                     title="Xóa phiếu nhập">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchaseModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="post" id="purchaseForm">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-shopping-cart me-2"></i>Tạo phiếu nhập mới
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Header Info -->
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <label class="form-label required">
                <i class="fas fa-calendar me-1"></i>Ngày nhập
              </label>
              <input type="date" class="form-control" name="purchase_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">
                <i class="fas fa-truck me-1"></i>Nhà cung cấp
              </label>
              <select class="form-select" name="supplier_id">
                <option value="">-- Chọn nhà cung cấp --</option>
                <?php foreach($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-hashtag me-1"></i>Số phiếu
              </label>
              <input class="form-control" name="ref_no" placeholder="PN-001">
            </div>
            <div class="col-12">
              <label class="form-label">
                <i class="fas fa-sticky-note me-1"></i>Ghi chú
              </label>
              <textarea class="form-control" name="notes" rows="2" placeholder="Ghi chú về phiếu nhập..."></textarea>
            </div>
          </div>
          
          <!-- Items Table -->
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="fas fa-boxes me-2"></i>Chi tiết sản phẩm
              </h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm mb-0" id="purchaseItems">
                  <thead class="table-light">
                    <tr>
                      <th style="width:40%">Sản phẩm</th>
                      <th style="width:15%">Số lượng</th>
                      <th style="width:20%">Đơn giá</th>
                      <th style="width:15%">Thành tiền</th>
                      <th style="width:10%">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <div class="card-footer">
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="addPurchaseRow()">
                <i class="fas fa-plus me-1"></i>Thêm sản phẩm
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Lưu phiếu nhập
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function addPurchaseRow(){
  const tbody = document.querySelector('#purchaseItems tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-select form-select-sm" name="product_id[]" required>
        <option value="">-- Chọn sản phẩm --</option>
        <?php foreach($prods as $p): ?>
          <option value="<?= $p['id'] ?>"><?= h($p['name']) ?> (<?= h($p['sku']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="qty[]" required placeholder="0">
    </td>
    <td>
      <div class="input-group input-group-sm">
        <input type="number" step="1000" min="0" class="form-control" name="unit_cost[]" required placeholder="0">
        <span class="input-group-text">VNĐ</span>
      </div>
    </td>
    <td class="align-middle">
      <span class="text-muted">-</span>
    </td>
    <td>
      <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove()">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  `;
  tbody.appendChild(tr);
}

// Auto-calculate totals
document.addEventListener('input', function(e) {
  if(e.target.name === 'qty[]' || e.target.name === 'unit_cost[]') {
    const row = e.target.closest('tr');
    const qty = parseFloat(row.querySelector('[name="qty[]"]').value) || 0;
    const cost = parseFloat(row.querySelector('[name="unit_cost[]"]').value) || 0;
    const total = qty * cost;
    row.querySelector('td:nth-child(4) span').textContent = total > 0 ? total.toLocaleString() + ' VNĐ' : '-';
  }
});

// Form validation
document.getElementById('purchaseForm').addEventListener('submit', function(e) {
  const items = document.querySelectorAll('#purchaseItems tbody tr');
  if(items.length === 0) {
    e.preventDefault();
    alert('Vui lòng thêm ít nhất một sản phẩm!');
    return;
  }
  
  let hasValidItem = false;
  items.forEach(item => {
    const product = item.querySelector('[name="product_id[]"]').value;
    const qty = parseFloat(item.querySelector('[name="qty[]"]').value);
    const cost = parseFloat(item.querySelector('[name="unit_cost[]"]').value);
    
    if(product && qty > 0 && cost > 0) {
      hasValidItem = true;
    }
  });
  
  if(!hasValidItem) {
    e.preventDefault();
    alert('Vui lòng nhập đầy đủ thông tin sản phẩm!');
  }
});

// Add first row when modal opens
document.getElementById('purchaseModal').addEventListener('show.bs.modal', function() {
  document.getElementById('purchaseItems tbody').innerHTML = '';
  addPurchaseRow();
});
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
