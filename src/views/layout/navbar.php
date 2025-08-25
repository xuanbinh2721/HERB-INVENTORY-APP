<?php $u = current_user(); ?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">
      <i class="fas fa-leaf me-2"></i>Herb Inventory
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if($u): ?>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='dashboard' || !isset($_GET['page']) ? ' active':'' ?>" href="/?page=dashboard">
              <i class="fas fa-tachometer-alt me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='products' ? ' active':'' ?>" href="/?page=products">
              <i class="fas fa-box me-1"></i>Sản phẩm
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='suppliers' ? ' active':'' ?>" href="/?page=suppliers">
              <i class="fas fa-truck me-1"></i>Nhà cung cấp
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='customers' ? ' active':'' ?>" href="/?page=customers">
              <i class="fas fa-users me-1"></i>Khách hàng
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='purchases' ? ' active':'' ?>" href="/?page=purchases">
              <i class="fas fa-shopping-cart me-1"></i>Nhập hàng
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='sales' ? ' active':'' ?>" href="/?page=sales">
              <i class="fas fa-receipt me-1"></i>Bán hàng
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?= ($_GET['page']??'')==='revenue' ? ' active':'' ?>" href="/?page=revenue">
              <i class="fas fa-chart-line me-1"></i>Doanh thu
            </a>
          </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex gap-2 align-items-center">
        <?php if($u): ?>
          <a class="btn btn-outline-light btn-sm" href="/?page=export&type=stock">
            <i class="fas fa-download me-1"></i>Xuất CSV
          </a>
          <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="fas fa-user me-1"></i><?= h($u['username']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/?page=logout">
                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
              </a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-light btn-sm" href="/?page=login">
            <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
