<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../models/stock.php';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Validate date range
if(strtotime($from) > strtotime($to)) {
    $temp = $from;
    $from = $to;
    $to = $temp;
}

// Get sales data
$sales = $pdo->prepare("
    SELECT s.id, s.sale_date, s.invoice_no, c.name as customer_name, 
           COALESCE(SUM(si.qty*si.unit_price),0) total, COUNT(si.id) as item_count
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN sale_items si ON si.sale_id=s.id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY s.id 
    ORDER BY s.sale_date DESC
");
$sales->execute([$from, $to]);
$sales = $sales->fetchAll();

$rev_total = array_sum(array_map(fn($r)=>(float)$r['total'], $sales));

// Get detailed items for COGS calculation
$itemsStmt = $pdo->prepare("
    SELECT si.product_id, si.qty, si.unit_price, p.name as product_name, p.sku
    FROM sale_items si 
    JOIN sales s ON s.id=si.sale_id
    JOIN products p ON p.id=si.product_id
    WHERE s.sale_date BETWEEN ? AND ?
    ORDER BY s.sale_date DESC
");
$itemsStmt->execute([$from, $to]);
$items = $itemsStmt->fetchAll();

// Calculate COGS (Cost of Goods Sold) - using accurate cost at time of sale
$cogs = 0.0;
$cogs_details = [];
foreach($items as $it) {
    // Get the sale date for this item
    $sale_date_stmt = $pdo->prepare("
        SELECT s.sale_date 
        FROM sales s 
        JOIN sale_items si ON s.id = si.sale_id 
        WHERE si.product_id = ? AND si.qty = ? AND si.unit_price = ?
        ORDER BY s.sale_date DESC 
        LIMIT 1
    ");
    $sale_date_stmt->execute([$it['product_id'], $it['qty'], $it['unit_price']]);
    $sale_date = $sale_date_stmt->fetch()['sale_date'] ?? date('Y-m-d');
    
    // Calculate average cost at the time of sale
    $avg_cost = product_avg_cost($it['product_id'], $sale_date);
    $item_cogs = (float)$it['qty'] * (float)$avg_cost;
    $cogs += $item_cogs;
    
    // Store details for breakdown
    $cogs_details[] = [
        'product' => $it['product_name'],
        'sku' => $it['sku'],
        'qty' => $it['qty'],
        'avg_cost' => $avg_cost,
        'total_cost' => $item_cogs,
        'sale_date' => $sale_date
    ];
}

$gross_profit = $rev_total - $cogs;
$gross_margin = $rev_total > 0 ? ($gross_profit / $rev_total) * 100 : 0;

// Get additional statistics
$total_invoices = count($sales);
$avg_invoice_value = $total_invoices > 0 ? $rev_total / $total_invoices : 0;

// Get top selling products
$top_products = $pdo->prepare("
    SELECT p.name, p.sku, SUM(si.qty) as total_qty, SUM(si.qty * si.unit_price) as total_revenue
    FROM sale_items si 
    JOIN sales s ON s.id = si.sale_id
    JOIN products p ON p.id = si.product_id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY p.id, p.name, p.sku
    ORDER BY total_qty DESC
    LIMIT 5
");
$top_products->execute([$from, $to]);
$top_products = $top_products->fetchAll();

$title = 'Báo cáo doanh thu';
include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">
      <i class="fas fa-chart-line me-2 text-primary"></i>Báo cáo doanh thu
    </h2>
    <p class="text-muted mb-0">Thống kê doanh thu và lợi nhuận theo thời gian</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="/?page=export&type=revenue&from=<?= $from ?>&to=<?= $to ?>">
      <i class="fas fa-download me-2"></i>Xuất báo cáo
    </a>
  </div>
</div>

<!-- Date Filter -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form class="row g-3 align-items-end">
      <input type="hidden" name="page" value="revenue">
      <div class="col-md-3">
        <label class="form-label">
          <i class="fas fa-calendar me-1"></i>Từ ngày
        </label>
        <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">
          <i class="fas fa-calendar me-1"></i>Đến ngày
        </label>
        <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-filter me-1"></i>Lọc dữ liệu
        </button>
      </div>
      <div class="col-md-3">
        <a href="/?page=revenue" class="btn btn-outline-secondary w-100">
          <i class="fas fa-refresh me-1"></i>Tháng này
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Revenue Stats -->
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($rev_total, 0) ?></h3>
          <p><i class="fas fa-money-bill-wave me-2"></i>Tổng doanh thu</p>
        </div>
        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($cogs, 0) ?></h3>
          <p><i class="fas fa-shopping-cart me-2"></i>Giá vốn hàng bán</p>
        </div>
        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($gross_profit, 0) ?></h3>
          <p><i class="fas fa-chart-line me-2"></i>Lợi nhuận gộp</p>
        </div>
        <i class="fas fa-chart-line fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($gross_margin, 1) ?>%</h3>
          <p><i class="fas fa-percentage me-2"></i>Tỷ suất lợi nhuận</p>
        </div>
        <i class="fas fa-percentage fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
</div>

<!-- Additional Stats -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <i class="fas fa-receipt fa-2x text-primary mb-2"></i>
        <h4><?= number_format($total_invoices) ?></h4>
        <p class="text-muted mb-0">Tổng số hóa đơn</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
        <h4><?= number_format($avg_invoice_value, 0) ?> VNĐ</h4>
        <p class="text-muted mb-0">Giá trị HĐ trung bình</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <i class="fas fa-box fa-2x text-warning mb-2"></i>
        <h4><?= number_format(array_sum(array_map(fn($r)=>(int)$r['item_count'], $sales))) ?></h4>
        <p class="text-muted mb-0">Tổng sản phẩm bán</p>
      </div>
    </div>
  </div>
</div>

<!-- Top Products -->
<?php if(!empty($top_products)): ?>
<div class="card shadow-sm mb-4">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-trophy me-2"></i>Sản phẩm bán chạy nhất
    </h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>#</th>
            <th>Sản phẩm</th>
            <th>SKU</th>
            <th class="text-end">Số lượng bán</th>
            <th class="text-end">Doanh thu</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($top_products as $index => $product): ?>
            <tr>
              <td><span class="badge bg-primary"><?= $index + 1 ?></span></td>
              <td><strong><?= h($product['name']) ?></strong></td>
              <td><code><?= h($product['sku']) ?></code></td>
              <td class="text-end"><?= number_format($product['total_qty'], 3) ?></td>
              <td class="text-end fw-bold"><?= number_format($product['total_revenue'], 0) ?> VNĐ</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Sales Details -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-list me-2"></i>Chi tiết hóa đơn (<?= count($sales) ?> hóa đơn)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="80">Ngày</th>
            <th width="120">Số HĐ</th>
            <th>Khách hàng</th>
            <th width="100" class="text-center">Số SP</th>
            <th width="120" class="text-end">Tổng tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($sales)): ?>
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">
                <i class="fas fa-chart-line fa-2x mb-3 d-block"></i>
                Không có dữ liệu bán hàng trong khoảng thời gian này
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($sales as $r): ?>
              <tr>
                <td><strong><?= date('d/m/Y', strtotime($r['sale_date'])) ?></strong></td>
                <td>
                  <?php if($r['invoice_no']): ?>
                    <code><?= h($r['invoice_no']) ?></code>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($r['customer_name']): ?>
                    <i class="fas fa-user me-1"></i><?= h($r['customer_name']) ?>
                  <?php else: ?>
                    <span class="text-muted">Khách lẻ</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-info"><?= $r['item_count'] ?></span>
                </td>
                <td class="text-end fw-bold"><?= number_format($r['total'], 0) ?> VNĐ</td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- COGS Details -->
<?php if(!empty($cogs_details)): ?>
<div class="card shadow-sm mt-4">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-calculator me-2"></i>Chi tiết giá vốn hàng bán
    </h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Sản phẩm</th>
            <th>SKU</th>
            <th class="text-end">Số lượng bán</th>
            <th class="text-end">Giá vốn TB</th>
            <th class="text-end">Tổng giá vốn</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($cogs_details as $detail): ?>
            <tr>
              <td><strong><?= h($detail['product']) ?></strong></td>
              <td><code><?= h($detail['sku']) ?></code></td>
              <td class="text-end"><?= number_format($detail['qty'], 3) ?></td>
              <td class="text-end"><?= number_format($detail['avg_cost'], 0) ?> VNĐ</td>
              <td class="text-end fw-bold"><?= number_format($detail['total_cost'], 0) ?> VNĐ</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Notes -->
<div class="alert alert-info mt-4">
  <i class="fas fa-info-circle me-2"></i>
  <strong>Lưu ý:</strong> Giá vốn được tính theo phương pháp giá trung bình (toàn kỳ). 
  Để có báo cáo chính xác hơn, có thể triển khai phương pháp FIFO/LIFO hoặc theo lô hàng.
</div>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
