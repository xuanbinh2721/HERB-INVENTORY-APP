<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../models/stock.php';

$title = 'Dashboard';

// Lấy thống kê tổng quan
$totalProducts = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
$totalSuppliers = $pdo->query("SELECT COUNT(*) as count FROM suppliers")->fetch()['count'];
$totalCustomers = $pdo->query("SELECT COUNT(*) as count FROM customers")->fetch()['count'];

// Tính tổng giá trị tồn kho
$products = $pdo->query("SELECT id, sku, name, unit FROM products ORDER BY name")->fetchAll();
$totalStockValue = 0;
foreach($products as $p) {
    $stock = product_stock($p['id']);
    $avgCost = product_avg_cost($p['id']);
    $totalStockValue += $stock * $avgCost;
}

// Lấy doanh thu tháng này
$currentMonth = date('Y-m');
$monthlyRevenue = $pdo->query("
    SELECT COALESCE(SUM(si.qty * si.unit_price), 0) as revenue 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id 
    WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = '$currentMonth'
")->fetch()['revenue'];

include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>

<!-- Stats Cards -->
<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($totalProducts) ?></h3>
          <p><i class="fas fa-box me-2"></i>Tổng sản phẩm</p>
        </div>
        <i class="fas fa-box fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($totalSuppliers) ?></h3>
          <p><i class="fas fa-truck me-2"></i>Nhà cung cấp</p>
        </div>
        <i class="fas fa-truck fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($totalCustomers) ?></h3>
          <p><i class="fas fa-users me-2"></i>Khách hàng</p>
        </div>
        <i class="fas fa-users fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="stats-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3><?= number_format($monthlyRevenue, 0) ?></h3>
          <p><i class="fas fa-chart-line me-2"></i>Doanh thu tháng</p>
        </div>
        <i class="fas fa-chart-line fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
</div>

<!-- Stock Overview -->
<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="fas fa-warehouse me-2"></i>Tồn kho hiện tại
    </h5>
    <div class="text-muted">
      Tổng giá trị: <strong><?= number_format($totalStockValue, 0) ?> VNĐ</strong>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>#</th>
            <th>SKU</th>
            <th>Tên sản phẩm</th>
            <th>ĐVT</th>
            <th class="text-end">Tồn kho</th>
            <th class="text-end">Giá vốn TB</th>
            <th class="text-end">Giá trị</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($products as $p): 
          $stock = product_stock($p['id']); 
          $avg = product_avg_cost($p['id']);
          $value = $stock * $avg;
        ?>
          <tr>
            <td><span class="badge bg-primary"><?= h($p['id']) ?></span></td>
            <td><code><?= h($p['sku']) ?></code></td>
            <td><strong><?= h($p['name']) ?></strong></td>
            <td><span class="badge bg-secondary"><?= h($p['unit']) ?></span></td>
            <td class="text-end">
              <span class="fw-bold <?= $stock > 0 ? 'text-success' : 'text-danger' ?>">
                <?= number_format($stock, 3) ?>
              </span>
            </td>
            <td class="text-end"><?= number_format($avg, 0) ?> VNĐ</td>
            <td class="text-end fw-bold"><?= number_format($value, 0) ?> VNĐ</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thao tác nhanh</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="/?page=products&action=add" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Thêm sản phẩm mới
          </a>
          <a href="/?page=purchases&action=add" class="btn btn-success">
            <i class="fas fa-shopping-cart me-2"></i>Tạo phiếu nhập
          </a>
          <a href="/?page=sales&action=add" class="btn btn-warning">
            <i class="fas fa-receipt me-2"></i>Tạo hóa đơn bán
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-download me-2"></i>Xuất báo cáo</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="/?page=export&type=stock" class="btn btn-outline-primary">
            <i class="fas fa-file-csv me-2"></i>Xuất báo cáo tồn kho
          </a>
          <a href="/?page=export&type=sales" class="btn btn-outline-success">
            <i class="fas fa-chart-bar me-2"></i>Xuất báo cáo bán hàng
          </a>
          <a href="/?page=revenue" class="btn btn-outline-info">
            <i class="fas fa-chart-pie me-2"></i>Xem báo cáo doanh thu
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
