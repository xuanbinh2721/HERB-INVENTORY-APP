<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../models/stock.php';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$sales = $pdo->prepare("SELECT s.id, s.sale_date, s.invoice_no, COALESCE(SUM(si.qty*si.unit_price),0) total
                        FROM sales s LEFT JOIN sale_items si ON si.sale_id=s.id
                        WHERE s.sale_date BETWEEN ? AND ?
                        GROUP BY s.id ORDER BY s.sale_date DESC");
$sales->execute([$from,$to]);
$sales = $sales->fetchAll();

$rev_total = array_sum(array_map(fn($r)=>(float)$r['total'],$sales));

$itemsStmt = $pdo->prepare("SELECT si.product_id, si.qty, si.unit_price
                            FROM sale_items si JOIN sales s ON s.id=si.sale_id
                            WHERE s.sale_date BETWEEN ? AND ?");
$itemsStmt->execute([$from,$to]);
$items=$itemsStmt->fetchAll();

$cogs = 0.0; foreach($items as $it){ $avg = product_avg_cost($it['product_id']); $cogs += (float)$it['qty'] * (float)$avg; }
$gross = $rev_total - $cogs;

$title='Doanh thu';
include __DIR__ . '/../views/layout/header.php';
include __DIR__ . '/../views/layout/navbar.php';
?>
<h3 class="mb-3">Báo cáo doanh thu</h3>
<form class="row g-2 mb-3">
  <input type="hidden" name="page" value="revenue">
  <div class="col-auto"><label class="form-label">Từ</label><input type="date" class="form-control" name="from" value="<?= h($from) ?>"></div>
  <div class="col-auto"><label class="form-label">Đến</label><input type="date" class="form-control" name="to" value="<?= h($to) ?>"></div>
  <div class="col-auto align-self-end"><button class="btn btn-primary">Lọc</button></div>
</form>

<div class="row g-3">
  <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Doanh thu</div><div class="display-6"><?= number_format($rev_total,2) ?> đ</div></div></div></div>
  <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Giá vốn (TB)</div><div class="display-6"><?= number_format($cogs,2) ?> đ</div></div></div></div>
  <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Lợi nhuận gộp (ước tính)</div><div class="display-6"><strong><?= number_format($gross,2) ?> đ</strong></div></div></div></div>
</div>

<div class="card mt-4 shadow-sm"><div class="card-body">
  <h5 class="card-title">Chi tiết hóa đơn</h5>
  <div class="table-responsive">
    <table class="table table-sm table-striped"><thead><tr><th>Ngày</th><th>Hóa đơn</th><th class="text-end">Tổng tiền</th></tr></thead><tbody>
      <?php foreach($sales as $r): ?><tr><td><?= h($r['sale_date']) ?></td><td><?= h($r['invoice_no']) ?></td><td class="text-end"><?= number_format($r['total'],2) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</div></div>

<p class="text-muted mt-3">* Lưu ý: Giá vốn tính theo giá nhập trung bình (toàn kỳ). Muốn chính xác hơn, hãy triển khai FIFO/LIFO.</p>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
