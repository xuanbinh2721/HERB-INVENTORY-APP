<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">
      <i class="fas fa-receipt me-2 text-primary"></i>Chi tiết hóa đơn #<?= $sale['id'] ?>
    </h2>
    <p class="text-muted mb-0">Thông tin chi tiết hóa đơn bán hàng</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="/?page=sales">
      <i class="fas fa-arrow-left me-2"></i>Quay lại
    </a>
    <a class="btn btn-outline-primary" href="/?page=export&type=sale_detail&id=<?= $sale['id'] ?>">
      <i class="fas fa-download me-2"></i>Xuất PDF
    </a>
  </div>
</div>

<!-- Sale Information -->
<div class="row g-4 mb-4">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-info-circle me-2"></i>Thông tin hóa đơn
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold text-muted">Số hóa đơn:</label>
            <div class="h5 mb-0">
              <?php if($sale['invoice_no']): ?>
                <code class="fs-4"><?= h($sale['invoice_no']) ?></code>
              <?php else: ?>
                <span class="text-muted">Không có</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold text-muted">Ngày bán:</label>
            <div class="h5 mb-0">
              <i class="fas fa-calendar me-2 text-primary"></i>
              <?= date('d/m/Y', strtotime($sale['sale_date'])) ?>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold text-muted">Khách hàng:</label>
            <div class="h5 mb-0">
              <?php if($sale['customer_name']): ?>
                <i class="fas fa-user me-2 text-success"></i>
                <?= h($sale['customer_name']) ?>
              <?php else: ?>
                <i class="fas fa-user me-2 text-muted"></i>
                <span class="text-muted">Khách lẻ</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold text-muted">Tổng tiền:</label>
            <div class="h4 mb-0 text-primary fw-bold">
              <?= number_format(array_sum(array_map(fn($item) => $item['qty'] * $item['unit_price'], $sale_items)), 0) ?> VNĐ
            </div>
          </div>
          <?php if($sale['notes']): ?>
            <div class="col-12">
              <label class="form-label fw-bold text-muted">Ghi chú:</label>
              <div class="p-3 bg-light rounded">
                <i class="fas fa-sticky-note me-2 text-warning"></i>
                <?= h($sale['notes']) ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-user me-2"></i>Thông tin khách hàng
        </h5>
      </div>
      <div class="card-body">
        <?php if($sale['customer_name']): ?>
          <div class="mb-3">
            <label class="form-label fw-bold text-muted">Tên:</label>
            <div><?= h($sale['customer_name']) ?></div>
          </div>
          <?php if($sale['customer_phone']): ?>
            <div class="mb-3">
              <label class="form-label fw-bold text-muted">Điện thoại:</label>
              <div>
                <i class="fas fa-phone me-1"></i>
                <?= h($sale['customer_phone']) ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if($sale['customer_address']): ?>
            <div class="mb-3">
              <label class="form-label fw-bold text-muted">Địa chỉ:</label>
              <div>
                <i class="fas fa-map-marker-alt me-1"></i>
                <?= h($sale['customer_address']) ?>
              </div>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="text-center text-muted py-3">
            <i class="fas fa-user-slash fa-2x mb-2"></i>
            <p class="mb-0">Khách lẻ</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Sale Items -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-boxes me-2"></i>Chi tiết sản phẩm (<?= count($sale_items) ?> sản phẩm)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="50">#</th>
            <th>Sản phẩm</th>
            <th width="100">SKU</th>
            <th width="80">Đơn vị</th>
            <th width="100" class="text-end">Số lượng</th>
            <th width="120" class="text-end">Đơn giá</th>
            <th width="120" class="text-end">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($sale_items)): ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="fas fa-box fa-2x mb-3 d-block"></i>
                Không có sản phẩm nào trong hóa đơn này
              </td>
            </tr>
          <?php else: ?>
            <?php 
            $total_amount = 0;
            foreach($sale_items as $index => $item): 
              $item_total = $item['qty'] * $item['unit_price'];
              $total_amount += $item_total;
            ?>
              <tr>
                <td><span class="badge bg-secondary"><?= $index + 1 ?></span></td>
                <td>
                  <div>
                    <strong><?= h($item['product_name']) ?></strong>
                  </div>
                </td>
                <td><code><?= h($item['sku']) ?></code></td>
                <td><?= h($item['unit']) ?></td>
                <td class="text-end"><?= number_format($item['qty'], 3) ?></td>
                <td class="text-end"><?= number_format($item['unit_price'], 0) ?> VNĐ</td>
                <td class="text-end fw-bold"><?= number_format($item_total, 0) ?> VNĐ</td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if(!empty($sale_items)): ?>
          <tfoot class="table-light">
            <tr>
              <td colspan="6" class="text-end fw-bold">Tổng cộng:</td>
              <td class="text-end fw-bold fs-5 text-primary"><?= number_format($total_amount, 0) ?> VNĐ</td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<?php if(!empty($sale_items)): ?>
<div class="row g-4 mt-4">
  <div class="col-md-3">
    <div class="card bg-primary text-white">
      <div class="card-body text-center">
        <i class="fas fa-box fa-2x mb-2"></i>
        <h4><?= count($sale_items) ?></h4>
        <p class="mb-0">Sản phẩm</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-success text-white">
      <div class="card-body text-center">
        <i class="fas fa-calculator fa-2x mb-2"></i>
        <h4><?= number_format(array_sum(array_map(fn($item) => $item['qty'], $sale_items)), 3) ?></h4>
        <p class="mb-0">Tổng số lượng</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-info text-white">
      <div class="card-body text-center">
        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
        <h4><?= number_format(array_sum(array_map(fn($item) => $item['unit_price'], $sale_items)) / count($sale_items), 0) ?> VNĐ</h4>
        <p class="mb-0">Đơn giá TB</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-warning text-white">
      <div class="card-body text-center">
        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
        <h4><?= number_format($total_amount, 0) ?> VNĐ</h4>
        <p class="mb-0">Tổng tiền</p>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
