<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../models/stock.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $date = $_POST['sale_date']; 
    $customer = (int)($_POST['customer_id']??0); 
    $inv = trim($_POST['invoice_no']??''); 
    $notes = trim($_POST['notes']??'');
    
    // Validation
    if(empty($date)) {
        $error = "Vui lòng chọn ngày bán hàng";
    } else {
        // Check if invoice number already exists
        if(!empty($inv)) {
            $check_inv = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE invoice_no = ?");
            $check_inv->execute([$inv]);
            if($check_inv->fetch()['count'] > 0) {
                $error = "Số hóa đơn đã tồn tại!";
            }
        }
        
        if(!isset($error)) {
            // Handle customer_id - if 0, set to NULL for foreign key constraint
            $customer_id = $customer > 0 ? $customer : null;
            
            // Insert sale header
            $stmt = $pdo->prepare("INSERT INTO sales(sale_date,customer_id,invoice_no,notes) VALUES(?,?,?,?)");
            $stmt->execute([$date, $customer_id, $inv, $notes]);
            $sid = $pdo->lastInsertId();

            // Insert sale items with stock validation
            $pids = $_POST['product_id']??[]; 
            $qtys = $_POST['qty']??[]; 
            $prices = $_POST['unit_price']??[];
            $total_items = 0;
            $errors = [];
            
            for($i=0; $i<count($pids); $i++){
                $pp = (int)$pids[$i]; 
                $qq = (float)$qtys[$i]; 
                $pr = (float)$prices[$i];
                
                if($pp && $qq > 0 && $pr > 0){
                    // Check stock availability
                    $current_stock = product_stock($pp);
                    if($current_stock < $qq) {
                        $product_name = $pdo->prepare("SELECT name FROM products WHERE id = ?")->execute([$pp]) ? 
                            $pdo->prepare("SELECT name FROM products WHERE id = ?")->fetch()['name'] : 'Unknown';
                        $errors[] = "Sản phẩm '$product_name' chỉ còn $current_stock trong kho, không đủ để bán $qq";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO sale_items(sale_id,product_id,qty,unit_price) VALUES(?,?,?,?)");
                        $stmt->execute([$sid, $pp, $qq, $pr]);
                        $total_items++;
                    }
                }
            }
            
            if(!empty($errors)) {
                // Rollback if there are stock errors
                $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sid]);
                $error = implode("<br>", $errors);
            } elseif($total_items > 0) {
                $message = "Tạo hóa đơn thành công với $total_items sản phẩm!";
                redirect('/?page=sales');
            } else {
                // Rollback if no valid items
                $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sid]);
                $error = "Vui lòng thêm ít nhất một sản phẩm";
            }
        }
    }
}

if(($_GET['action']??'')==='delete' && isset($_GET['id'])){
    $stmt = $pdo->prepare("DELETE FROM sales WHERE id=?"); 
    $stmt->execute([(int)$_GET['id']]);
    $message = "Xóa hóa đơn thành công!";
    redirect('/?page=sales');
}

// Handle view sale details
if(($_GET['action']??'')==='view' && isset($_GET['id'])){
    $sale_id = (int)$_GET['id'];
    
    // Get sale header
    $sale_stmt = $pdo->prepare("
        SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $sale_stmt->execute([$sale_id]);
    $sale = $sale_stmt->fetch();
    
    if(!$sale) {
        $error = "Không tìm thấy hóa đơn!";
    } else {
        // Get sale items
        $items_stmt = $pdo->prepare("
            SELECT si.*, p.name as product_name, p.sku, p.unit
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
            ORDER BY si.id
        ");
        $items_stmt->execute([$sale_id]);
        $sale_items = $items_stmt->fetchAll();
        
        $title = 'Chi tiết hóa đơn #' . $sale_id;
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/layout/navbar.php';
        
        // Show sale details view
        include __DIR__ . '/../views/sale_detail.php';
        include __DIR__ . '/../views/layout/footer.php';
        exit;
    }
}

$title = 'Quản lý bán hàng';
$rows = $pdo->query("
    SELECT s.*, c.name customer, COALESCE(SUM(si.qty*si.unit_price),0) total, COUNT(si.id) as item_count
    FROM sales s 
    LEFT JOIN customers c ON c.id=s.customer_id
    LEFT JOIN sale_items si ON si.sale_id=s.id
    GROUP BY s.id 
    ORDER BY s.id DESC
")->fetchAll();

$prods = $pdo->query("SELECT id,name,sku,unit FROM products ORDER BY name")->fetchAll();
$customers = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll();

include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">
      <i class="fas fa-receipt me-2 text-primary"></i>Quản lý bán hàng
    </h2>
    <p class="text-muted mb-0">Quản lý hóa đơn bán hàng và chi tiết sản phẩm</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="/?page=export&type=sales">
      <i class="fas fa-download me-2"></i>Xuất CSV
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal">
      <i class="fas fa-plus me-2"></i>Tạo hóa đơn
    </button>
  </div>
</div>

<?php if(isset($error)): ?>
  <div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
  </div>
<?php endif; ?>

<!-- Sales Table -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-list me-2"></i>Danh sách hóa đơn (<?= count($rows) ?> hóa đơn)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="60">#</th>
            <th width="100">Ngày</th>
            <th>Khách hàng</th>
            <th width="120">Số HĐ</th>
            <th width="100" class="text-center">Số SP</th>
            <th width="120" class="text-end">Tổng tiền</th>
            <th width="150" class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($rows)): ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="fas fa-receipt fa-2x mb-3 d-block"></i>
                Chưa có hóa đơn nào. Hãy tạo hóa đơn đầu tiên!
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><span class="badge bg-primary"><?= $r['id'] ?></span></td>
                <td><strong><?= date('d/m/Y', strtotime($r['sale_date'])) ?></strong></td>
                <td>
                  <?php if($r['customer']): ?>
                    <i class="fas fa-user me-1"></i><?= h($r['customer']) ?>
                  <?php else: ?>
                    <span class="text-muted">Khách lẻ</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($r['invoice_no']): ?>
                    <code><?= h($r['invoice_no']) ?></code>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-info"><?= $r['item_count'] ?></span>
                </td>
                <td class="text-end fw-bold"><?= number_format($r['total'], 0) ?> VNĐ</td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-primary" 
                       href="/?page=sales&action=view&id=<?= $r['id'] ?>" 
                       title="Xem chi tiết">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a class="btn btn-outline-danger btn-delete" 
                       href="/?page=sales&action=delete&id=<?= $r['id'] ?>" 
                       title="Xóa hóa đơn">
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

<!-- Sale Modal -->
<div class="modal fade" id="saleModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="post" id="saleForm">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-receipt me-2"></i>Tạo hóa đơn mới
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Header Info -->
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <label class="form-label required">
                <i class="fas fa-calendar me-1"></i>Ngày bán
              </label>
              <input type="date" class="form-control" name="sale_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="fas fa-user me-1"></i>Khách hàng
              </label>
              <select class="form-select" name="customer_id">
                <option value="">-- Chọn khách hàng --</option>
                <?php foreach($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">
                <i class="fas fa-hashtag me-1"></i>Số hóa đơn
              </label>
              <input class="form-control" name="invoice_no" placeholder="HD-001">
            </div>
            <div class="col-12">
              <label class="form-label">
                <i class="fas fa-sticky-note me-1"></i>Ghi chú
              </label>
              <textarea class="form-control" name="notes" rows="2" placeholder="Ghi chú về hóa đơn..."></textarea>
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
                <table class="table table-sm mb-0" id="saleItems">
                  <thead class="table-light">
                    <tr>
                      <th style="width:35%">Sản phẩm</th>
                      <th style="width:15%">Tồn kho</th>
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
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSaleRow()">
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
            <i class="fas fa-save me-1"></i>Lưu hóa đơn
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Product stock data
const productStocks = {};
<?php foreach($prods as $p): ?>
  productStocks[<?= $p['id'] ?>] = <?= product_stock($p['id']) ?>;
<?php endforeach; ?>

function addSaleRow(){
  const tbody = document.querySelector('#saleItems tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-select form-select-sm" name="product_id[]" required onchange="updateStock(this)">
        <option value="">-- Chọn sản phẩm --</option>
        <?php foreach($prods as $p): ?>
          <option value="<?= $p['id'] ?>"><?= h($p['name']) ?> (<?= h($p['sku']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </td>
    <td class="align-middle">
      <span class="stock-display text-muted">-</span>
    </td>
    <td>
      <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="qty[]" required placeholder="0" onchange="validateQty(this)">
    </td>
    <td>
      <div class="input-group input-group-sm">
        <input type="number" step="1000" min="0" class="form-control" name="unit_price[]" required placeholder="0">
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

function updateStock(select) {
  const row = select.closest('tr');
  const productId = select.value;
  const stockDisplay = row.querySelector('.stock-display');
  const qtyInput = row.querySelector('[name="qty[]"]');
  
  if(productId && productStocks[productId] !== undefined) {
    const stock = productStocks[productId];
    stockDisplay.textContent = stock.toLocaleString();
    stockDisplay.className = stock > 0 ? 'stock-display text-success' : 'stock-display text-danger';
    
    // Set max quantity
    qtyInput.max = stock;
    qtyInput.placeholder = `Tối đa: ${stock}`;
  } else {
    stockDisplay.textContent = '-';
    stockDisplay.className = 'stock-display text-muted';
    qtyInput.max = '';
    qtyInput.placeholder = '0';
  }
}

function validateQty(input) {
  const row = input.closest('tr');
  const productId = row.querySelector('[name="product_id[]"]').value;
  const qty = parseFloat(input.value) || 0;
  
  if(productId && productStocks[productId] !== undefined) {
    const stock = productStocks[productId];
    if(qty > stock) {
      alert(`Số lượng vượt quá tồn kho! Tồn kho hiện tại: ${stock}`);
      input.value = stock;
      input.focus();
    }
  }
}

// Auto-calculate totals
document.addEventListener('input', function(e) {
  if(e.target.name === 'qty[]' || e.target.name === 'unit_price[]') {
    const row = e.target.closest('tr');
    const qty = parseFloat(row.querySelector('[name="qty[]"]').value) || 0;
    const price = parseFloat(row.querySelector('[name="unit_price[]"]').value) || 0;
    const total = qty * price;
    row.querySelector('td:nth-child(5) span').textContent = total > 0 ? total.toLocaleString() + ' VNĐ' : '-';
  }
});

// Form validation
document.getElementById('saleForm').addEventListener('submit', function(e) {
  const items = document.querySelectorAll('#saleItems tbody tr');
  if(items.length === 0) {
    e.preventDefault();
    alert('Vui lòng thêm ít nhất một sản phẩm!');
    return;
  }
  
  let hasValidItem = false;
  let stockErrors = [];
  
  items.forEach((item, index) => {
    const product = item.querySelector('[name="product_id[]"]').value;
    const qty = parseFloat(item.querySelector('[name="qty[]"]').value);
    const price = parseFloat(item.querySelector('[name="unit_price[]"]').value);
    
    if(product && qty > 0 && price > 0) {
      hasValidItem = true;
      
      // Check stock
      if(productStocks[product] !== undefined && qty > productStocks[product]) {
        const productName = item.querySelector('[name="product_id[]"] option:checked').text;
        stockErrors.push(`Sản phẩm "${productName}" chỉ còn ${productStocks[product]} trong kho`);
      }
    }
  });
  
  if(!hasValidItem) {
    e.preventDefault();
    alert('Vui lòng nhập đầy đủ thông tin sản phẩm!');
  } else if(stockErrors.length > 0) {
    e.preventDefault();
    alert('Lỗi tồn kho:\n' + stockErrors.join('\n'));
  }
});

// Add first row when modal opens
document.getElementById('saleModal').addEventListener('show.bs.modal', function() {
  document.getElementById('saleItems tbody').innerHTML = '';
  addSaleRow();
});
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
